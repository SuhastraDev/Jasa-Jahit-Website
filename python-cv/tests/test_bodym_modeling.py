from __future__ import annotations

import csv
import hashlib
import json
from pathlib import Path
import sys
import tempfile
import unittest

import numpy as np


PYTHON_CV_ROOT = Path(__file__).resolve().parents[1]
if str(PYTHON_CV_ROOT) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV_ROOT))

from bodym_contract import MEASUREMENT_FIELDS
from bodym_preprocessing import feature_names


def _write_matrix(
    root: Path,
    rows: list[tuple[str, str, str, float]] | None = None,
) -> tuple[Path, Path]:
    matrix_path = root / "matrix.csv"
    manifest_path = root / "manifest.json"
    columns = (
        "split",
        "subject_id",
        "photo_id",
        "gender",
        "height_cm",
        "weight_kg",
        *feature_names(),
        *MEASUREMENT_FIELDS,
    )
    rows = rows or [
        ("train", "train-a", "p1", 1.0),
        ("train", "train-b", "p2", 2.0),
        ("testA", "shared", "p3", 3.0),
        ("testA", "valid-a", "p4", 4.0),
        ("testB", "shared", "p5", 5.0),
        ("testB", "valid-b", "p6", 6.0),
    ]
    with matrix_path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=columns, lineterminator="\n")
        writer.writeheader()
        for split, subject_id, photo_id, seed in rows:
            row = {
                "split": split,
                "subject_id": subject_id,
                "photo_id": photo_id,
                "gender": "female",
                "height_cm": f"{150 + seed:.8f}",
                "weight_kg": f"{50 + seed:.8f}",
            }
            row.update({name: f"{seed + index / 1000:.8f}" for index, name in enumerate(feature_names())})
            row.update({name: f"{20 + seed + index:.8f}" for index, name in enumerate(MEASUREMENT_FIELDS)})
            writer.writerow(row)

    digest = hashlib.sha256(matrix_path.read_bytes()).hexdigest()
    manifest_path.write_text(
        json.dumps(
            {
                "matrix_sha256": digest,
                "row_count": len(rows),
                "split_counts": {
                    split: sum(row[0] == split for row in rows)
                    for split in ("train", "testA", "testB")
                },
                "feature_names": list(feature_names()),
                "target_names": list(MEASUREMENT_FIELDS),
                "feature_count": len(feature_names()),
                "target_count": len(MEASUREMENT_FIELDS),
                "pipeline_version": "bodym-features.v1",
                "preprocessing_version": "bodym-preprocess.v1",
                "contract_version": "bodym.v1",
            }
        ),
        encoding="utf-8",
    )
    return matrix_path, manifest_path


class BodyMModelingDatasetTest(unittest.TestCase):
    def test_later_split_rows_are_excluded_when_subject_overlaps(self) -> None:
        from bodym_modeling import load_modeling_dataset

        with tempfile.TemporaryDirectory() as directory:
            matrix_path, manifest_path = _write_matrix(Path(directory))
            dataset = load_modeling_dataset(matrix_path, manifest_path)

        self.assertEqual(dataset.train.row_count, 2)
        self.assertEqual(dataset.validation.row_count, 2)
        self.assertEqual(dataset.test.row_count, 1)
        self.assertEqual(dataset.test.subject_ids.tolist(), ["valid-b"])
        self.assertEqual(
            dataset.exclusions,
            (
                {
                    "subject_id": "shared",
                    "kept_split": "testA",
                    "excluded_split": "testB",
                    "excluded_rows": 1,
                },
            ),
        )
        self.assertEqual(dataset.cross_split_subjects, ())

    def test_metrics_report_mae_rmse_and_signed_bias(self) -> None:
        from bodym_modeling import calculate_regression_metrics

        metrics = calculate_regression_metrics(
            np.asarray([[10.0, 20.0], [14.0, 22.0]]),
            np.asarray([[12.0, 18.0], [13.0, 25.0]]),
            ("first", "second"),
        )

        self.assertEqual(metrics["sample_count"], 2)
        self.assertEqual(metrics["per_target"]["first"]["mae_cm"], 1.5)
        self.assertEqual(metrics["per_target"]["first"]["bias_cm"], 0.5)
        self.assertEqual(metrics["per_target"]["second"]["mae_cm"], 2.5)
        self.assertEqual(metrics["per_target"]["second"]["bias_cm"], 0.5)
        self.assertAlmostEqual(metrics["per_target"]["first"]["rmse_cm"], 1.581139, places=6)

    def test_experiment_selects_on_validation_and_verifies_artifacts(self) -> None:
        from bodym_modeling import run_phase3_experiment, verify_phase3_artifacts

        rows = []
        for index in range(1, 13):
            rows.append(("train", f"train-{index}", f"p-{index}", float(index)))
        for index in range(13, 17):
            rows.append(("testA", f"validation-{index}", f"p-{index}", float(index)))
        for index in range(17, 21):
            rows.append(("testB", f"test-{index}", f"p-{index}", float(index)))

        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            matrix_path, manifest_path = _write_matrix(root, rows)
            report = run_phase3_experiment(
                matrix_path,
                manifest_path,
                root / "artifacts",
                model_names=("median", "nearest_neighbor", "extra_trees"),
            )
            verification = verify_phase3_artifacts(root / "artifacts" / "phase-3-report.json")

        self.assertEqual(report["selection"]["selected_model"], "extra_trees")
        self.assertFalse(report["split_policy"]["selection_uses_testB"])
        self.assertIn("test", report["models"]["extra_trees"]["metrics"])
        self.assertEqual(verification["errors"], [])


if __name__ == "__main__":
    unittest.main()
