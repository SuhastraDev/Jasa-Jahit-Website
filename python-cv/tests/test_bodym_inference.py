from __future__ import annotations

from pathlib import Path
import sys
import tempfile
import unittest

import joblib
import numpy as np


PYTHON_CV_ROOT = Path(__file__).resolve().parents[1]
if str(PYTHON_CV_ROOT) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV_ROOT))

from bodym_contract import MEASUREMENT_FIELDS
from bodym_preprocessing import feature_names


class ConstantEstimator:
    def predict(self, X: np.ndarray) -> np.ndarray:
        values = np.arange(1, len(MEASUREMENT_FIELDS) + 1, dtype=np.float64)
        return np.tile(values, (len(X), 1))


class DiagnosticConstantEstimator(ConstantEstimator):
    def predict_with_diagnostics(self, X: np.ndarray):
        predictions = self.predict(X)
        return predictions, [
            {
                "method": "test-retrieval",
                "neighbors_used": 3,
                "base_predictions_cm": row.tolist(),
                "retrieval_predictions_cm": row.tolist(),
                "corrections_cm": np.zeros_like(row).tolist(),
                "correction_modes": ["base"] * len(row),
                "correction_strengths": [0.0] * len(row),
            }
            for row in predictions
        ]


class PassthroughTransformer:
    def transform(self, X: np.ndarray) -> np.ndarray:
        return np.asarray(X, dtype=np.float64)


class ZeroDistanceNeighbors:
    def kneighbors(self, X: np.ndarray, return_distance: bool = True):
        distances = np.zeros((len(X), 1), dtype=np.float64)
        indexes = np.zeros_like(distances, dtype=int)
        return (distances, indexes) if return_distance else indexes


def write_bundle(path: Path, estimator=None) -> None:
    calibration = {
        key: {
            "nominal_coverage": coverage,
            "error_band_cm": {name: 2.0 for name in MEASUREMENT_FIELDS},
            "empirical_coverage": {name: coverage for name in MEASUREMENT_FIELDS},
        }
        for key, coverage in (("0.80", 0.80), ("0.90", 0.90), ("0.95", 0.95))
    }
    joblib.dump(
        {
            "model_version": "bodym-v1",
            "finalization_version": "bodym-finalization.v1",
            "contract_version": "bodym.v1",
            "preprocessing_version": "bodym-preprocess.v1",
            "matrix_sha256": "test-matrix",
            "selected_model": "mlp",
            "random_seed": 7,
            "feature_names": feature_names(),
            "target_names": MEASUREMENT_FIELDS,
            "estimator": estimator or ConstantEstimator(),
            "diagnostics": {
                "calibration_subject_count": 10,
                "calibration": calibration,
                "ood": {
                    "warning_threshold": 1.0,
                    "rejection_threshold": 2.0,
                    "validation_distances_sorted": np.asarray([0.0, 1.0]),
                    "scaler": PassthroughTransformer(),
                    "projector": PassthroughTransformer(),
                    "nearest_neighbors": ZeroDistanceNeighbors(),
                },
                "plausibility": {
                    "lower_cm": {name: -100.0 for name in MEASUREMENT_FIELDS},
                    "upper_cm": {name: 300.0 for name in MEASUREMENT_FIELDS},
                    "silent_clipping": False,
                },
            },
        },
        path,
    )


class BodyMInferenceTest(unittest.TestCase):
    def test_loader_reports_version_and_predicts_frozen_feature_order(self) -> None:
        from bodym_inference import BodyMModelService

        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "bodym-v1.joblib"
            write_bundle(model_path)
            service = BodyMModelService(model_path)

            status = service.status(load=True)
            result = service.predict_features(np.zeros(len(feature_names())))

        self.assertTrue(status["loaded"])
        self.assertEqual(status["model_version"], "bodym-v1")
        self.assertEqual(tuple(result["predictions_cm"]), MEASUREMENT_FIELDS)
        self.assertEqual(result["predictions_cm"]["ankle_girth"], 1.0)
        self.assertEqual(result["measurement_method"], "bodym_ml")

    def test_reference_scale_and_silhouettes_create_224_model_features(self) -> None:
        from bodym_inference import build_bodym_features

        front = np.zeros((120, 100), dtype=np.uint8)
        side = np.zeros((120, 100), dtype=np.uint8)
        front[10:110, 30:70] = 255
        side[10:110, 40:60] = 255

        result = build_bodym_features(
            front,
            side,
            front_pixels_per_cm=0.625,
            side_pixels_per_cm=0.625,
        )

        self.assertEqual(result["values"].shape, (1, 224))
        self.assertEqual(result["feature_names"], feature_names())
        self.assertAlmostEqual(result["views"]["front"]["body_height_cm"], 160.0)
        self.assertAlmostEqual(result["views"]["side"]["body_height_cm"], 160.0)

    def test_service_preserves_retrieval_diagnostics(self) -> None:
        from bodym_inference import BodyMModelService

        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "bodym-v1.joblib"
            write_bundle(model_path, DiagnosticConstantEstimator())
            result = BodyMModelService(model_path).predict_features(
                np.zeros(len(feature_names()))
            )

        self.assertEqual(result["retrieval"]["method"], "test-retrieval")
        self.assertEqual(result["retrieval"]["neighbors_used"], 3)

    def test_committed_artifact_loads_hybrid_estimator(self) -> None:
        from bodym_inference import BodyMModelService

        model_path = PYTHON_CV_ROOT / "models" / "bodym-v1.joblib"
        result = BodyMModelService(model_path).predict_features(
            np.zeros(len(feature_names()), dtype=np.float32)
        )

        self.assertEqual(
            result["retrieval"]["method"],
            "subject-centroid-retrieval+calibrated-residual",
        )
        self.assertEqual(result["retrieval"]["reference_subject_count"], 2018)
        self.assertEqual(result["confidence_definition"], "empirical validation coverage")


if __name__ == "__main__":
    unittest.main()
