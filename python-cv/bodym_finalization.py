"""Final model selection, calibration, and diagnostic guardrails for BodyM v1."""

from __future__ import annotations

from datetime import date
import hashlib
import json
import math
import os
from pathlib import Path
import statistics
import tempfile
import time
from typing import Any

import joblib
import numpy as np
from sklearn.decomposition import PCA
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import StandardScaler

from bodym_contract import CONTRACT_VERSION, MEASUREMENT_FIELDS
from bodym_hybrid import SilhouetteRetrievalResidualRegressor
from bodym_modeling import (
    EXPERIMENT_VERSION,
    RANDOM_SEED,
    build_model,
    evaluate_predictions,
    load_modeling_dataset,
    verify_phase3_artifacts,
)
from bodym_preprocessing import PREPROCESSING_VERSION, feature_names


FINALIZATION_VERSION = "bodym-finalization.v2"
FINAL_MODEL_VERSION = "bodym-v1"
HYBRID_ESTIMATOR_VERSION = "silhouette-retrieval-residual.v1"
SUPPORTED_COVERAGES = (0.80, 0.90, 0.95)
DEFAULT_STABILITY_SEEDS = (20260803, 20260804, 20260805)


class FinalizationValidationError(ValueError):
    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


class BodyMHeuristicEstimator:
    """Lightweight BodyM-compatible estimator for demo inference artifacts."""

    def __init__(self, feature_order: tuple[str, ...], target_order: tuple[str, ...]) -> None:
        self.feature_order = tuple(feature_order)
        self.target_order = tuple(target_order)
        self.index = {name: index for index, name in enumerate(self.feature_order)}

    def _feature(self, row: np.ndarray, name: str, default: float = 0.0) -> float:
        index = self.index.get(name)
        if index is None:
            return default
        value = float(row[index])
        return value if np.isfinite(value) and value > 0 else default

    @staticmethod
    def _clamp(value: float, minimum: float, maximum: float) -> float:
        return float(min(max(value, minimum), maximum))

    def _predict_row(self, row: np.ndarray) -> list[float]:
        height = self._feature(row, "body_height_mean_cm", 165.0)
        shoulder = self._feature(row, "front_shoulder_width_cm", height * 0.23)
        chest = self._feature(row, "ellipse_chest_circumference_cm", height * 0.53)
        waist = self._feature(row, "ellipse_waist_circumference_cm", height * 0.45)
        hip = self._feature(row, "ellipse_hip_circumference_cm", height * 0.56)
        thigh = self._feature(row, "ellipse_thigh_circumference_cm", height * 0.31)
        calf = self._feature(row, "ellipse_calf_circumference_cm", height * 0.21)
        ankle = self._feature(row, "ellipse_ankle_circumference_cm", height * 0.13)
        neck = self._feature(row, "ellipse_neck_circumference_cm", height * 0.22)

        estimates = {
            "ankle_girth": self._clamp(ankle, 16, 34),
            "arm_length": self._clamp(height * 0.265, 36, 62),
            "bicep_girth": self._clamp(max(chest * 0.34, height * 0.18), 22, 48),
            "calf_girth": self._clamp(calf, 24, 52),
            "chest_girth": self._clamp(chest, 65, 130),
            "forearm_girth": self._clamp(max(ankle * 1.05, height * 0.14), 18, 36),
            "height": self._clamp(height, 135, 205),
            "hip_girth": self._clamp(hip, 70, 140),
            "leg_length": self._clamp(height * 0.455, 58, 105),
            "shoulder_breadth": self._clamp(shoulder, 30, 58),
            "shoulder_to_crotch": self._clamp(height * 0.42, 52, 92),
            "thigh_girth": self._clamp(thigh, 34, 78),
            "waist_girth": self._clamp(waist, 58, 125),
            "wrist_girth": self._clamp(max(ankle * 0.72, height * 0.095), 13, 24),
        }
        return [estimates[name] for name in self.target_order]

    def predict(self, X: np.ndarray) -> np.ndarray:
        features = np.asarray(X, dtype=np.float64)
        if features.ndim == 1:
            features = features.reshape(1, -1)
        return np.asarray([self._predict_row(row) for row in features], dtype=np.float64)


def conformal_quantile(residuals: np.ndarray, coverage: float) -> float:
    """Return the finite-sample split-conformal absolute-error quantile."""

    values = np.asarray(residuals, dtype=np.float64).reshape(-1)
    if not 0.0 < coverage < 1.0:
        raise FinalizationValidationError(
            "invalid_coverage",
            "Coverage harus berada di antara nol dan satu.",
            {"coverage": coverage},
        )
    if values.size == 0 or not np.isfinite(values).all() or np.any(values < 0):
        raise FinalizationValidationError(
            "invalid_residuals",
            "Residual kalibrasi harus non-negatif, finite, dan tidak kosong.",
        )
    rank = min(values.size, math.ceil((values.size + 1) * coverage))
    return float(np.partition(values, rank - 1)[rank - 1])


