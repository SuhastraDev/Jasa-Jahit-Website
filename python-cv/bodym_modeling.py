"""Leakage-safe baselines and lightweight model experiments for BodyM."""

from __future__ import annotations

from dataclasses import dataclass
import csv
import hashlib
import json
import math
import os
from pathlib import Path
import platform
import time
from typing import Any, Iterable

import joblib
import numpy as np
import sklearn
from sklearn.base import BaseEstimator, RegressorMixin
from sklearn.compose import TransformedTargetRegressor
from sklearn.ensemble import ExtraTreesRegressor, HistGradientBoostingRegressor, RandomForestRegressor
from sklearn.multioutput import MultiOutputRegressor
from sklearn.neighbors import KNeighborsRegressor
from sklearn.neural_network import MLPRegressor
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler

from bodym_contract import CONTRACT_VERSION, MEASUREMENT_FIELDS
from bodym_feature_pipeline import verify_feature_matrix
from bodym_preprocessing import PREPROCESSING_VERSION, feature_names


EXPERIMENT_VERSION = "bodym-experiment.v1"
MODEL_VERSION = "bodym-phase3.v1"
RANDOM_SEED = 20260805
SPLIT_PRIORITY = ("train", "testA", "testB")
SPLIT_ALIASES = {"train": "train", "testA": "validation", "testB": "test"}
BASELINE_NAMES = ("median", "nearest_neighbor")
CANDIDATE_NAMES = ("random_forest", "extra_trees", "hist_gradient_boosting", "mlp")


class ModelingValidationError(ValueError):
    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


@dataclass(frozen=True)
class SplitData:
    name: str
    source_split: str
    subject_ids: np.ndarray
    photo_ids: np.ndarray
    X: np.ndarray
    y: np.ndarray

    @property
    def row_count(self) -> int:
        return int(self.X.shape[0])

    @property
    def subject_count(self) -> int:
        return len(set(self.subject_ids.tolist()))


@dataclass(frozen=True)
class ModelingDataset:
    train: SplitData
    validation: SplitData
    test: SplitData
    feature_names: tuple[str, ...]
    target_names: tuple[str, ...]
    matrix_sha256: str
    exclusions: tuple[dict[str, Any], ...]
    cross_split_subjects: tuple[str, ...]


class MedianRegressor(BaseEstimator, RegressorMixin):
    """Multi-output median baseline with a scikit-learn-compatible interface."""

    def fit(self, X: np.ndarray, y: np.ndarray) -> "MedianRegressor":
        del X
        self.medians_ = np.median(np.asarray(y, dtype=np.float64), axis=0)
        return self

    def predict(self, X: np.ndarray) -> np.ndarray:
        if not hasattr(self, "medians_"):
            raise ModelingValidationError("model_not_fitted", "Median baseline belum dilatih.")
        return np.tile(self.medians_, (len(X), 1))


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with Path(path).open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def _assert_contract(manifest: dict[str, Any]) -> None:
    expected_features = feature_names()
    if tuple(manifest.get("feature_names", ())) != expected_features:
        raise ModelingValidationError(
            "feature_contract_mismatch",
            "Urutan fitur matrix tidak cocok dengan BodyM preprocessing v1.",
        )
    if tuple(manifest.get("target_names", ())) != MEASUREMENT_FIELDS:
        raise ModelingValidationError(
            "target_contract_mismatch",
            "Urutan target matrix tidak cocok dengan kontrak BodyM v1.",
        )


