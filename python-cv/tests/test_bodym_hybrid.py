from __future__ import annotations

from pathlib import Path
import sys
import unittest

import numpy as np
from sklearn.base import BaseEstimator, RegressorMixin


PYTHON_CV_ROOT = Path(__file__).resolve().parents[1]
if str(PYTHON_CV_ROOT) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV_ROOT))


class OffsetEstimator(BaseEstimator, RegressorMixin):
    def __init__(self, offset: float = 0.0) -> None:
        self.offset = offset

    def fit(self, X: np.ndarray, y: np.ndarray) -> "OffsetEstimator":
        self.target_count_ = np.asarray(y).shape[1]
        return self

    def predict(self, X: np.ndarray) -> np.ndarray:
        values = np.asarray(X, dtype=np.float64)
        base = values[:, :1] * 10.0 + self.offset
        return np.repeat(base, self.target_count_, axis=1)


class PrefitOffsetEstimator(OffsetEstimator):
    def __init__(self, offset: float = 0.0, target_count: int = 2) -> None:
        super().__init__(offset=offset)
        self.target_count = target_count
        self.target_count_ = target_count
        self.fit_calls = 0

    def fit(self, X: np.ndarray, y: np.ndarray) -> "PrefitOffsetEstimator":
        self.fit_calls += 1
        raise AssertionError("Estimator prefit tidak boleh dilatih ulang.")


class SilhouetteRetrievalResidualRegressorTest(unittest.TestCase):
    def test_local_residual_corrects_a_systematic_base_bias(self) -> None:
        from bodym_hybrid import SilhouetteRetrievalResidualRegressor

        train_X = np.asarray([[1.0, 0.0], [1.1, 0.0], [2.0, 0.0], [2.1, 0.0]])
        train_y = np.repeat(train_X[:, :1] * 10.0, 2, axis=1)
        subjects = np.asarray(["a", "a", "b", "b"], dtype=object)
        validation_X = np.asarray([[1.5, 0.0], [2.5, 0.0]])
        validation_y = np.repeat(validation_X[:, :1] * 10.0, 2, axis=1)

        estimator = SilhouetteRetrievalResidualRegressor(
            OffsetEstimator(offset=-2.0),
            n_neighbors=2,
            pca_components=1,
        )
        estimator.fit(train_X, train_y, subject_ids=subjects)
        estimator.calibrate(
            validation_X,
            validation_y,
            subject_ids=np.asarray(["v1", "v2"], dtype=object),
        )

        prediction, diagnostics = estimator.predict_with_diagnostics(np.asarray([[1.8, 0.0]]))

        np.testing.assert_allclose(prediction, [[18.0, 18.0]], atol=1e-6)
        self.assertEqual(estimator.reference_subject_count_, 2)
        self.assertEqual(diagnostics[0]["neighbors_used"], 2)
        self.assertNotIn("subject_id", diagnostics[0])
        self.assertEqual(diagnostics[0]["correction_modes"], ["local_residual", "local_residual"])

    def test_calibration_keeps_base_prediction_when_correction_does_not_help(self) -> None:
        from bodym_hybrid import SilhouetteRetrievalResidualRegressor

        train_X = np.asarray([[1.0, 0.0], [2.0, 0.0], [3.0, 0.0]])
        train_y = np.repeat(train_X[:, :1] * 10.0, 2, axis=1)
        subjects = np.asarray(["a", "b", "c"], dtype=object)

        estimator = SilhouetteRetrievalResidualRegressor(
            OffsetEstimator(offset=0.0),
            n_neighbors=2,
            pca_components=1,
        )
        estimator.fit(train_X, train_y, subject_ids=subjects)
        estimator.calibrate(train_X, train_y, subject_ids=subjects)

        prediction, diagnostics = estimator.predict_with_diagnostics(np.asarray([[2.5, 0.0]]))

        np.testing.assert_allclose(prediction, [[25.0, 25.0]], atol=1e-6)
        self.assertEqual(diagnostics[0]["correction_modes"], ["base", "base"])
        self.assertEqual(diagnostics[0]["corrections_cm"], [0.0, 0.0])

    def test_prefit_base_is_preserved_without_refitting(self) -> None:
        from bodym_hybrid import SilhouetteRetrievalResidualRegressor

        train_X = np.asarray([[1.0, 0.0], [2.0, 0.0], [3.0, 0.0]])
        train_y = np.repeat(train_X[:, :1] * 10.0, 2, axis=1)
        subjects = np.asarray(["a", "b", "c"], dtype=object)
        base = PrefitOffsetEstimator(offset=0.0, target_count=2)

        estimator = SilhouetteRetrievalResidualRegressor(
            base,
            base_is_prefit=True,
            n_neighbors=2,
            pca_components=1,
        )
        estimator.fit(train_X, train_y, subject_ids=subjects)

        np.testing.assert_allclose(estimator.predict(train_X), train_y, atol=1e-6)
        self.assertIs(estimator.base_estimator_, base)
        self.assertEqual(base.fit_calls, 0)


if __name__ == "__main__":
    unittest.main()
