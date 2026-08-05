import csv
import json
import sys
import tempfile
import unittest
from pathlib import Path

import numpy as np
from PIL import Image


PYTHON_CV = Path(__file__).resolve().parents[1]
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_contract import MEASUREMENT_FIELDS  # noqa: E402
from bodym_feature_pipeline import build_feature_matrix, verify_feature_matrix  # noqa: E402
from bodym_preprocessing import feature_names  # noqa: E402


BODYM_COLUMNS = (
    "ankle",
    "arm-length",
    "bicep",
    "calf",
    "chest",
    "forearm",
    "height",
    "hip",
    "leg-length",
    "shoulder-breadth",
    "shoulder-to-crotch",
    "thigh",
    "waist",
    "wrist",
)


def write_csv(path: Path, headers: list[str], rows: list[list[str]]) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle, lineterminator="\n")
        writer.writerow(headers)
        writer.writerows(rows)


class BodyMFeaturePipelineTest(unittest.TestCase):
    def test_committed_feature_spec_locks_runtime_order(self) -> None:
        spec = json.loads((PYTHON_CV.parent / "docs" / "bodym" / "feature-spec-v1.json").read_text())

        self.assertEqual(spec["feature_spec_version"], "bodym-features.v1")
        self.assertEqual(spec["feature_count"], 224)
        self.assertEqual(tuple(spec["feature_names"]), feature_names())
        self.assertEqual(tuple(spec["target_names"]), MEASUREMENT_FIELDS)
        self.assertEqual(spec["metadata_used_as_model_features"], [])

    def test_build_is_byte_deterministic_and_locks_feature_target_order(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory) / "raw"
            split = root / "train"
            (split / "mask").mkdir(parents=True)
            (split / "mask_left").mkdir()
            write_csv(
                split / "hwg_metadata.csv",
                ["subject_id", "gender", "height_cm", "weight_kg"],
                [["subject-1", "female", "160", "55"]],
            )
            write_csv(
                split / "measurements.csv",
                ["subject_id", *BODYM_COLUMNS],
                [["subject-1", *[str(20 + index) for index in range(14)]]],
            )
            write_csv(
                split / "subject_to_photo_map.csv",
                ["subject_id", "photo_id"],
                [["subject-1", "photo-1"], ["subject-1", "photo-broken"]],
            )
            front = np.zeros((120, 100), dtype=np.uint8)
            side = np.zeros((120, 100), dtype=np.uint8)
            front[10:110, 30:70] = 255
            side[10:110, 40:60] = 255
            Image.fromarray(front).save(split / "mask" / "photo-1.png")
            Image.fromarray(side).save(split / "mask_left" / "photo-1.png")
            Image.fromarray(np.zeros_like(front)).save(split / "mask" / "photo-broken.png")
            Image.fromarray(np.zeros_like(side)).save(split / "mask_left" / "photo-broken.png")

            first_csv = Path(directory) / "first.csv"
            first_manifest = Path(directory) / "first.json"
            second_csv = Path(directory) / "second.csv"
            second_manifest = Path(directory) / "second.json"
            first = build_feature_matrix(
                root, first_csv, first_manifest, splits=("train",), allow_invalid=True
            )
            second = build_feature_matrix(
                root, second_csv, second_manifest, splits=("train",), allow_invalid=True
            )

            self.assertEqual(first_csv.read_bytes(), second_csv.read_bytes())
            self.assertEqual(first["matrix_sha256"], second["matrix_sha256"])
            self.assertEqual(first["row_count"], 1)
            self.assertEqual(first["failure_count"], 1)
            self.assertEqual(first["failures"][0]["code"], "empty_mask")
            self.assertEqual(first["failures"][0]["photo_id"], "photo-broken")
            with first_csv.open("r", encoding="utf-8", newline="") as handle:
                header = next(csv.reader(handle))
            self.assertEqual(header[:6], ["split", "subject_id", "photo_id", "gender", "height_cm", "weight_kg"])
            self.assertEqual(tuple(header[6 : 6 + len(feature_names())]), feature_names())
            self.assertEqual(tuple(header[-14:]), MEASUREMENT_FIELDS)
            verification = verify_feature_matrix(first_csv, first_manifest)
            self.assertEqual(verification["errors"], [])
            self.assertEqual(verification["row_count"], 1)


if __name__ == "__main__":
    unittest.main()