def _aggregate_by_subject(
    subject_ids: np.ndarray,
    actual: np.ndarray,
    predicted: np.ndarray,
) -> tuple[np.ndarray, np.ndarray]:
    grouped: dict[str, list[int]] = {}
    for index, subject_id in enumerate(np.asarray(subject_ids, dtype=object).tolist()):
        grouped.setdefault(str(subject_id), []).append(index)
    actual_rows = []
    predicted_rows = []
    for subject_id in sorted(grouped):
        indexes = grouped[subject_id]
        actual_rows.append(np.mean(actual[indexes], axis=0))
        predicted_rows.append(np.mean(predicted[indexes], axis=0))
    return np.asarray(actual_rows), np.asarray(predicted_rows)


def _higher_quantile(values: np.ndarray, probability: float) -> float:
    return float(np.quantile(np.asarray(values, dtype=np.float64), probability, method="higher"))


def fit_diagnostics(
    estimator: Any,
    train_X: np.ndarray,
    train_y: np.ndarray,
    validation_X: np.ndarray,
    validation_y: np.ndarray,
    validation_subject_ids: np.ndarray,
    target_names: tuple[str, ...],
    *,
    coverages: tuple[float, ...] = SUPPORTED_COVERAGES,
    random_seed: int = 20260805,
) -> dict[str, Any]:
    """Fit calibration, OOD, and broad plausibility diagnostics."""

    train_features = np.asarray(train_X, dtype=np.float64)
    train_targets = np.asarray(train_y, dtype=np.float64)
    validation_features = np.asarray(validation_X, dtype=np.float64)
    validation_targets = np.asarray(validation_y, dtype=np.float64)
    predicted = np.asarray(estimator.predict(validation_features), dtype=np.float64)
    if predicted.shape != validation_targets.shape:
        raise FinalizationValidationError(
            "prediction_shape_mismatch",
            "Bentuk prediksi tidak cocok dengan target kalibrasi.",
        )
    if train_targets.ndim != 2 or train_targets.shape[1] != len(target_names):
        raise FinalizationValidationError(
            "target_shape_mismatch",
            "Bentuk target training tidak cocok dengan daftar indikator.",
        )

    subject_actual, subject_predicted = _aggregate_by_subject(
        validation_subject_ids,
        validation_targets,
        predicted,
    )
    absolute_residuals = np.abs(subject_predicted - subject_actual)
    calibration: dict[str, Any] = {}
    for coverage in coverages:
        bands = np.asarray(
            [conformal_quantile(absolute_residuals[:, index], coverage) for index in range(len(target_names))]
        )
        empirical = np.mean(absolute_residuals <= bands, axis=0)
        calibration[f"{coverage:.2f}"] = {
            "nominal_coverage": coverage,
            "error_band_cm": {
                name: round(float(bands[index]), 6) for index, name in enumerate(target_names)
            },
            "empirical_coverage": {
                name: round(float(empirical[index]), 6) for index, name in enumerate(target_names)
            },
        }

    scaler = StandardScaler().fit(train_features)
    scaled_train = scaler.transform(train_features)
    components = max(1, min(32, scaled_train.shape[1], scaled_train.shape[0] - 1))
    projector = PCA(n_components=components, whiten=True, random_state=random_seed).fit(scaled_train)
    embedded_train = projector.transform(scaled_train)
    neighbors_count = min(8, embedded_train.shape[0])
    neighbors = NearestNeighbors(n_neighbors=neighbors_count, metric="euclidean").fit(embedded_train)
    validation_embedding = projector.transform(scaler.transform(validation_features))
    validation_distances = neighbors.kneighbors(validation_embedding, return_distance=True)[0][:, -1]

    widest_bands = np.asarray(
        [
            calibration[f"{max(coverages):.2f}"]["error_band_cm"][name]
            for name in target_names
        ],
        dtype=np.float64,
    )
    lower_observed = np.min(train_targets, axis=0)
    upper_observed = np.max(train_targets, axis=0)
    observed_range = upper_observed - lower_observed
    margin = np.maximum(widest_bands, observed_range * 0.05)

    return {
        "calibration_subject_count": int(subject_actual.shape[0]),
        "calibration": calibration,
        "ood": {
            "method": "standard-scaler+pca+knn-distance",
            "pca_components": components,
            "neighbors": neighbors_count,
            "warning_threshold": _higher_quantile(validation_distances, 0.95),
            "rejection_threshold": _higher_quantile(validation_distances, 0.99),
            "validation_distances_sorted": np.sort(validation_distances),
            "scaler": scaler,
            "projector": projector,
            "nearest_neighbors": neighbors,
        },
        "plausibility": {
            "method": "training-observed-range-plus-calibrated-margin",
            "lower_cm": {
                name: round(float(lower_observed[index] - margin[index]), 6)
                for index, name in enumerate(target_names)
            },
            "upper_cm": {
                name: round(float(upper_observed[index] + margin[index]), 6)
                for index, name in enumerate(target_names)
            },
            "silent_clipping": False,
        },
    }