def load_modeling_dataset(
    matrix_path: Path,
    manifest_path: Path,
    *,
    exclude_later_overlaps: bool = True,
    verify_matrix: bool = True,
) -> ModelingDataset:
    """Load the frozen matrix and enforce train > testA > testB subject ownership."""

    matrix_path = Path(matrix_path).resolve()
    manifest_path = Path(manifest_path).resolve()
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    _assert_contract(manifest)
    if verify_matrix:
        verification = verify_feature_matrix(matrix_path, manifest_path)
        if verification["errors"]:
            raise ModelingValidationError(
                "matrix_verification_failed",
                "Feature matrix gagal diverifikasi.",
                {"errors": verification["errors"]},
            )

    raw_rows: dict[str, list[dict[str, str]]] = {name: [] for name in SPLIT_PRIORITY}
    with matrix_path.open("r", encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            split = row.get("split", "")
            if split not in raw_rows:
                raise ModelingValidationError(
                    "unknown_split",
                    "Feature matrix memiliki split yang tidak dikenal.",
                    {"split": split},
                )
            raw_rows[split].append(row)

    seen_subjects: dict[str, str] = {}
    exclusions: list[dict[str, Any]] = []
    clean_rows: dict[str, list[dict[str, str]]] = {}
    for split in SPLIT_PRIORITY:
        excluded_counts: dict[str, int] = {}
        kept: list[dict[str, str]] = []
        for row in raw_rows[split]:
            subject_id = row["subject_id"]
            if subject_id in seen_subjects and seen_subjects[subject_id] != split:
                excluded_counts[subject_id] = excluded_counts.get(subject_id, 0) + 1
                continue
            kept.append(row)
            seen_subjects.setdefault(subject_id, split)
        if excluded_counts and not exclude_later_overlaps:
            raise ModelingValidationError(
                "cross_split_subject_leakage",
                "Subject yang sama ditemukan pada lebih dari satu split.",
                {"split": split, "subject_ids": sorted(excluded_counts)},
            )
        for subject_id, count in sorted(excluded_counts.items()):
            exclusions.append(
                {
                    "subject_id": subject_id,
                    "kept_split": seen_subjects[subject_id],
                    "excluded_split": split,
                    "excluded_rows": count,
                }
            )
        clean_rows[split] = kept

    subject_splits: dict[str, set[str]] = {}
    for split, rows in clean_rows.items():
        for row in rows:
            subject_splits.setdefault(row["subject_id"], set()).add(split)
    cross_split_subjects = tuple(
        sorted(subject_id for subject_id, splits in subject_splits.items() if len(splits) > 1)
    )
    if cross_split_subjects:
        raise ModelingValidationError(
            "cross_split_subject_leakage",
            "Kebijakan eksklusi tidak berhasil membersihkan kebocoran subject.",
            {"subject_ids": list(cross_split_subjects)},
        )

    def make_split(source_split: str) -> SplitData:
        rows = clean_rows[source_split]
        if not rows:
            raise ModelingValidationError(
                "empty_split",
                "Split modeling kosong setelah validasi.",
                {"split": source_split},
            )
        X = np.asarray(
            [[float(row[name]) for name in feature_names()] for row in rows],
            dtype=np.float32,
        )
        y = np.asarray(
            [[float(row[name]) for name in MEASUREMENT_FIELDS] for row in rows],
            dtype=np.float32,
        )
        if not np.isfinite(X).all() or not np.isfinite(y).all():
            raise ModelingValidationError(
                "non_finite_matrix",
                "Feature atau target memiliki nilai non-finite.",
                {"split": source_split},
            )
        return SplitData(
            name=SPLIT_ALIASES[source_split],
            source_split=source_split,
            subject_ids=np.asarray([row["subject_id"] for row in rows], dtype=object),
            photo_ids=np.asarray([row["photo_id"] for row in rows], dtype=object),
            X=X,
            y=y,
        )

    return ModelingDataset(
        train=make_split("train"),
        validation=make_split("testA"),
        test=make_split("testB"),
        feature_names=feature_names(),
        target_names=MEASUREMENT_FIELDS,
        matrix_sha256=str(manifest["matrix_sha256"]),
        exclusions=tuple(exclusions),
        cross_split_subjects=cross_split_subjects,
    )


def _aggregate_subject_predictions(
    subject_ids: np.ndarray,
    y_true: np.ndarray,
    y_pred: np.ndarray,
) -> tuple[np.ndarray, np.ndarray]:
    grouped: dict[str, list[int]] = {}
    for index, subject_id in enumerate(subject_ids.tolist()):
        grouped.setdefault(str(subject_id), []).append(index)
    true_rows = []
    prediction_rows = []
    for subject_id in sorted(grouped):
        indexes = grouped[subject_id]
        true_rows.append(np.mean(y_true[indexes], axis=0))
        prediction_rows.append(np.mean(y_pred[indexes], axis=0))
    return np.asarray(true_rows), np.asarray(prediction_rows)


def calculate_regression_metrics(
    y_true: np.ndarray,
    y_pred: np.ndarray,
    target_names: Iterable[str],
) -> dict[str, Any]:
    targets = tuple(target_names)
    actual = np.asarray(y_true, dtype=np.float64)
    predicted = np.asarray(y_pred, dtype=np.float64)
    if actual.shape != predicted.shape or actual.ndim != 2 or actual.shape[1] != len(targets):
        raise ModelingValidationError(
            "metric_shape_mismatch",
            "Bentuk target dan prediksi tidak cocok untuk evaluasi.",
            {"actual": list(actual.shape), "predicted": list(predicted.shape)},
        )
    residual = predicted - actual
    mae = np.mean(np.abs(residual), axis=0)
    rmse = np.sqrt(np.mean(np.square(residual), axis=0))
    bias = np.mean(residual, axis=0)
    return {
        "sample_count": int(actual.shape[0]),
        "macro_mae_cm": round(float(np.mean(mae)), 6),
        "macro_rmse_cm": round(float(np.mean(rmse)), 6),
        "macro_abs_bias_cm": round(float(np.mean(np.abs(bias))), 6),
        "per_target": {
            name: {
                "mae_cm": round(float(mae[index]), 6),
                "rmse_cm": round(float(rmse[index]), 6),
                "bias_cm": round(float(bias[index]), 6),
            }
            for index, name in enumerate(targets)
        },
    }


def evaluate_predictions(split: SplitData, y_pred: np.ndarray) -> dict[str, Any]:
    subject_true, subject_pred = _aggregate_subject_predictions(
        split.subject_ids,
        split.y,
        np.asarray(y_pred),
    )
    return {
        "row_level": calculate_regression_metrics(split.y, y_pred, MEASUREMENT_FIELDS),
        "subject_level": calculate_regression_metrics(
            subject_true,
            subject_pred,
            MEASUREMENT_FIELDS,
        ),
    }


def _model_definitions(random_seed: int) -> dict[str, tuple[Any, dict[str, Any]]]:
    return {
        "median": (MedianRegressor(), {"strategy": "per-target train median"}),
        "nearest_neighbor": (
            Pipeline(
                (
                    ("scale", StandardScaler()),
                    ("model", KNeighborsRegressor(n_neighbors=7, weights="distance", p=2, n_jobs=-1)),
                )
            ),
            {"n_neighbors": 7, "weights": "distance", "metric": "minkowski", "p": 2},
        ),
        "random_forest": (
            RandomForestRegressor(
                n_estimators=160,
                max_features=0.7,
                min_samples_leaf=2,
                random_state=random_seed,
                n_jobs=-1,
            ),
            {"n_estimators": 160, "max_features": 0.7, "min_samples_leaf": 2},
        ),
        "extra_trees": (
            ExtraTreesRegressor(
                n_estimators=200,
                max_features=0.8,
                min_samples_leaf=2,
                random_state=random_seed,
                n_jobs=-1,
            ),
            {"n_estimators": 200, "max_features": 0.8, "min_samples_leaf": 2},
        ),
        "hist_gradient_boosting": (
            MultiOutputRegressor(
                HistGradientBoostingRegressor(
                    learning_rate=0.06,
                    max_iter=160,
                    max_leaf_nodes=31,
                    l2_regularization=0.1,
                    random_state=random_seed,
                ),
                n_jobs=1,
            ),
            {
                "learning_rate": 0.06,
                "max_iter": 160,
                "max_leaf_nodes": 31,
                "l2_regularization": 0.1,
            },
        ),
        "mlp": (
            Pipeline(
                (
                    ("scale", StandardScaler()),
                    (
                        "model",
                        TransformedTargetRegressor(
                            regressor=MLPRegressor(
                                hidden_layer_sizes=(128, 64),
                                activation="relu",
                                learning_rate_init=0.001,
                                batch_size=128,
                                max_iter=400,
                                early_stopping=True,
                                validation_fraction=0.15,
                                n_iter_no_change=25,
                                random_state=random_seed,
                            ),
                            transformer=StandardScaler(),
                        ),
                    ),
                )
            ),
            {
                "hidden_layer_sizes": [128, 64],
                "max_iter": 400,
                "early_stopping": True,
                "validation_fraction": 0.15,
                "n_iter_no_change": 25,
            },
        ),
    }


def build_model(model_name: str, *, random_seed: int = RANDOM_SEED) -> tuple[Any, dict[str, Any]]:
    """Build one registered BodyM estimator through a stable public interface."""

    definitions = _model_definitions(random_seed)
    if model_name not in definitions:
        raise ModelingValidationError(
            "unknown_model",
            "Nama model tidak dikenal.",
            {"model": model_name},
        )
    return definitions[model_name]


def _write_metric_rows(report: dict[str, Any], path: Path) -> None:
    fieldnames = ("model", "group", "split", "level", "target", "mae_cm", "rmse_cm", "bias_cm")
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames, lineterminator="\n")
        writer.writeheader()
        for model_name, model_result in report["models"].items():
            for split_name, split_result in model_result["metrics"].items():
                for level, level_result in split_result.items():
                    for target, metrics in level_result["per_target"].items():
                        writer.writerow(
                            {
                                "model": model_name,
                                "group": model_result["group"],
                                "split": split_name,
                                "level": level,
                                "target": target,
                                **metrics,
                            }
                        )


