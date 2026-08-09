"""Subject-safe silhouette retrieval and local residual correction."""

from __future__ import annotations

from typing import Any

import numpy as np
from sklearn.base import BaseEstimator, RegressorMixin, clone
from sklearn.decomposition import PCA
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import StandardScaler


class HybridEstimatorValidationError(ValueError):
    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


class SilhouetteRetrievalResidualRegressor(BaseEstimator, RegressorMixin):
    """Blend a base regressor with measurements and residuals from similar subjects."""

    def __init__(
        self,
        base_estimator: Any,
        *,
        base_is_prefit: bool = False,
        n_neighbors: int = 12,
        pca_components: int = 32,
        distance_power: float = 2.0,
        correction_grid: tuple[float, ...] = (0.25, 0.5, 0.75, 1.0),
        minimum_improvement_cm: float = 0.01,
        random_state: int = 20260805,
    ) -> None:
        self.base_estimator = base_estimator
        self.base_is_prefit = base_is_prefit
        self.n_neighbors = n_neighbors
        self.pca_components = pca_components
        self.distance_power = distance_power
        self.correction_grid = correction_grid
        self.minimum_improvement_cm = minimum_improvement_cm
        self.random_state = random_state

    @staticmethod
    def _validated_arrays(
        X: np.ndarray,
        y: np.ndarray | None = None,
    ) -> tuple[np.ndarray, np.ndarray | None]:
        features = np.asarray(X, dtype=np.float64)
        targets = None if y is None else np.asarray(y, dtype=np.float64)
        if features.ndim != 2 or features.shape[0] == 0 or not np.isfinite(features).all():
            raise HybridEstimatorValidationError(
                "invalid_features",
                "Fitur hybrid harus berupa matrix finite yang tidak kosong.",
            )
        if targets is not None:
            if (
                targets.ndim != 2
                or targets.shape[0] != features.shape[0]
                or not np.isfinite(targets).all()
            ):
                raise HybridEstimatorValidationError(
                    "invalid_targets",
                    "Target hybrid harus finite dan memiliki jumlah baris yang sama.",
                )
        return features, targets

    @staticmethod
    def _group_indexes(subject_ids: np.ndarray, row_count: int) -> dict[str, list[int]]:
        subjects = np.asarray(subject_ids, dtype=object).reshape(-1)
        if len(subjects) != row_count:
            raise HybridEstimatorValidationError(
                "subject_count_mismatch",
                "Jumlah subject ID tidak cocok dengan jumlah baris fitur.",
            )
        groups: dict[str, list[int]] = {}
        for index, subject_id in enumerate(subjects.tolist()):
            groups.setdefault(str(subject_id), []).append(index)
        return groups

    @staticmethod
    def _aggregate_rows(values: np.ndarray, groups: dict[str, list[int]]) -> np.ndarray:
        return np.asarray(
            [np.mean(values[indexes], axis=0) for _, indexes in sorted(groups.items())],
            dtype=np.float64,
        )

    def fit(
        self,
        X: np.ndarray,
        y: np.ndarray,
        *,
        subject_ids: np.ndarray,
    ) -> "SilhouetteRetrievalResidualRegressor":
        features, targets = self._validated_arrays(X, y)
        assert targets is not None
        groups = self._group_indexes(subject_ids, len(features))
        if len(groups) < 2:
            raise HybridEstimatorValidationError(
                "insufficient_reference_subjects",
                "Retrieval membutuhkan minimal dua subject training.",
            )
        if self.n_neighbors <= 0 or self.pca_components <= 0 or self.distance_power <= 0:
            raise HybridEstimatorValidationError(
                "invalid_hybrid_configuration",
                "Konfigurasi neighbor, PCA, dan bobot jarak harus positif.",
            )

        if self.base_is_prefit:
            self.base_estimator_ = self.base_estimator
        else:
            self.base_estimator_ = clone(self.base_estimator)
            self.base_estimator_.fit(features, targets)
        base_predictions = np.asarray(self.base_estimator_.predict(features), dtype=np.float64)
        if base_predictions.shape != targets.shape or not np.isfinite(base_predictions).all():
            raise HybridEstimatorValidationError(
                "invalid_base_prediction",
                "Model dasar menghasilkan prediksi training yang tidak valid.",
            )

        reference_features = self._aggregate_rows(features, groups)
        self.reference_targets_ = self._aggregate_rows(targets, groups)
        self.reference_residuals_ = self._aggregate_rows(targets - base_predictions, groups)
        self.reference_subject_count_ = len(groups)
        self.feature_count_ = features.shape[1]
        self.target_count_ = targets.shape[1]
        self.neighbors_used_ = min(int(self.n_neighbors), self.reference_subject_count_)

        self.retrieval_scaler_ = StandardScaler().fit(reference_features)
        scaled = self.retrieval_scaler_.transform(reference_features)
        component_count = max(
            1,
            min(int(self.pca_components), scaled.shape[1], scaled.shape[0] - 1),
        )
        self.retrieval_projector_ = PCA(
            n_components=component_count,
            whiten=True,
            random_state=self.random_state,
        ).fit(scaled)
        embedded = self.retrieval_projector_.transform(scaled)
        self.retrieval_neighbors_ = NearestNeighbors(
            n_neighbors=self.neighbors_used_,
            metric="euclidean",
        ).fit(embedded)
        self.correction_modes_ = np.full(self.target_count_, "base", dtype=object)
        self.correction_strengths_ = np.zeros(self.target_count_, dtype=np.float64)
        self.is_calibrated_ = False
        return self

    def _check_fitted(self) -> None:
        if not hasattr(self, "retrieval_neighbors_"):
            raise HybridEstimatorValidationError(
                "model_not_fitted",
                "Estimator hybrid belum dilatih.",
            )

    def _retrieval_components(
        self,
        X: np.ndarray,
    ) -> tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
        self._check_fitted()
        features, _ = self._validated_arrays(X)
        if features.shape[1] != self.feature_count_:
            raise HybridEstimatorValidationError(
                "feature_count_mismatch",
                "Jumlah fitur prediksi hybrid tidak cocok dengan training.",
                {"expected": self.feature_count_, "actual": features.shape[1]},
            )
        base = np.asarray(self.base_estimator_.predict(features), dtype=np.float64)
        embedded = self.retrieval_projector_.transform(
            self.retrieval_scaler_.transform(features)
        )
        distances, indexes = self.retrieval_neighbors_.kneighbors(
            embedded,
            return_distance=True,
        )
        safe_distances = np.maximum(distances, 1e-8)
        weights = np.power(safe_distances, -float(self.distance_power))
        exact = distances <= 1e-8
        exact_rows = np.any(exact, axis=1)
        if np.any(exact_rows):
            weights[exact_rows] = exact[exact_rows].astype(np.float64)
        weights /= np.sum(weights, axis=1, keepdims=True)
        retrieval = np.einsum("nk,nkt->nt", weights, self.reference_targets_[indexes])
        local_residual = np.einsum(
            "nk,nkt->nt",
            weights,
            self.reference_residuals_[indexes],
        )
        return base, retrieval, local_residual, distances, weights

    def calibrate(
        self,
        X: np.ndarray,
        y: np.ndarray,
        *,
        subject_ids: np.ndarray,
    ) -> "SilhouetteRetrievalResidualRegressor":
        features, targets = self._validated_arrays(X, y)
        assert targets is not None
        if targets.shape[1] != self.target_count_:
            raise HybridEstimatorValidationError(
                "target_count_mismatch",
                "Jumlah target kalibrasi hybrid tidak cocok dengan training.",
            )
        groups = self._group_indexes(subject_ids, len(features))
        base, retrieval, local_residual, _, _ = self._retrieval_components(features)
        subject_targets = self._aggregate_rows(targets, groups)
        subject_base = self._aggregate_rows(base, groups)
        subject_retrieval = self._aggregate_rows(retrieval, groups)
        subject_residual = self._aggregate_rows(local_residual, groups)

        modes = np.full(self.target_count_, "base", dtype=object)
        strengths = np.zeros(self.target_count_, dtype=np.float64)
        for target_index in range(self.target_count_):
            actual = subject_targets[:, target_index]
            base_values = subject_base[:, target_index]
            best_error = float(np.mean(np.abs(base_values - actual)))
            for mode, signal in (
                ("local_residual", subject_residual[:, target_index]),
                ("retrieval_blend", subject_retrieval[:, target_index] - base_values),
            ):
                for strength in self.correction_grid:
                    candidate = base_values + float(strength) * signal
                    error = float(np.mean(np.abs(candidate - actual)))
                    if error < best_error - float(self.minimum_improvement_cm):
                        best_error = error
                        modes[target_index] = mode
                        strengths[target_index] = float(strength)
        self.correction_modes_ = modes
        self.correction_strengths_ = strengths
        self.is_calibrated_ = True
        return self

    def _apply_correction(
        self,
        base: np.ndarray,
        retrieval: np.ndarray,
        local_residual: np.ndarray,
    ) -> np.ndarray:
        prediction = np.array(base, copy=True)
        for target_index, mode in enumerate(self.correction_modes_.tolist()):
            strength = float(self.correction_strengths_[target_index])
            if mode == "local_residual":
                prediction[:, target_index] += strength * local_residual[:, target_index]
            elif mode == "retrieval_blend":
                prediction[:, target_index] += strength * (
                    retrieval[:, target_index] - base[:, target_index]
                )
        return prediction

    def predict(self, X: np.ndarray) -> np.ndarray:
        base, retrieval, local_residual, _, _ = self._retrieval_components(X)
        return self._apply_correction(base, retrieval, local_residual)

    def predict_with_diagnostics(
        self,
        X: np.ndarray,
    ) -> tuple[np.ndarray, list[dict[str, Any]]]:
        base, retrieval, local_residual, distances, _ = self._retrieval_components(X)
        prediction = self._apply_correction(base, retrieval, local_residual)
        correction = prediction - base
        rows = []
        for index in range(len(prediction)):
            rows.append(
                {
                    "method": "subject-centroid-retrieval+calibrated-residual",
                    "neighbors_used": self.neighbors_used_,
                    "reference_subject_count": self.reference_subject_count_,
                    "distance": {
                        "minimum": round(float(np.min(distances[index])), 6),
                        "mean": round(float(np.mean(distances[index])), 6),
                        "maximum": round(float(np.max(distances[index])), 6),
                    },
                    "correction_modes": self.correction_modes_.tolist(),
                    "correction_strengths": [
                        round(float(value), 4) for value in self.correction_strengths_
                    ],
                    "base_predictions_cm": [round(float(value), 6) for value in base[index]],
                    "retrieval_predictions_cm": [
                        round(float(value), 6) for value in retrieval[index]
                    ],
                    "corrections_cm": [
                        round(float(value), 6) for value in correction[index]
                    ],
                }
            )
        return prediction, rows

    def correction_summary(self, target_names: tuple[str, ...]) -> dict[str, Any]:
        self._check_fitted()
        if len(target_names) != self.target_count_:
            raise HybridEstimatorValidationError(
                "target_name_count_mismatch",
                "Jumlah nama target tidak cocok untuk ringkasan hybrid.",
            )
        return {
            "method": "subject-centroid-retrieval+calibrated-residual",
            "reference_subject_count": self.reference_subject_count_,
            "neighbors": self.neighbors_used_,
            "pca_components": self.retrieval_projector_.n_components_,
            "per_target": {
                name: {
                    "mode": str(self.correction_modes_[index]),
                    "strength": round(float(self.correction_strengths_[index]), 4),
                }
                for index, name in enumerate(target_names)
            },
        }