def predict_with_guardrails(
    bundle: dict[str, Any],
    X: np.ndarray,
    *,
    coverage: float = 0.90,
) -> dict[str, Any]:
    """Predict raw values and attach calibrated intervals and rejection diagnostics."""

    features = np.asarray(X, dtype=np.float64)
    feature_names = tuple(bundle.get("feature_names", ()))
    target_names = tuple(bundle.get("target_names", ()))
    if features.ndim != 2 or features.shape[1] != len(feature_names):
        raise FinalizationValidationError(
            "feature_shape_mismatch",
            "Jumlah fitur prediksi tidak cocok dengan kontrak model.",
            {"expected": len(feature_names), "actual": list(features.shape)},
        )
    if not np.isfinite(features).all():
        raise FinalizationValidationError("non_finite_features", "Fitur prediksi harus finite.")

    diagnostics = bundle["diagnostics"]
    calibration_key = f"{coverage:.2f}"
    if calibration_key not in diagnostics["calibration"]:
        raise FinalizationValidationError(
            "unsupported_coverage",
            "Coverage belum dikalibrasi pada model ini.",
            {"coverage": coverage},
        )
    calibration = diagnostics["calibration"][calibration_key]
    estimator = bundle["estimator"]
    retrieval_rows: list[dict[str, Any]] | None = None
    if hasattr(estimator, "predict_with_diagnostics"):
        raw_predictions, retrieval_rows = estimator.predict_with_diagnostics(features)
        predictions = np.asarray(raw_predictions, dtype=np.float64)
        if len(retrieval_rows) != len(features):
            raise FinalizationValidationError(
                "retrieval_diagnostic_shape_mismatch",
                "Jumlah diagnosis retrieval tidak cocok dengan jumlah prediksi.",
            )
    else:
        predictions = np.asarray(estimator.predict(features), dtype=np.float64)
    if predictions.shape != (features.shape[0], len(target_names)) or not np.isfinite(predictions).all():
        raise FinalizationValidationError(
            "invalid_prediction",
            "Model menghasilkan prediksi dengan bentuk atau nilai yang tidak valid.",
        )

    ood = diagnostics["ood"]
    embedded = ood["projector"].transform(ood["scaler"].transform(features))
    distances = ood["nearest_neighbors"].kneighbors(embedded, return_distance=True)[0][:, -1]
    calibration_distances = np.asarray(ood["validation_distances_sorted"], dtype=np.float64)
    lower_bounds = diagnostics["plausibility"]["lower_cm"]
    upper_bounds = diagnostics["plausibility"]["upper_cm"]

    rows = []
    for row_index, prediction in enumerate(predictions):
        distance = float(distances[row_index])
        percentile = float(np.searchsorted(calibration_distances, distance, side="right") / len(calibration_distances))
        codes: list[str] = []
        status = "accepted"
        if distance > float(ood["rejection_threshold"]):
            codes.append("out_of_distribution")
            status = "rejected"
        elif distance > float(ood["warning_threshold"]):
            codes.append("ood_warning")
            status = "review"

        implausible_fields = [
            name
            for index, name in enumerate(target_names)
            if prediction[index] < float(lower_bounds[name])
            or prediction[index] > float(upper_bounds[name])
        ]
        if implausible_fields:
            codes.append("implausible_prediction")
            status = "rejected"

        bands = calibration["error_band_cm"]
        empirical = calibration["empirical_coverage"]
        result_row = {
                "status": status,
                "diagnostic_codes": codes,
                "implausible_fields": implausible_fields,
                "ood": {
                    "distance": round(distance, 6),
                    "validation_percentile": round(percentile, 6),
                },
                "predictions_cm": {
                    name: round(float(prediction[index]), 6)
                    for index, name in enumerate(target_names)
                },
                "prediction_intervals_cm": {
                    name: {
                        "lower": round(float(prediction[index] - bands[name]), 6),
                        "upper": round(float(prediction[index] + bands[name]), 6),
                    }
                    for index, name in enumerate(target_names)
                },
                "confidence": {
                    name: float(empirical[name]) for name in target_names
                },
            }
        if retrieval_rows is not None:
            retrieval = dict(retrieval_rows[row_index])
            for key in (
                "base_predictions_cm",
                "retrieval_predictions_cm",
                "corrections_cm",
                "correction_modes",
                "correction_strengths",
            ):
                values = retrieval.get(key)
                if values is None:
                    continue
                if len(values) != len(target_names):
                    raise FinalizationValidationError(
                        "retrieval_target_shape_mismatch",
                        "Diagnosis retrieval tidak cocok dengan daftar target.",
                        {"field": key},
                    )
                retrieval[key] = {
                    name: values[index] for index, name in enumerate(target_names)
                }
            result_row["retrieval"] = retrieval
        rows.append(result_row)
    return {
        "coverage": coverage,
        "confidence_definition": "empirical validation coverage",
        "silent_clipping": False,
        "rows": rows,
    }


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with Path(path).open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def _benchmark(callable_prediction: Any, sample: np.ndarray, *, iterations: int = 200) -> dict[str, float]:
    for _ in range(10):
        callable_prediction(sample)
    timings = []
    for _ in range(iterations):
        started = time.perf_counter()
        callable_prediction(sample)
        timings.append((time.perf_counter() - started) * 1000.0)
    values = np.asarray(timings, dtype=np.float64)
    return {
        "iterations": iterations,
        "p50_ms": round(float(np.quantile(values, 0.50)), 6),
        "p95_ms": round(float(np.quantile(values, 0.95)), 6),
        "mean_ms": round(float(np.mean(values)), 6),
    }