def run_phase3_experiment(
    matrix_path: Path,
    manifest_path: Path,
    output_dir: Path,
    *,
    model_names: tuple[str, ...] = (*BASELINE_NAMES, *CANDIDATE_NAMES),
    random_seed: int = RANDOM_SEED,
    verify_matrix: bool = True,
    progress: Any | None = None,
) -> dict[str, Any]:
    """Train candidates, select on testA, and evaluate the winner once on testB."""

    unknown = sorted(set(model_names) - set((*BASELINE_NAMES, *CANDIDATE_NAMES)))
    if unknown:
        raise ModelingValidationError("unknown_model", "Nama model tidak dikenal.", {"models": unknown})
    if not set(BASELINE_NAMES).issubset(model_names):
        raise ModelingValidationError(
            "baseline_required",
            "Eksperimen wajib menyertakan median dan nearest-neighbor.",
        )
    candidate_names = tuple(name for name in model_names if name in CANDIDATE_NAMES)
    if not candidate_names:
        raise ModelingValidationError("candidate_required", "Eksperimen membutuhkan model kandidat.")

    dataset = load_modeling_dataset(
        matrix_path,
        manifest_path,
        verify_matrix=verify_matrix,
    )
    output_dir = Path(output_dir).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)
    fitted: dict[str, Any] = {}
    results: dict[str, Any] = {}

    for model_name in model_names:
        estimator, configuration = build_model(model_name, random_seed=random_seed)
        if progress:
            progress("fit_started", model_name, None)
        started = time.perf_counter()
        estimator.fit(dataset.train.X, dataset.train.y)
        fit_seconds = time.perf_counter() - started
        fitted[model_name] = estimator
        results[model_name] = {
            "group": "baseline" if model_name in BASELINE_NAMES else "candidate",
            "configuration": configuration,
            "fit_seconds": round(fit_seconds, 4),
            "metrics": {
                "train": evaluate_predictions(dataset.train, estimator.predict(dataset.train.X)),
                "validation": evaluate_predictions(
                    dataset.validation,
                    estimator.predict(dataset.validation.X),
                ),
            },
        }
        if progress:
            progress(
                "fit_completed",
                model_name,
                results[model_name]["metrics"]["validation"]["subject_level"]["macro_mae_cm"],
            )

    winner = min(
        candidate_names,
        key=lambda name: results[name]["metrics"]["validation"]["subject_level"]["macro_mae_cm"],
    )
    test_models = tuple(dict.fromkeys((*BASELINE_NAMES, winner)))
    for model_name in test_models:
        results[model_name]["metrics"]["test"] = evaluate_predictions(
            dataset.test,
            fitted[model_name].predict(dataset.test.X),
        )

    baseline_validation_mae = min(
        results[name]["metrics"]["validation"]["subject_level"]["macro_mae_cm"]
        for name in BASELINE_NAMES
    )
    winner_validation_mae = results[winner]["metrics"]["validation"]["subject_level"]["macro_mae_cm"]
    best_baseline_by_target = {
        target: min(
            results[name]["metrics"]["validation"]["subject_level"]["per_target"][target]["mae_cm"]
            for name in BASELINE_NAMES
        )
        for target in MEASUREMENT_FIELDS
    }
    targets_beating_baseline = [
        target
        for target in MEASUREMENT_FIELDS
        if results[winner]["metrics"]["validation"]["subject_level"]["per_target"][target]["mae_cm"]
        < best_baseline_by_target[target]
    ]

    report_path = output_dir / "phase-3-report.json"
    metrics_path = output_dir / "phase-3-metrics.csv"
    model_path = output_dir / "bodym-phase3-selected.joblib"
    bundle = {
        "model_version": MODEL_VERSION,
        "experiment_version": EXPERIMENT_VERSION,
        "contract_version": CONTRACT_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "matrix_sha256": dataset.matrix_sha256,
        "selected_model": winner,
        "feature_names": dataset.feature_names,
        "target_names": dataset.target_names,
        "estimator": fitted[winner],
    }
    temporary_model = model_path.with_suffix(model_path.suffix + ".part")
    joblib.dump(bundle, temporary_model, compress=3)
    os.replace(temporary_model, model_path)

    report = {
        "schema_version": 1,
        "experiment_version": EXPERIMENT_VERSION,
        "model_version": MODEL_VERSION,
        "random_seed": random_seed,
        "input": {
            "matrix_path": str(Path(matrix_path).resolve()),
            "manifest_path": str(Path(manifest_path).resolve()),
            "matrix_sha256": dataset.matrix_sha256,
            "feature_count": len(dataset.feature_names),
            "target_count": len(dataset.target_names),
        },
        "split_policy": {
            "training": "train",
            "selection": "testA",
            "final_test": "testB",
            "metric_unit": "subject",
            "selection_uses_testB": False,
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
            "cross_split_subjects_after_policy": list(dataset.cross_split_subjects),
            "exclusions": list(dataset.exclusions),
        },
        "selection": {
            "criterion": "lowest validation subject-level macro MAE",
            "selected_model": winner,
            "winner_validation_macro_mae_cm": winner_validation_mae,
            "best_baseline_validation_macro_mae_cm": baseline_validation_mae,
            "macro_mae_improvement_percent": round(
                ((baseline_validation_mae - winner_validation_mae) / baseline_validation_mae) * 100,
                6,
            ),
            "targets_beating_best_baseline": targets_beating_baseline,
            "target_win_count": len(targets_beating_baseline),
            "acceptance_passed": winner_validation_mae < baseline_validation_mae
            and len(targets_beating_baseline) >= math.ceil(len(MEASUREMENT_FIELDS) / 2),
        },
        "models": results,
        "runtime": {
            "python": platform.python_version(),
            "numpy": np.__version__,
            "scikit_learn": sklearn.__version__,
            "joblib": joblib.__version__,
        },
        "artifacts": {
            "selected_model": str(model_path),
            "selected_model_sha256": _sha256(model_path),
            "metrics_csv": str(metrics_path),
            "report_json": str(report_path),
        },
    }
    _write_metric_rows(report, metrics_path)
    report_path.write_text(json.dumps(report, indent=2, ensure_ascii=True) + "\n", encoding="utf-8")
    return report


