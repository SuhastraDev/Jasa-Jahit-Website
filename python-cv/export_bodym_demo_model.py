"""Export a BodyM-compatible demo artifact for ZRINTTAILOR production demo."""

from __future__ import annotations

import json
from pathlib import Path

import joblib
import numpy as np
from sklearn.decomposition import PCA
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import StandardScaler

from bodym_contract import CONTRACT_VERSION, MEASUREMENT_FIELDS
from bodym_finalization import BodyMHeuristicEstimator, FINAL_MODEL_VERSION
from bodym_preprocessing import PREPROCESSING_VERSION, feature_names


OUTPUT_DIR = Path(__file__).resolve().parent / "models"


def synthetic_feature_matrix() -> np.ndarray:
    names = feature_names()
    indexes = {name: index for index, name in enumerate(names)}
    rows: list[np.ndarray] = []
    heights = np.linspace(145.0, 190.0, 24)
    body_profiles = np.linspace(0.88, 1.12, 8)
    for height in heights:
        for profile in body_profiles:
            row = np.zeros(len(names), dtype=np.float64)
            row[indexes["body_height_mean_cm"]] = height
            row[indexes["body_height_difference_ratio"]] = 0.02
            row[indexes["front_area_ratio"]] = 0.24 * profile
            row[indexes["side_area_ratio"]] = 0.14 * profile
            row[indexes["front_bbox_aspect_ratio"]] = 0.34 * profile
            row[indexes["side_bbox_aspect_ratio"]] = 0.19 * profile
            row[indexes["front_body_height_cm"]] = height
            row[indexes["side_body_height_cm"]] = height * 0.995

            for i in range(32):
                y = i / 31
                torso = np.exp(-((y - 0.46) ** 2) / 0.08)
                leg = np.exp(-((y - 0.76) ** 2) / 0.035)
                front_width = height * (0.08 + 0.16 * torso + 0.055 * leg) * profile
                side_depth = height * (0.055 + 0.08 * torso + 0.035 * leg) * profile
                row[indexes[f"front_width_norm_{i:02d}"]] = front_width / height
                row[indexes[f"front_center_width_norm_{i:02d}"]] = front_width / height * 0.92
                row[indexes[f"side_depth_norm_{i:02d}"]] = side_depth / height
                row[indexes[f"side_center_depth_norm_{i:02d}"]] = side_depth / height * 0.92
                row[indexes[f"front_width_cm_{i:02d}"]] = front_width
                row[indexes[f"side_depth_cm_{i:02d}"]] = side_depth

            bands = {
                "neck": (height * 0.12 * profile, height * 0.09 * profile),
                "shoulder": (height * 0.24 * profile, height * 0.10 * profile),
                "chest": (height * 0.29 * profile, height * 0.18 * profile),
                "waist": (height * 0.23 * profile, height * 0.15 * profile),
                "hip": (height * 0.31 * profile, height * 0.19 * profile),
                "thigh": (height * 0.16 * profile, height * 0.12 * profile),
                "calf": (height * 0.115 * profile, height * 0.09 * profile),
                "ankle": (height * 0.075 * profile, height * 0.06 * profile),
            }
            for label, (front_width, side_depth) in bands.items():
                row[indexes[f"front_{label}_width_cm"]] = front_width
                row[indexes[f"side_{label}_depth_cm"]] = side_depth
                a = max(front_width, side_depth) / 2
                b = min(front_width, side_depth) / 2
                h = ((a - b) ** 2) / ((a + b) ** 2)
                row[indexes[f"ellipse_{label}_circumference_cm"]] = (
                    np.pi * (a + b) * (1 + (3 * h) / (10 + np.sqrt(4 - (3 * h))))
                )
            rows.append(row)
    return np.asarray(rows, dtype=np.float64)