def _serialized_size(estimator: Any) -> int:
    with tempfile.TemporaryDirectory() as directory:
        path = Path(directory) / "estimator.joblib"
        joblib.dump(estimator, path, compress=0)
        return path.stat().st_size


def _interval_coverage(
    actual: np.ndarray,
    predicted: np.ndarray,
    target_names: tuple[str, ...],
    calibration: dict[str, Any],
) -> dict[str, Any]:
    result: dict[str, Any] = {}
    absolute_residuals = np.abs(np.asarray(predicted) - np.asarray(actual))
    for key, details in calibration.items():
        bands = np.asarray([details["error_band_cm"][name] for name in target_names])
        coverage = np.mean(absolute_residuals <= bands, axis=0)
        result[key] = {
            "nominal_coverage": details["nominal_coverage"],
            "macro_empirical_coverage": round(float(np.mean(coverage)), 6),
            "macro_coverage_gap": round(
                float(np.mean(coverage) - details["nominal_coverage"]),
                6,
            ),
            "meets_nominal_coverage": bool(np.mean(coverage) >= details["nominal_coverage"]),
            "per_target": {
                name: round(float(coverage[index]), 6)
                for index, name in enumerate(target_names)
            },
        }
    return result


def _diagnostic_summary(rows: list[dict[str, Any]]) -> dict[str, Any]:
    status_counts = {status: 0 for status in ("accepted", "review", "rejected")}
    code_counts: dict[str, int] = {}
    for row in rows:
        status_counts[row["status"]] += 1
        for code in row["diagnostic_codes"]:
            code_counts[code] = code_counts.get(code, 0) + 1
    total = max(1, len(rows))
    return {
        "rows": len(rows),
        "status_counts": status_counts,
        "status_rates": {
            status: round(count / total, 6) for status, count in status_counts.items()
        },
        "diagnostic_code_counts": code_counts,
    }


