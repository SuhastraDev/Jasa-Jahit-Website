from __future__ import annotations

from pathlib import Path
import sys
import unittest

import numpy as np


PYTHON_CV_ROOT = Path(__file__).resolve().parents[1]
if str(PYTHON_CV_ROOT) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV_ROOT))


class FirstFeaturesEstimator:
    def predict(self, X: np.ndarray) -> np.ndarray:
        values = np.asarray(X, dtype=np.float64)
        return values[:, :2]


class DiagnosticFirstFeaturesEstimator(FirstFeaturesEstimator):
    def predict_with_diagnostics(self, X: np.ndarray):
        predictions = self.predict(X)
        diagnostics = [
            {
                "method": "test-retrieval",
                "neighbors_used": 2,
                "base_predictions_cm": [float(value) for value in row],
                "retrieval_predictions_cm": [float(value + 1.0) for value in row],
                "corrections_cm": [0.0 for _ in row],
                "correction_modes": ["base" for _ in row],
                "correction_strengths": [0.0 for _ in row],
            }
            for row in predictions
        ]
        return predictions, diagnostics


class BodyMCalibrationTest(unittest.TestCase):
    def test_conformal_quantile_uses_finite_sample_higher_rule(self) -> None:
        from bodym_finalization import conformal_quantile

        residuals = np.asarray([1.0, 2.0, 3.0, 4.0])

        self.assertEqual(conformal_quantile(residuals, 0.80), 4.0)
        self.assertEqual(conformal_quantile(residuals, 0.50), 3.0)

    def test_calibration_records_real_error_bands_and_empirical_coverage(self) -> None:
        from bodym_finalization import fit_diagnostics

        rng = np.random.default_rng(7)
        train_X = rng.normal(size=(60, 4))
        train_y = train_X[:, :2]
        validation_X = rng.normal(size=(20, 4))
        validation_y = validation_X[:, :2] + np.asarray([1.0, -2.0])

        diagnostics = fit_diagnostics(
            FirstFeaturesEstimator(),
            train_X,
            train_y,
            validation_X,
            validation_y,
            np.asarray([f"subject-{index}" for index in range(20)]),
            ("first", "second"),
            coverages=(0.90,),
        )

        calibration = diagnostics["calibration"]["0.90"]
        self.assertEqual(calibration["error_band_cm"], {"first": 1.0, "second": 2.0})
        self.assertEqual(calibration["empirical_coverage"], {"first": 0.95, "second": 1.0})
        self.assertEqual(diagnostics["calibration_subject_count"], 20)

    def test_prediction_rejects_outlier_without_silently_clipping_values(self) -> None:
        from bodym_finalization import fit_diagnostics, predict_with_guardrails

        rng = np.random.default_rng(11)
        train_X = rng.normal(size=(80, 4))
        train_y = train_X[:, :2]
        validation_X = rng.normal(size=(30, 4))
        validation_y = validation_X[:, :2] + 0.5
        diagnostics = fit_diagnostics(
            FirstFeaturesEstimator(),
            train_X,
            train_y,
            validation_X,
            validation_y,
            np.asarray([f"subject-{index}" for index in range(30)]),
            ("first", "second"),
        )
        bundle = {
            "estimator": FirstFeaturesEstimator(),
            "feature_names": ("a", "b", "c", "d"),
            "target_names": ("first", "second"),
            "diagnostics": diagnostics,
        }

        result = predict_with_guardrails(bundle, np.full((1, 4), 100.0), coverage=0.90)

        self.assertEqual(result["rows"][0]["predictions_cm"], {"first": 100.0, "second": 100.0})
        self.assertEqual(result["rows"][0]["status"], "rejected")
        self.assertIn("out_of_distribution", result["rows"][0]["diagnostic_codes"])
        self.assertIn("implausible_prediction", result["rows"][0]["diagnostic_codes"])
        self.assertFalse(result["silent_clipping"])

    def test_prediction_maps_retrieval_diagnostics_to_measurement_names(self) -> None:
        from bodym_finalization import fit_diagnostics, predict_with_guardrails

        rng = np.random.default_rng(17)
        train_X = rng.normal(size=(80, 4))
        train_y = train_X[:, :2]
        validation_X = rng.normal(size=(30, 4))
        validation_y = validation_X[:, :2]
        estimator = DiagnosticFirstFeaturesEstimator()
        diagnostics = fit_diagnostics(
            estimator,
            train_X,
            train_y,
            validation_X,
            validation_y,
            np.asarray([f"subject-{index}" for index in range(30)]),
            ("first", "second"),
        )
        bundle = {
            "estimator": estimator,
            "feature_names": ("a", "b", "c", "d"),
            "target_names": ("first", "second"),
            "diagnostics": diagnostics,
        }

        result = predict_with_guardrails(bundle, np.zeros((1, 4)), coverage=0.90)

        retrieval = result["rows"][0]["retrieval"]
        self.assertEqual(retrieval["method"], "test-retrieval")
        self.assertEqual(retrieval["base_predictions_cm"], {"first": 0.0, "second": 0.0})
        self.assertEqual(retrieval["correction_modes"], {"first": "base", "second": "base"})


if __name__ == "__main__":
    unittest.main()
