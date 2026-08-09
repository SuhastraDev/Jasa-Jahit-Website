"""Versioned BodyM v1 model loading and silhouette inference."""

from __future__ import annotations

import os
from pathlib import Path
import threading
from typing import Any

import joblib
import numpy as np

from bodym_contract import CONTRACT_VERSION, MEASUREMENT_FIELDS, MEASUREMENT_METHOD
from bodym_finalization import FINAL_MODEL_VERSION, predict_with_guardrails
from bodym_preprocessing import (
    PREPROCESSING_VERSION,
    SilhouetteValidationError,
    extract_pair_features,
    feature_names,
    preprocess_silhouette,
)


class BodyMInferenceError(RuntimeError):
    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


def resolve_model_path(value: str | Path | None = None) -> Path:
    configured = value or os.getenv("BODYM_MODEL_PATH") or "models/bodym-v1.joblib"
    path = Path(configured)
    if not path.is_absolute():
        path = Path(__file__).resolve().parent / path
    return path.resolve()


def _preprocess_view(
    mask: np.ndarray,
    *,
    view: str,
    pixels_per_cm: float,
):
    if not np.isfinite(pixels_per_cm) or pixels_per_cm <= 0:
        raise BodyMInferenceError(
            "invalid_reference_scale",
            f"Skala benda patokan pada foto {view} tidak valid.",
            {"failed_view": view, "pixels_per_cm": pixels_per_cm},
        )
    try:
        return preprocess_silhouette(mask, view=view, cm_per_pixel=1.0 / pixels_per_cm)
    except SilhouetteValidationError as exc:
        details = {**exc.details, "failed_view": view}
        raise BodyMInferenceError(exc.code, str(exc), details) from exc


def build_bodym_features(
    front_mask: np.ndarray,
    side_mask: np.ndarray,
    *,
    front_pixels_per_cm: float,
    side_pixels_per_cm: float,
) -> dict[str, Any]:
    """Convert A4/KTP scale and front/side masks into the frozen 224 features."""

    front = _preprocess_view(
        front_mask,
        view="front",
        pixels_per_cm=front_pixels_per_cm,
    )
    side = _preprocess_view(
        side_mask,
        view="side",
        pixels_per_cm=side_pixels_per_cm,
    )
    vector = extract_pair_features(front, side)
    return {
        "values": np.asarray(vector.values, dtype=np.float32).reshape(1, -1),
        "feature_names": vector.names,
        "views": {
            "front": {
                "body_bbox": list(front.body_bbox),
                "body_height_cm": front.body_height_cm,
                "cm_per_pixel": front.cm_per_pixel,
                "diagnostics": front.diagnostics,
            },
            "side": {
                "body_bbox": list(side.body_bbox),
                "body_height_cm": side.body_height_cm,
                "cm_per_pixel": side.cm_per_pixel,
                "diagnostics": side.diagnostics,
            },
        },
    }