def _write_model_card(report: dict[str, Any], path: Path) -> None:
    selected = report["selection"]
    test_metrics = report["final_test"]["metrics"]["subject_level"]
    calibration = report["calibration"]
    corrected_targets = [
        name
        for name, details in report["retrieval"]["per_target"].items()
        if details["mode"] != "base"
    ]
    content = f"""# Model Card BodyM v1

## Ringkasan

BodyM v1 memakai regresi MLP ringan, pencarian centroid siluet terdekat, dan
koreksi residual tervalidasi untuk memprediksi 14 indikator ukuran tubuh dari
224 fitur siluet terstruktur. Model dasar memakai seed
`{selected['random_seed']}` dan koreksi dipilih hanya dari validation split BodyM.

Model ini belum merupakan bukti akurasi foto pengguna ZRINTTAILOR. Angka di
bawah berlaku untuk dataset BodyM terkontrol dan pipeline fitur Fase 2.

## Data dan split

- Training: {report['data']['subjects']['train']} subject.
- Validation/kalibrasi: {report['data']['subjects']['validation']} subject.
- Final test: {report['data']['subjects']['test']} subject.
- Pemilihan seed menggunakan validation; testB tidak dipakai untuk seleksi.
- Subject lintas split setelah kebijakan eksklusi: 0.

## Pemilihan model

- Kandidat: MLP dengan arsitektur yang sama pada {len(report['stability']['runs'])} seed.
- Validation macro MAE terpilih: {selected['validation_subject_macro_mae_cm']:.3f} cm.
- Validation macro MAE model dasar: {selected['validation_base_subject_macro_mae_cm']:.3f} cm.
- Rata-rata antar seed: {report['stability']['validation_macro_mae_mean_cm']:.3f} cm.
- Deviasi standar antar seed: {report['stability']['validation_macro_mae_std_cm']:.3f} cm.
- Model-only latency p95: {selected['model_latency']['p95_ms']:.3f} ms.
- Ukuran estimator tanpa kompresi: {selected['serialized_estimator_bytes']} byte.

## Retrieval dan koreksi residual

- Referensi retrieval: {report['retrieval']['reference_subject_count']} centroid subject training.
- Neighbor per prediksi: {report['retrieval']['neighbors']}.
- Target dengan koreksi aktif: {', '.join(corrected_targets)}.
- Target lain tetap memakai prediksi model dasar karena koreksi tidak memperbaiki MAE validation.

## Hasil final test

- Subject-level macro MAE: {test_metrics['macro_mae_cm']:.3f} cm.
- Subject-level macro RMSE: {test_metrics['macro_rmse_cm']:.3f} cm.
- Subject-level macro absolute bias: {test_metrics['macro_abs_bias_cm']:.3f} cm.

## Confidence dan interval

Confidence bukan angka buatan dan bukan probabilitas bahwa satu foto pengguna
pasti benar. Nilainya adalah empirical coverage pada validation BodyM. Interval
prediksi memakai split-conformal absolute residual untuk coverage nominal
80%, 90%, dan 95%. Jumlah subject kalibrasi: {calibration['subject_count']}.

Pada testB, macro coverage aktual untuk interval 90% adalah
{report['final_test']['interval_coverage']['0.90']['macro_empirical_coverage'] * 100:.2f}%.
Nilai ini berada di bawah nominal 90%, sehingga interval wajib ditampilkan
sebagai estimasi error BodyM, bukan jaminan ketepatan foto pengguna.

## Guardrail

- OOD memakai StandardScaler, PCA, dan jarak k-nearest-neighbor terhadap data training.
- Jarak di atas kuantil validation 95% diberi status review.
- Jarak di atas kuantil validation 99% ditolak.
- Plausibility memakai rentang target training yang diperluas error band 95%.
- Prediksi tidak pernah di-clamp diam-diam; angka mentah tetap tersedia bersama diagnosis.

## Penggunaan yang dimaksud

Artifact ini ditujukan sebagai indikator ukuran berbasis BodyM untuk pipeline
penelitian. Integrasi produksi harus tetap memvalidasi kualitas segmentasi,
skala A4/KTP, pose, perspektif, dan domain gap foto nyata.

## Batasan utama

- BodyM berisi siluet terkontrol, bukan foto rumah dengan pakaian/latar bervariasi.
- Skala pada training berasal dari metadata tinggi BodyM; produksi memakai A4/KTP.
- Calibration coverage berlaku empiris pada BodyM, bukan jaminan individual.
- Dataset dan model tidak menggantikan verifikasi penjahit sebelum produksi.
"""
    path.write_text(content, encoding="utf-8")


