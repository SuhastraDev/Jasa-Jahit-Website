import csv
import importlib.util
import struct
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location("bodym_dataset", ROOT / "tools" / "bodym_dataset.py")
bodym_dataset = importlib.util.module_from_spec(SPEC)
assert SPEC.loader is not None
SPEC.loader.exec_module(bodym_dataset)


def write_csv(path: Path, headers: list[str], rows: list[list[str]]) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(headers)
        writer.writerows(rows)


def write_png_header(path: Path, width: int = 720, height: int = 960) -> None:
    path.write_bytes(b"\x89PNG\r\n\x1a\n" + b"\x00\x00\x00\rIHDR" + struct.pack(">II", width, height))


class BodyMDatasetToolTest(unittest.TestCase):
    def test_group_summary_counts_splits_extensions_and_bytes(self) -> None:
        summary = bodym_dataset.group_summary(
            [
                {"key": "train/mask/a.png", "size": 10},
                {"key": "train/measurements.csv", "size": 20},
                {"key": "testA/mask/b.png", "size": 30},
            ]
        )

        self.assertEqual(summary["object_count"], 3)
        self.assertEqual(summary["total_bytes"], 60)
        self.assertEqual(summary["objects_by_split"], {"testA": 1, "train": 2})
        self.assertEqual(summary["objects_by_extension"], {".csv": 1, ".png": 2})

    def test_audit_split_links_subject_measurement_and_two_views(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            split = root / "train"
            (split / "mask").mkdir(parents=True)
            (split / "mask_left").mkdir()
            write_csv(split / "hwg_metadata.csv", ["subject_id", "gender", "height_cm", "weight_kg"], [["s1", "female", "160", "55"]])
            write_csv(split / "measurements.csv", ["subject_id", "height", "chest"], [["s1", "161", "88"]])
            write_csv(split / "subject_to_photo_map.csv", ["subject_id", "photo_id"], [["s1", "p1"]])
            write_png_header(split / "mask" / "p1.png")
            write_png_header(split / "mask_left" / "p1.png")

            audit = bodym_dataset.audit_split(root, "train")

            self.assertEqual(audit["subjects"]["common_to_all_tables"], 1)
            self.assertEqual(audit["images"]["paired_photo_ids"], 1)
            self.assertEqual(audit["images"]["dimensions"], {"720x960": 2})
            self.assertEqual(audit["images"]["mapped_missing_front"], [])
            self.assertEqual(audit["images"]["mapped_missing_side"], [])

    def test_png_dimensions_rejects_non_png(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "invalid.png"
            path.write_bytes(b"not-a-png")
            with self.assertRaises(ValueError):
                bodym_dataset.png_dimensions(path)


if __name__ == "__main__":
    unittest.main()
