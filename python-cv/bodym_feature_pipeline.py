"""Reproducible BodyM feature-matrix builder."""

from __future__ import annotations

import csv
import hashlib
import json
import os
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Callable, Iterable

import numpy as np
from PIL import Image

from bodym_contract import CONTRACT_VERSION, MEASUREMENT_FIELDS
from bodym_preprocessing import (
    PREPROCESSING_VERSION,
    SilhouetteValidationError,
    extract_pair_features,
    feature_names,
    preprocess_silhouette,
    scale_from_known_height,
)


PIPELINE_VERSION = "bodym-features.v1"
BODYM_TO_CONTRACT = {
    "ankle": "ankle_girth",
    "arm-length": "arm_length",
    "bicep": "bicep_girth",
    "calf": "calf_girth",
    "chest": "chest_girth",
    "forearm": "forearm_girth",
    "height": "height",
    "hip": "hip_girth",
    "leg-length": "leg_length",
    "shoulder-breadth": "shoulder_breadth",
    "shoulder-to-crotch": "shoulder_to_crotch",
    "thigh": "thigh_girth",
    "waist": "waist_girth",
    "wrist": "wrist_girth",
}
METADATA_COLUMNS = ("split", "subject_id", "photo_id", "gender", "height_cm", "weight_kg")

if tuple(BODYM_TO_CONTRACT.values()) != MEASUREMENT_FIELDS:
    raise RuntimeError("BodyM target mapping does not match the frozen contract")


class DatasetBuildError(RuntimeError):
    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