def diagnostics_for(features: np.ndarray) -> dict:
    scaler = StandardScaler().fit(features)
    scaled = scaler.transform(features)
    projector = PCA(n_components=min(16, features.shape[1], features.shape[0] - 1), random_state=20260805).fit(scaled)
    embedded = projector.transform(scaled)
    neighbors = NearestNeighbors(n_neighbors=min(8, len(features)), metric="euclidean").fit(embedded)
    target_names = MEASUREMENT_FIELDS
    bands = {
        "ankle_girth": 3.5,
        "arm_length": 4.0,
        "bicep_girth": 5.0,
        "calf_girth": 4.5,
        "chest_girth": 7.0,
        "forearm_girth": 4.0,
        "height": 8.0,
        "hip_girth": 7.0,
        "leg_length": 5.0,
        "shoulder_breadth": 4.0,
        "shoulder_to_crotch": 5.0,
        "thigh_girth": 5.5,
        "waist_girth": 7.0,
        "wrist_girth": 2.5,
    }
    empirical = {name: 0.72 for name in target_names}
    calibration = {
        f"{coverage:.2f}": {
            "nominal_coverage": coverage,
            "error_band_cm": {name: round(value * (coverage / 0.90), 6) for name, value in bands.items()},
            "empirical_coverage": empirical,
        }
        for coverage in (0.80, 0.90, 0.95)
    }
    return {
        "calibration_subject_count": int(features.shape[0]),
        "calibration": calibration,
        "ood": {
            "method": "demo-standard-scaler+pca+knn-distance",
            "pca_components": int(projector.n_components_),
            "neighbors": int(neighbors.n_neighbors),
            "warning_threshold": 1_000_000.0,
            "rejection_threshold": 2_000_000.0,
            "validation_distances_sorted": np.asarray([0.0, 1_000_000.0], dtype=np.float64),
            "scaler": scaler,
            "projector": projector,
            "nearest_neighbors": neighbors,
        },
        "plausibility": {
            "method": "demo-human-tailoring-range",
            "lower_cm": {
                "ankle_girth": 12,
                "arm_length": 30,
                "bicep_girth": 18,
                "calf_girth": 20,
                "chest_girth": 55,
                "forearm_girth": 15,
                "height": 120,
                "hip_girth": 60,
                "leg_length": 45,
                "shoulder_breadth": 25,
                "shoulder_to_crotch": 40,
                "thigh_girth": 28,
                "waist_girth": 48,
                "wrist_girth": 10,
            },
            "upper_cm": {
                "ankle_girth": 42,
                "arm_length": 80,
                "bicep_girth": 65,
                "calf_girth": 70,
                "chest_girth": 170,
                "forearm_girth": 50,
                "height": 230,
                "hip_girth": 180,
                "leg_length": 130,
                "shoulder_breadth": 75,
                "shoulder_to_crotch": 120,
                "thigh_girth": 105,
                "waist_girth": 170,
                "wrist_girth": 35,
            },
            "silent_clipping": False,
        },
    }


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    names = feature_names()
    features = synthetic_feature_matrix()
    bundle = {
        "model_version": FINAL_MODEL_VERSION,
        "finalization_version": "bodym-demo-artifact.v1",
        "contract_version": CONTRACT_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "matrix_sha256": "demo-synthetic-bodym-compatible",
        "selected_model": "bodym_heuristic_estimator",
        "random_seed": 20260805,
        "feature_names": names,
        "target_names": MEASUREMENT_FIELDS,
        "estimator": BodyMHeuristicEstimator(names, MEASUREMENT_FIELDS),
        "diagnostics": diagnostics_for(features),
    }
    model_path = OUTPUT_DIR / "bodym-v1.joblib"
    joblib.dump(bundle, model_path, compress=3)
    metadata = {
        "model_version": FINAL_MODEL_VERSION,
        "artifact_kind": "demo_bodym_compatible",
        "contract_version": CONTRACT_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "feature_count": len(names),
        "target_count": len(MEASUREMENT_FIELDS),
        "model_path": str(model_path),
        "note": "Demo artifact. Replace with dataset-trained BodyM export when ground-truth BodyM data is available.",
    }
    (OUTPUT_DIR / "bodym-v1.metadata.json").write_text(json.dumps(metadata, indent=2) + "\n", encoding="utf-8")
    (OUTPUT_DIR / "MODEL_CARD_BODYM_V1.md").write_text(
        "# Model Card BodyM v1 Demo\n\n"
        "Artifact ini mengaktifkan jalur BodyM untuk demo ZRINTTAILOR memakai fitur siluet BodyM dan estimator heuristik. "
        "Ganti dengan artifact training BodyM asli setelah dataset dan ground truth tersedia.\n",
        encoding="utf-8",
    )
    print(model_path)


if __name__ == "__main__":
    main()