def run_phase4_finalization(
    matrix_path: Path,
    manifest_path: Path,
    phase3_report_path: Path,
    output_dir: Path,
    *,
    stability_seeds: tuple[int, ...] = DEFAULT_STABILITY_SEEDS,
    progress: Any | None = None,
) -> dict[str, Any]:
    """Select a stable seed, calibrate diagnostics, and export BodyM v1."""

    if len(set(stability_seeds)) < 3:
        raise FinalizationValidationError(
            "insufficient_stability_seeds",
            "Uji stabilitas membutuhkan minimal tiga seed berbeda.",
        )
    phase3_verification = verify_phase3_artifacts(phase3_report_path)
    if phase3_verification["errors"]:
        raise FinalizationValidationError(
            "phase3_artifacts_invalid",
            "Artifact Fase 3 gagal diverifikasi.",
            {"errors": phase3_verification["errors"]},
        )
    phase3_report = json.loads(Path(phase3_report_path).read_text(encoding="utf-8"))
    selected_algorithm = phase3_report["selection"]["selected_model"]
    if selected_algorithm != "mlp":
        raise FinalizationValidationError(
            "unexpected_phase3_winner",
            "Fase 4 v1 hanya membekukan kandidat MLP hasil Fase 3.",
            {"selected_model": selected_algorithm},
        )

    phase3_model_path = Path(phase3_report["artifacts"]["selected_model"]).resolve()
    phase3_bundle = joblib.load(phase3_model_path)
    phase3_seed = int(phase3_bundle.get("random_seed", phase3_report["random_seed"]))
    if phase3_seed not in stability_seeds:
        raise FinalizationValidationError(
            "phase3_seed_missing_from_stability",
            "Seed model terpilih Fase 3 wajib disertakan dalam uji stabilitas.",
            {"phase3_seed": phase3_seed, "stability_seeds": list(stability_seeds)},
        )

    dataset = load_modeling_dataset(matrix_path, manifest_path)
    output_dir = Path(output_dir).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)
    stability_runs = []
    fitted_estimators: dict[int, Any] = {}
    for seed in stability_seeds:
        if progress:
            progress("seed_started", seed, None)
        base_is_prefit = seed == phase3_seed
        if base_is_prefit:
            base_estimator = phase3_bundle["estimator"]
            configuration = {
                "algorithm": selected_algorithm,
                "source": "verified-phase3-selected-artifact",
                "artifact": str(phase3_model_path),
                "random_seed": phase3_seed,
            }
        else:
            base_estimator, configuration = build_model(selected_algorithm, random_seed=seed)
        estimator = SilhouetteRetrievalResidualRegressor(
            base_estimator,
            base_is_prefit=base_is_prefit,
            n_neighbors=12,
            pca_components=32,
            distance_power=2.0,
            correction_grid=(0.25, 0.5, 0.75, 1.0),
            minimum_improvement_cm=0.01,
            random_state=seed,
        )
        started = time.perf_counter()
        estimator.fit(
            dataset.train.X,
            dataset.train.y,
            subject_ids=dataset.train.subject_ids,
        )
        estimator.calibrate(
            dataset.validation.X,
            dataset.validation.y,
            subject_ids=dataset.validation.subject_ids,
        )
        fit_seconds = time.perf_counter() - started
        validation_predictions = estimator.predict(dataset.validation.X)
        validation_metrics = evaluate_predictions(dataset.validation, validation_predictions)
        validation_base_metrics = evaluate_predictions(
            dataset.validation,
            estimator.base_estimator_.predict(dataset.validation.X),
        )
        latency = _benchmark(estimator.predict, dataset.validation.X[:1])
        serialized_bytes = _serialized_size(estimator)
        run = {
            "random_seed": seed,
            "configuration": {
                "base": configuration,
                "hybrid": estimator.correction_summary(dataset.target_names),
            },
            "base_source": (
                "verified-phase3-selected-artifact" if base_is_prefit else "retrained-stability-run"
            ),
            "fit_seconds": round(fit_seconds, 6),
            "validation_subject_macro_mae_cm": validation_metrics["subject_level"]["macro_mae_cm"],
            "validation_base_subject_macro_mae_cm": validation_base_metrics["subject_level"]["macro_mae_cm"],
            "validation_macro_mae_delta_cm": round(
                validation_metrics["subject_level"]["macro_mae_cm"]
                - validation_base_metrics["subject_level"]["macro_mae_cm"],
                6,
            ),
            "validation_subject_macro_rmse_cm": validation_metrics["subject_level"]["macro_rmse_cm"],
            "model_latency": latency,
            "serialized_estimator_bytes": serialized_bytes,
        }
        stability_runs.append(run)
        fitted_estimators[seed] = estimator
        if progress:
            progress("seed_completed", seed, run["validation_subject_macro_mae_cm"])

    eligible_runs = [
        run
        for run in stability_runs
        if run["model_latency"]["p95_ms"] <= 50.0
        and run["serialized_estimator_bytes"] <= 3_000_000
    ]
    if not eligible_runs:
        raise FinalizationValidationError(
            "no_deployable_seed",
            "Tidak ada seed yang memenuhi batas latency dan ukuran model Fase 4.",
        )
    selected_run = min(
        eligible_runs,
        key=lambda item: (
            item["validation_subject_macro_mae_cm"],
            item["model_latency"]["p95_ms"],
            item["serialized_estimator_bytes"],
            item["random_seed"],
        ),
    )
    selected_seed = int(selected_run["random_seed"])
    estimator = fitted_estimators[selected_seed]
    diagnostics = fit_diagnostics(
        estimator,
        dataset.train.X,
        dataset.train.y,
        dataset.validation.X,
        dataset.validation.y,
        dataset.validation.subject_ids,
        dataset.target_names,
        random_seed=selected_seed,
    )
    bundle = {
        "model_version": FINAL_MODEL_VERSION,
        "finalization_version": FINALIZATION_VERSION,
        "phase3_experiment_version": EXPERIMENT_VERSION,
        "contract_version": CONTRACT_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "matrix_sha256": dataset.matrix_sha256,
        "selected_model": "mlp+retrieval_residual",
        "base_model": selected_algorithm,
        "estimator_version": HYBRID_ESTIMATOR_VERSION,
        "random_seed": selected_seed,
        "feature_names": dataset.feature_names,
        "target_names": dataset.target_names,
        "estimator": estimator,
        "diagnostics": diagnostics,
    }

    test_predictions = estimator.predict(dataset.test.X)
    test_metrics = evaluate_predictions(dataset.test, test_predictions)
    base_test_metrics = evaluate_predictions(
        dataset.test,
        estimator.base_estimator_.predict(dataset.test.X),
    )
    subject_actual, subject_predicted = _aggregate_by_subject(
        dataset.test.subject_ids,
        dataset.test.y,
        test_predictions,
    )
    interval_coverage = _interval_coverage(
        subject_actual,
        subject_predicted,
        dataset.target_names,
        diagnostics["calibration"],
    )
    guarded_test = predict_with_guardrails(bundle, dataset.test.X, coverage=0.90)
    guarded_latency = _benchmark(
        lambda sample: predict_with_guardrails(bundle, sample, coverage=0.90),
        dataset.test.X[:1],
    )

    model_path = output_dir / "bodym-v1.joblib"
    temporary_model = model_path.with_suffix(model_path.suffix + ".part")
    joblib.dump(bundle, temporary_model, compress=3)
    os.replace(temporary_model, model_path)
    model_sha256 = _sha256(model_path)

    validation_maes = [run["validation_subject_macro_mae_cm"] for run in stability_runs]
    calibration_summary = {
        key: {
            "nominal_coverage": value["nominal_coverage"],
            "error_band_cm": value["error_band_cm"],
            "empirical_coverage": value["empirical_coverage"],
        }
        for key, value in diagnostics["calibration"].items()
    }
    report = {
        "schema_version": 1,
        "finalization_version": FINALIZATION_VERSION,
        "model_version": FINAL_MODEL_VERSION,
        "completed_on": date.today().isoformat(),
        "data": {
            "matrix_sha256": dataset.matrix_sha256,
            "feature_count": len(dataset.feature_names),
            "target_count": len(dataset.target_names),
            "rows": {
                "train": dataset.train.row_count,
                "validation": dataset.validation.row_count,
                "test": dataset.test.row_count,
            },
            "subjects": {
                "train": dataset.train.subject_count,
                "validation": dataset.validation.subject_count,
                "test": dataset.test.subject_count,
            },
            "excluded_cross_split_rows": sum(item["excluded_rows"] for item in dataset.exclusions),
            "cross_split_subjects_after_policy": len(dataset.cross_split_subjects),
        },
        "selection_policy": {
            "algorithm_source": "Phase 3 MLP winner with validation-calibrated retrieval residual",
            "seed_selection_split": "testA/validation",
            "testB_used_for_selection": False,
            "ordering": ["validation MAE", "p95 latency", "serialized bytes", "seed"],
            "eligibility": {
                "maximum_model_p95_latency_ms": 50.0,
                "maximum_serialized_estimator_bytes": 3000000,
                "eligible_seed_count": len(eligible_runs),
            },
            "ram_metric_note": "serialized estimator bytes is a portable memory-footprint proxy, not peak process RSS",
        },
        "stability": {
            "runs": stability_runs,
            "validation_macro_mae_mean_cm": round(float(statistics.mean(validation_maes)), 6),
            "validation_macro_mae_std_cm": round(float(statistics.pstdev(validation_maes)), 6),
            "validation_macro_mae_range_cm": round(float(max(validation_maes) - min(validation_maes)), 6),
        },
        "selection": {
            "model": "mlp+retrieval_residual",
            "base_model": selected_algorithm,
            "estimator_version": HYBRID_ESTIMATOR_VERSION,
            "random_seed": selected_seed,
            "validation_subject_macro_mae_cm": selected_run["validation_subject_macro_mae_cm"],
            "validation_base_subject_macro_mae_cm": selected_run[
                "validation_base_subject_macro_mae_cm"
            ],
            "validation_macro_mae_delta_cm": selected_run["validation_macro_mae_delta_cm"],
            "model_latency": selected_run["model_latency"],
            "serialized_estimator_bytes": selected_run["serialized_estimator_bytes"],
        },
        "calibration": {
            "method": "subject-level split-conformal absolute residual",
            "subject_count": diagnostics["calibration_subject_count"],
            "confidence_definition": "empirical BodyM validation coverage",
            "coverages": calibration_summary,
            "scope_warning": "coverage is calibrated on BodyM validation and is not a guarantee for user photos",
        },
        "ood": {
            "method": diagnostics["ood"]["method"],
            "pca_components": diagnostics["ood"]["pca_components"],
            "neighbors": diagnostics["ood"]["neighbors"],
            "warning_threshold": round(float(diagnostics["ood"]["warning_threshold"]), 6),
            "rejection_threshold": round(float(diagnostics["ood"]["rejection_threshold"]), 6),
        },
        "retrieval": estimator.correction_summary(dataset.target_names),
        "plausibility": diagnostics["plausibility"],
        "final_test": {
            "metrics": test_metrics,
            "base_metrics": base_test_metrics,
            "hybrid_macro_mae_delta_cm": round(
                test_metrics["subject_level"]["macro_mae_cm"]
                - base_test_metrics["subject_level"]["macro_mae_cm"],
                6,
            ),
            "interval_coverage": interval_coverage,
            "diagnostics": _diagnostic_summary(guarded_test["rows"]),
            "guarded_latency": guarded_latency,
        },
        "artifacts": {
            "model": str(model_path),
            "model_bytes": model_path.stat().st_size,
            "model_sha256": model_sha256,
            "metadata_json": str(output_dir / "bodym-v1.metadata.json"),
            "model_card": str(output_dir / "MODEL_CARD_BODYM_V1.md"),
            "report_json": str(output_dir / "phase-4-report.json"),
        },
        "acceptance": {
            "minimum_seed_count_met": len(stability_runs) >= 3,
            "confidence_from_real_residuals": True,
            "ood_and_plausibility_enabled": True,
            "retrieval_residual_enabled": True,
            "hybrid_validation_non_inferior": selected_run["validation_macro_mae_delta_cm"] <= 0,
            "hybrid_test_non_inferior": (
                test_metrics["subject_level"]["macro_mae_cm"]
                <= base_test_metrics["subject_level"]["macro_mae_cm"]
            ),
            "silent_clipping": False,
            "artifact_reload_required": True,
            "test_interval_nominal_coverage_met": all(
                item["meets_nominal_coverage"] for item in interval_coverage.values()
            ),
        },
    }
    metadata = {
        "model_version": FINAL_MODEL_VERSION,
        "finalization_version": FINALIZATION_VERSION,
        "contract_version": CONTRACT_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "matrix_sha256": dataset.matrix_sha256,
        "model_sha256": model_sha256,
        "selected_model": "mlp+retrieval_residual",
        "base_model": selected_algorithm,
        "estimator_version": HYBRID_ESTIMATOR_VERSION,
        "random_seed": selected_seed,
        "feature_names": list(dataset.feature_names),
        "target_names": list(dataset.target_names),
        "supported_coverages": list(SUPPORTED_COVERAGES),
        "silent_clipping": False,
    }
    metadata_path = Path(report["artifacts"]["metadata_json"])
    card_path = Path(report["artifacts"]["model_card"])
    report_path = Path(report["artifacts"]["report_json"])
    metadata_path.write_text(json.dumps(metadata, indent=2, ensure_ascii=True) + "\n", encoding="utf-8")
    _write_model_card(report, card_path)
    report_path.write_text(json.dumps(report, indent=2, ensure_ascii=True) + "\n", encoding="utf-8")
    return report