def _read_csv_by_id(path: Path, id_column: str) -> dict[str, dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        rows = list(csv.DictReader(handle))
    result = {row[id_column]: row for row in rows}
    if len(result) != len(rows):
        raise DatasetBuildError(
            "duplicate_csv_id",
            f"ID duplikat ditemukan pada {path.name}.",
            {"path": str(path), "id_column": id_column},
        )
    return result


def _read_photo_map(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        rows = list(csv.DictReader(handle))
    return sorted(rows, key=lambda row: (row["subject_id"], row["photo_id"]))


def _load_mask(path: Path) -> np.ndarray:
    if not path.is_file():
        raise DatasetBuildError("missing_mask", "File siluet tidak ditemukan.", {"path": str(path)})
    with Image.open(path) as image:
        return np.asarray(image.convert("L"), dtype=np.uint8)


def _number(value: str, *, field: str, subject_id: str) -> float:
    try:
        number = float(value)
    except (TypeError, ValueError) as error:
        raise DatasetBuildError(
            "invalid_numeric_value",
            f"Nilai {field} bukan angka.",
            {"field": field, "subject_id": subject_id, "value": value},
        ) from error
    if not np.isfinite(number):
        raise DatasetBuildError(
            "invalid_numeric_value",
            f"Nilai {field} tidak finite.",
            {"field": field, "subject_id": subject_id, "value": value},
        )
    return number


def _format_number(value: float) -> str:
    return f"{float(value):.8f}"


def iter_feature_rows(
    dataset_root: Path,
    *,
    splits: Iterable[str],
    progress: Callable[[int, str, str], None] | None = None,
    allow_invalid: bool = False,
    failures: list[dict[str, Any]] | None = None,
) -> Iterable[dict[str, str]]:
    processed = 0
    for split_name in splits:
        split_root = dataset_root / split_name
        metadata = _read_csv_by_id(split_root / "hwg_metadata.csv", "subject_id")
        measurements = _read_csv_by_id(split_root / "measurements.csv", "subject_id")
        photo_map = _read_photo_map(split_root / "subject_to_photo_map.csv")

        for relation in photo_map:
            subject_id = relation["subject_id"]
            photo_id = relation["photo_id"]
            if subject_id not in metadata or subject_id not in measurements:
                raise DatasetBuildError(
                    "subject_relation_missing",
                    "Subjek pada photo map tidak lengkap di metadata atau measurements.",
                    {"split": split_name, "subject_id": subject_id, "photo_id": photo_id},
                )
            meta = metadata[subject_id]
            targets = measurements[subject_id]
            known_height = _number(meta["height_cm"], field="height_cm", subject_id=subject_id)
            front_mask = _load_mask(split_root / "mask" / f"{photo_id}.png")
            side_mask = _load_mask(split_root / "mask_left" / f"{photo_id}.png")

            try:
                front = preprocess_silhouette(
                    front_mask,
                    view="front",
                    cm_per_pixel=scale_from_known_height(front_mask, known_height),
                )
                side = preprocess_silhouette(
                    side_mask,
                    view="side",
                    cm_per_pixel=scale_from_known_height(side_mask, known_height),
                )
                vector = extract_pair_features(front, side)
            except SilhouetteValidationError as error:
                build_error = DatasetBuildError(
                    error.code,
                    str(error),
                    {
                        "split": split_name,
                        "subject_id": subject_id,
                        "photo_id": photo_id,
                        **error.details,
                    },
                )
                if not allow_invalid:
                    raise build_error from error
                if failures is not None:
                    failures.append(
                        {
                            "code": build_error.code,
                            "message": str(build_error),
                            **build_error.details,
                        }
                    )
                continue

            row = {
                "split": split_name,
                "subject_id": subject_id,
                "photo_id": photo_id,
                "gender": meta["gender"],
                "height_cm": _format_number(known_height),
                "weight_kg": _format_number(
                    _number(meta["weight_kg"], field="weight_kg", subject_id=subject_id)
                ),
            }
            row.update({name: _format_number(value) for name, value in vector.as_dict().items()})
            for bodym_name, contract_name in BODYM_TO_CONTRACT.items():
                row[contract_name] = _format_number(
                    _number(targets[bodym_name], field=bodym_name, subject_id=subject_id)
                )
            processed += 1
            if progress:
                progress(processed, split_name, photo_id)
            yield row


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def build_feature_matrix(
    dataset_root: Path,
    output_csv: Path,
    output_manifest: Path,
    *,
    splits: tuple[str, ...] = ("train", "testA", "testB"),
    progress: Callable[[int, str, str], None] | None = None,
    allow_invalid: bool = False,
) -> dict[str, Any]:
    dataset_root = Path(dataset_root).resolve()
    output_csv = Path(output_csv).resolve()
    output_manifest = Path(output_manifest).resolve()
    output_csv.parent.mkdir(parents=True, exist_ok=True)
    output_manifest.parent.mkdir(parents=True, exist_ok=True)
    temporary_csv = output_csv.with_suffix(output_csv.suffix + ".part")
    columns = (*METADATA_COLUMNS, *feature_names(), *MEASUREMENT_FIELDS)
    split_counts: dict[str, int] = {split: 0 for split in splits}
    row_count = 0
    failures: list[dict[str, Any]] = []

    with temporary_csv.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=columns, lineterminator="\n")
        writer.writeheader()
        for row in iter_feature_rows(
            dataset_root,
            splits=splits,
            progress=progress,
            allow_invalid=allow_invalid,
            failures=failures,
        ):
            writer.writerow(row)
            row_count += 1
            split_counts[row["split"]] += 1
    os.replace(temporary_csv, output_csv)

    manifest = {
        "schema_version": 1,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "pipeline_version": PIPELINE_VERSION,
        "preprocessing_version": PREPROCESSING_VERSION,
        "contract_version": CONTRACT_VERSION,
        "dataset_root": str(dataset_root),
        "matrix_path": str(output_csv),
        "matrix_sha256": _sha256(output_csv),
        "row_count": row_count,
        "input_pair_count": row_count + len(failures),
        "failure_count": len(failures),
        "failures": failures,
        "split_counts": split_counts,
        "feature_count": len(feature_names()),
        "feature_names": list(feature_names()),
        "target_names": list(MEASUREMENT_FIELDS),
        "scale_policy": "BodyM height_cm divided by robust silhouette pixel height",
    }
    output_manifest.write_text(
        json.dumps(manifest, indent=2, ensure_ascii=True) + "\n",
        encoding="utf-8",
    )
    return manifest


def verify_feature_matrix(matrix_path: Path, manifest_path: Path) -> dict[str, Any]:
    matrix_path = Path(matrix_path).resolve()
    manifest_path = Path(manifest_path).resolve()
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    expected_columns = [*METADATA_COLUMNS, *feature_names(), *MEASUREMENT_FIELDS]
    numeric_columns = {"height_cm", "weight_kg", *feature_names(), *MEASUREMENT_FIELDS}
    errors: list[str] = []
    row_count = 0
    split_counts: dict[str, int] = {}
    row_keys: set[tuple[str, str, str]] = set()

    if _sha256(matrix_path) != manifest.get("matrix_sha256"):
        errors.append("matrix_sha256_mismatch")
    with matrix_path.open("r", encoding="utf-8", newline="") as handle:
        reader = csv.DictReader(handle)
        if list(reader.fieldnames or []) != expected_columns:
            errors.append("header_order_mismatch")
        for row in reader:
            row_count += 1
            split_counts[row["split"]] = split_counts.get(row["split"], 0) + 1
            key = (row["split"], row["subject_id"], row["photo_id"])
            if key in row_keys:
                errors.append(f"duplicate_row:{'/'.join(key)}")
            row_keys.add(key)
            for column in numeric_columns:
                try:
                    value = float(row[column])
                except (KeyError, TypeError, ValueError):
                    errors.append(f"invalid_number:{row.get('split')}:{row.get('photo_id')}:{column}")
                    continue
                if not np.isfinite(value):
                    errors.append(f"non_finite:{row['split']}:{row['photo_id']}:{column}")
            if len(errors) >= 50:
                break

    if row_count != manifest.get("row_count"):
        errors.append("row_count_mismatch")
    if split_counts != manifest.get("split_counts"):
        errors.append("split_counts_mismatch")
    return {
        "matrix_path": str(matrix_path),
        "row_count": row_count,
        "split_counts": split_counts,
        "feature_count": len(feature_names()),
        "target_count": len(MEASUREMENT_FIELDS),
        "errors": errors,
    }