class BodyMModelService:
    """Thread-safe lazy loader for a frozen, version-checked BodyM bundle."""

    def __init__(self, model_path: str | Path | None = None) -> None:
        self.model_path = resolve_model_path(model_path)
        self._bundle: dict[str, Any] | None = None
        self._load_error: str | None = None
        self._lock = threading.Lock()

    def _load(self) -> dict[str, Any]:
        if self._bundle is not None:
            return self._bundle
        with self._lock:
            if self._bundle is not None:
                return self._bundle
            if not self.model_path.is_file():
                self._load_error = "model_file_missing"
                raise BodyMInferenceError(
                    "model_file_missing",
                    "Model estimasi ukuran belum tersedia.",
                    {"model_path": str(self.model_path)},
                )
            try:
                bundle = joblib.load(self.model_path)
            except Exception as exc:
                self._load_error = "model_load_failed"
                raise BodyMInferenceError(
                    "model_load_failed",
                    "Model estimasi ukuran gagal dimuat.",
                ) from exc

            checks = {
                "model_version": (bundle.get("model_version"), FINAL_MODEL_VERSION),
                "contract_version": (bundle.get("contract_version"), CONTRACT_VERSION),
                "preprocessing_version": (
                    bundle.get("preprocessing_version"),
                    PREPROCESSING_VERSION,
                ),
                "feature_names": (tuple(bundle.get("feature_names", ())), feature_names()),
                "target_names": (tuple(bundle.get("target_names", ())), MEASUREMENT_FIELDS),
            }
            mismatches = {
                name: {"actual": actual, "expected": expected}
                for name, (actual, expected) in checks.items()
                if actual != expected
            }
            if mismatches:
                self._load_error = "model_contract_mismatch"
                raise BodyMInferenceError(
                    "model_contract_mismatch",
                    "Versi atau urutan fitur model estimasi ukuran tidak cocok.",
                    {"mismatches": mismatches},
                )
            self._bundle = bundle
            self._load_error = None
            return bundle

    def status(self, *, load: bool = False) -> dict[str, Any]:
        if load and self._bundle is None:
            try:
                self._load()
            except BodyMInferenceError:
                pass
        bundle = self._bundle
        return {
            "enabled": os.getenv("BODYM_ENABLED", "false").lower() in ("1", "true", "yes", "on"),
            "loaded": bundle is not None,
            "available": self.model_path.is_file(),
            "model_path": str(self.model_path),
            "model_version": bundle.get("model_version") if bundle else None,
            "contract_version": bundle.get("contract_version") if bundle else CONTRACT_VERSION,
            "preprocessing_version": bundle.get("preprocessing_version") if bundle else PREPROCESSING_VERSION,
            "feature_count": len(bundle.get("feature_names", ())) if bundle else len(feature_names()),
            "target_count": len(bundle.get("target_names", ())) if bundle else len(MEASUREMENT_FIELDS),
            "load_error": self._load_error,
        }

    def predict_features(self, values: np.ndarray, *, coverage: float = 0.90) -> dict[str, Any]:
        bundle = self._load()
        features = np.asarray(values, dtype=np.float32)
        if features.ndim == 1:
            features = features.reshape(1, -1)
        prediction = predict_with_guardrails(bundle, features, coverage=coverage)
        if len(prediction["rows"]) != 1:
            raise BodyMInferenceError("batch_not_supported", "Inference foto hanya menerima satu subject.")
        row = prediction["rows"][0]
        result = {
            "model_version": bundle["model_version"],
            "contract_version": bundle["contract_version"],
            "preprocessing_version": bundle["preprocessing_version"],
            "measurement_method": MEASUREMENT_METHOD,
            "status": row["status"],
            "diagnostic_codes": row["diagnostic_codes"],
            "implausible_fields": row["implausible_fields"],
            "ood": row["ood"],
            "predictions_cm": row["predictions_cm"],
            "prediction_intervals_cm": row["prediction_intervals_cm"],
            "per_field_confidence": row["confidence"],
            "confidence_definition": prediction["confidence_definition"],
            "coverage": prediction["coverage"],
            "silent_clipping": prediction["silent_clipping"],
        }
        if "retrieval" in row:
            result["retrieval"] = row["retrieval"]
        return result

    def predict_masks(
        self,
        front_mask: np.ndarray,
        side_mask: np.ndarray,
        *,
        front_pixels_per_cm: float,
        side_pixels_per_cm: float,
        coverage: float = 0.90,
    ) -> dict[str, Any]:
        features = build_bodym_features(
            front_mask,
            side_mask,
            front_pixels_per_cm=front_pixels_per_cm,
            side_pixels_per_cm=side_pixels_per_cm,
        )
        result = self.predict_features(features["values"], coverage=coverage)
        result["feature_count"] = len(features["feature_names"])
        result["views"] = features["views"]
        return result


_DEFAULT_SERVICE: BodyMModelService | None = None
_DEFAULT_SERVICE_LOCK = threading.Lock()


def get_bodym_service() -> BodyMModelService:
    global _DEFAULT_SERVICE
    if _DEFAULT_SERVICE is None:
        with _DEFAULT_SERVICE_LOCK:
            if _DEFAULT_SERVICE is None:
                _DEFAULT_SERVICE = BodyMModelService()
    return _DEFAULT_SERVICE