def verify_phase3_artifacts(report_path: Path) -> dict[str, Any]:
    report_path = Path(report_path).resolve()
    report = json.loads(report_path.read_text(encoding="utf-8"))
    errors: list[str] = []
    model_path = Path(report.get("artifacts", {}).get("selected_model", ""))
    metrics_path = Path(report.get("artifacts", {}).get("metrics_csv", ""))
    if report.get("experiment_version") != EXPERIMENT_VERSION:
        errors.append("experiment_version_mismatch")
    if report.get("input", {}).get("feature_count") != len(feature_names()):
        errors.append("feature_count_mismatch")
    if report.get("input", {}).get("target_count") != len(MEASUREMENT_FIELDS):
        errors.append("target_count_mismatch")
    if report.get("split_policy", {}).get("cross_split_subjects_after_policy"):
        errors.append("cross_split_subject_leakage")
    if report.get("selection", {}).get("selected_model") not in CANDIDATE_NAMES:
        errors.append("invalid_selected_model")
    if not model_path.is_file():
        errors.append("selected_model_missing")
    elif _sha256(model_path) != report.get("artifacts", {}).get("selected_model_sha256"):
        errors.append("selected_model_sha256_mismatch")
    else:
        bundle = joblib.load(model_path)
        if tuple(bundle.get("feature_names", ())) != feature_names():
            errors.append("model_feature_order_mismatch")
        if tuple(bundle.get("target_names", ())) != MEASUREMENT_FIELDS:
            errors.append("model_target_order_mismatch")
        try:
            smoke_prediction = np.asarray(
                bundle["estimator"].predict(np.zeros((1, len(feature_names())), dtype=np.float32))
            )
        except Exception:
            errors.append("model_smoke_prediction_failed")
        else:
            if smoke_prediction.shape != (1, len(MEASUREMENT_FIELDS)):
                errors.append("model_prediction_shape_mismatch")
            elif not np.isfinite(smoke_prediction).all():
                errors.append("model_prediction_non_finite")
    if not metrics_path.is_file():
        errors.append("metrics_csv_missing")
    return {
        "report_path": str(report_path),
        "selected_model": report.get("selection", {}).get("selected_model"),
        "acceptance_passed": report.get("selection", {}).get("acceptance_passed"),
        "errors": errors,
    }