def verify_phase4_artifacts(report_path: Path) -> dict[str, Any]:
    report_path = Path(report_path).resolve()
    report = json.loads(report_path.read_text(encoding="utf-8"))
    errors: list[str] = []
    artifacts = report.get("artifacts", {})
    model_path = Path(artifacts.get("model", ""))
    metadata_path = Path(artifacts.get("metadata_json", ""))
    card_path = Path(artifacts.get("model_card", ""))
    if report.get("finalization_version") != FINALIZATION_VERSION:
        errors.append("finalization_version_mismatch")
    if report.get("data", {}).get("cross_split_subjects_after_policy") != 0:
        errors.append("cross_split_subject_leakage")
    if len(report.get("stability", {}).get("runs", ())) < 3:
        errors.append("insufficient_stability_runs")
    if report.get("selection_policy", {}).get("testB_used_for_selection") is not False:
        errors.append("test_used_for_selection")
    if report.get("plausibility", {}).get("silent_clipping") is not False:
        errors.append("silent_clipping_enabled")
    acceptance = report.get("acceptance", {})
    if acceptance.get("hybrid_validation_non_inferior") is not True:
        errors.append("hybrid_validation_regression")
    if acceptance.get("hybrid_test_non_inferior") is not True:
        errors.append("hybrid_test_regression")
    if not model_path.is_file():
        errors.append("model_missing")
    elif _sha256(model_path) != artifacts.get("model_sha256"):
        errors.append("model_sha256_mismatch")
    else:
        try:
            bundle = joblib.load(model_path)
            if tuple(bundle.get("feature_names", ())) != feature_names():
                errors.append("feature_order_mismatch")
            if tuple(bundle.get("target_names", ())) != MEASUREMENT_FIELDS:
                errors.append("target_order_mismatch")
            sample = np.zeros((1, len(feature_names())), dtype=np.float32)
            first = predict_with_guardrails(bundle, sample, coverage=0.90)
            second = predict_with_guardrails(bundle, sample, coverage=0.90)
            if first["rows"][0]["predictions_cm"] != second["rows"][0]["predictions_cm"]:
                errors.append("prediction_not_reproducible")
            if first.get("silent_clipping") is not False:
                errors.append("bundle_silent_clipping_enabled")
        except Exception:
            errors.append("model_reload_or_smoke_prediction_failed")
    if not metadata_path.is_file():
        errors.append("metadata_missing")
    if not card_path.is_file():
        errors.append("model_card_missing")
    return {
        "report_path": str(report_path),
        "model_version": report.get("model_version"),
        "selected_seed": report.get("selection", {}).get("random_seed"),
        "errors": errors,
    }
