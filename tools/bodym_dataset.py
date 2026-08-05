#!/usr/bin/env python3
"""Inventory, download, verify, and audit the public BodyM dataset."""

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import os
import struct
import time
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from collections import Counter, defaultdict
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable


BUCKET_URL = "https://amazon-bodym.s3.us-west-2.amazonaws.com"
DATASET_SOURCE = "https://registry.opendata.aws/bodym/"
LICENSE = "CC BY-NC 4.0"
XML_NAMESPACE = {"s3": "http://s3.amazonaws.com/doc/2006-03-01/"}


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat()


def request_bytes(url: str, attempts: int = 4) -> bytes:
    last_error: Exception | None = None
    for attempt in range(attempts):
        try:
            request = urllib.request.Request(url, headers={"User-Agent": "zrinttailor-bodym-audit/1.0"})
            with urllib.request.urlopen(request, timeout=60) as response:
                return response.read()
        except Exception as error:  # pragma: no cover - depends on network state
            last_error = error
            if attempt + 1 < attempts:
                time.sleep(2**attempt)
    raise RuntimeError(f"Request failed after {attempts} attempts: {url}") from last_error


def list_objects() -> list[dict[str, Any]]:
    objects: list[dict[str, Any]] = []
    continuation_token: str | None = None

    while True:
        params = {"list-type": "2", "max-keys": "1000"}
        if continuation_token:
            params["continuation-token"] = continuation_token
        payload = request_bytes(f"{BUCKET_URL}/?{urllib.parse.urlencode(params)}")
        root = ET.fromstring(payload)

        for item in root.findall("s3:Contents", XML_NAMESPACE):
            objects.append(
                {
                    "key": item.findtext("s3:Key", default="", namespaces=XML_NAMESPACE),
                    "size": int(item.findtext("s3:Size", default="0", namespaces=XML_NAMESPACE)),
                    "etag": item.findtext("s3:ETag", default="", namespaces=XML_NAMESPACE).strip('"'),
                    "last_modified": item.findtext(
                        "s3:LastModified", default="", namespaces=XML_NAMESPACE
                    ),
                }
            )

        if root.findtext("s3:IsTruncated", default="false", namespaces=XML_NAMESPACE) != "true":
            break
        continuation_token = root.findtext("s3:NextContinuationToken", namespaces=XML_NAMESPACE)
        if not continuation_token:
            raise RuntimeError("S3 response is truncated but has no continuation token")

    return objects


def group_summary(objects: Iterable[dict[str, Any]]) -> dict[str, Any]:
    by_split: Counter[str] = Counter()
    by_extension: Counter[str] = Counter()
    total_bytes = 0
    total_objects = 0

    for item in objects:
        key = item["key"]
        split = key.split("/", 1)[0] if "/" in key else "root"
        extension = Path(key).suffix.lower() or "[none]"
        by_split[split] += 1
        by_extension[extension] += 1
        total_bytes += item["size"]
        total_objects += 1

    return {
        "object_count": total_objects,
        "total_bytes": total_bytes,
        "total_mib": round(total_bytes / 1024 / 1024, 2),
        "objects_by_split": dict(sorted(by_split.items())),
        "objects_by_extension": dict(sorted(by_extension.items())),
    }


def write_json(path: Path, payload: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, indent=2, ensure_ascii=True) + "\n", encoding="utf-8")


def command_inventory(args: argparse.Namespace) -> None:
    objects = list_objects()
    manifest = {
        "schema_version": 1,
        "generated_at": utc_now(),
        "source": DATASET_SOURCE,
        "bucket_url": BUCKET_URL,
        "license": LICENSE,
        "summary": group_summary(objects),
        "objects": objects,
    }
    output = Path(args.output).resolve()
    write_json(output, manifest)
    print(json.dumps({"output": str(output), **manifest["summary"]}, indent=2))


def file_md5(path: Path) -> str:
    digest = hashlib.md5()  # nosec B324 - required only to compare public S3 ETags
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def load_inventory(path: Path) -> dict[str, Any]:
    manifest = json.loads(path.read_text(encoding="utf-8"))
    if manifest.get("schema_version") != 1 or not isinstance(manifest.get("objects"), list):
        raise ValueError(f"Unsupported inventory manifest: {path}")
    return manifest


def selected_objects(manifest: dict[str, Any], splits: set[str]) -> list[dict[str, Any]]:
    if not splits:
        return manifest["objects"]
    return [item for item in manifest["objects"] if item["key"].split("/", 1)[0] in splits]


def download_object(item: dict[str, Any], root: Path) -> str:
    destination = (root / Path(item["key"])).resolve()
    destination.relative_to(root)
    destination.parent.mkdir(parents=True, exist_ok=True)

    if destination.exists() and destination.stat().st_size == item["size"]:
        if "-" in item["etag"] or file_md5(destination) == item["etag"]:
            return "skipped"

    encoded_key = urllib.parse.quote(item["key"], safe="/")
    temporary = destination.with_suffix(destination.suffix + ".part")
    temporary.write_bytes(request_bytes(f"{BUCKET_URL}/{encoded_key}"))
    if temporary.stat().st_size != item["size"]:
        temporary.unlink(missing_ok=True)
        raise RuntimeError(f"Size mismatch for {item['key']}")
    if "-" not in item["etag"] and file_md5(temporary) != item["etag"]:
        temporary.unlink(missing_ok=True)
        raise RuntimeError(f"ETag mismatch for {item['key']}")
    os.replace(temporary, destination)
    return "downloaded"


def command_download(args: argparse.Namespace) -> None:
    inventory_path = Path(args.inventory).resolve()
    root = Path(args.root).resolve()
    root.mkdir(parents=True, exist_ok=True)
    objects = selected_objects(load_inventory(inventory_path), set(args.split or []))
    counters: Counter[str] = Counter()

    with ThreadPoolExecutor(max_workers=args.workers) as executor:
        futures = {executor.submit(download_object, item, root): item for item in objects}
        for index, future in enumerate(as_completed(futures), start=1):
            item = futures[future]
            try:
                counters[future.result()] += 1
            except Exception as error:
                raise RuntimeError(f"Failed to download {item['key']}: {error}") from error
            if index % 500 == 0 or index == len(objects):
                print(f"Processed {index}/{len(objects)} objects")

    print(json.dumps({"root": str(root), "selected": len(objects), **counters}, indent=2))


def command_verify(args: argparse.Namespace) -> None:
    manifest = load_inventory(Path(args.inventory).resolve())
    root = Path(args.root).resolve()
    objects = selected_objects(manifest, set(args.split or []))
    errors: list[str] = []
    verified = 0

    for item in objects:
        path = (root / Path(item["key"])).resolve()
        try:
            path.relative_to(root)
        except ValueError:
            errors.append(f"Unsafe path: {item['key']}")
            continue
        if not path.is_file():
            errors.append(f"Missing: {item['key']}")
            continue
        if path.stat().st_size != item["size"]:
            errors.append(f"Size mismatch: {item['key']}")
            continue
        if "-" not in item["etag"] and file_md5(path) != item["etag"]:
            errors.append(f"ETag mismatch: {item['key']}")
            continue
        verified += 1

    result = {"verified": verified, "expected": len(objects), "errors": errors}
    print(json.dumps(result, indent=2))
    if errors:
        raise SystemExit(1)


def read_csv(path: Path) -> tuple[list[str], list[dict[str, str]]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        return list(reader.fieldnames or []), list(reader)


def missing_values(rows: list[dict[str, str]], headers: list[str]) -> dict[str, int]:
    return {
        header: sum(1 for row in rows if row.get(header, "").strip() == "")
        for header in headers
    }


def numeric_summary(rows: list[dict[str, str]], headers: list[str]) -> dict[str, Any]:
    summary: dict[str, Any] = {}
    for header in headers:
        if header in {"subject_id", "gender", "photo_id"}:
            continue
        values: list[float] = []
        for row in rows:
            value = row.get(header, "").strip()
            if value:
                try:
                    values.append(float(value))
                except ValueError:
                    values = []
                    break
        if values:
            summary[header] = {
                "count": len(values),
                "min": round(min(values), 4),
                "max": round(max(values), 4),
                "mean": round(sum(values) / len(values), 4),
            }
    return summary


def duplicate_count(rows: list[dict[str, str]], columns: list[str]) -> int:
    keys = [tuple(row.get(column, "") for column in columns) for row in rows]
    return len(keys) - len(set(keys))


def png_dimensions(path: Path) -> tuple[int, int]:
    with path.open("rb") as handle:
        header = handle.read(24)
    if len(header) != 24 or header[:8] != b"\x89PNG\r\n\x1a\n" or header[12:16] != b"IHDR":
        raise ValueError("invalid PNG header")
    return struct.unpack(">II", header[16:24])


def audit_split(root: Path, split: str) -> dict[str, Any]:
    split_root = root / split
    metadata_headers, metadata_rows = read_csv(split_root / "hwg_metadata.csv")
    measurement_headers, measurement_rows = read_csv(split_root / "measurements.csv")
    map_headers, map_rows = read_csv(split_root / "subject_to_photo_map.csv")

    metadata_subjects = {row["subject_id"] for row in metadata_rows}
    measurement_subjects = {row["subject_id"] for row in measurement_rows}
    map_subjects = {row["subject_id"] for row in map_rows}
    mapped_photo_ids = {row["photo_id"] for row in map_rows}
    front_paths = list((split_root / "mask").glob("*.png"))
    side_paths = list((split_root / "mask_left").glob("*.png"))
    front_ids = {path.stem for path in front_paths}
    side_ids = {path.stem for path in side_paths}

    dimensions: Counter[str] = Counter()
    corrupt_png: list[str] = []
    for path in front_paths + side_paths:
        try:
            width, height = png_dimensions(path)
            dimensions[f"{width}x{height}"] += 1
        except ValueError:
            corrupt_png.append(str(path.relative_to(root)).replace("\\", "/"))

    photos_per_subject: Counter[str] = Counter(row["subject_id"] for row in map_rows)
    return {
        "csv": {
            "hwg_metadata": {
                "headers": metadata_headers,
                "rows": len(metadata_rows),
                "missing": missing_values(metadata_rows, metadata_headers),
                "duplicate_subject_ids": duplicate_count(metadata_rows, ["subject_id"]),
                "gender_counts": dict(sorted(Counter(row["gender"] for row in metadata_rows).items())),
                "numeric_summary": numeric_summary(metadata_rows, metadata_headers),
            },
            "measurements": {
                "headers": measurement_headers,
                "rows": len(measurement_rows),
                "measurement_count": max(0, len(measurement_headers) - 1),
                "missing": missing_values(measurement_rows, measurement_headers),
                "duplicate_subject_ids": duplicate_count(measurement_rows, ["subject_id"]),
                "numeric_summary": numeric_summary(measurement_rows, measurement_headers),
            },
            "subject_to_photo_map": {
                "headers": map_headers,
                "rows": len(map_rows),
                "unique_photo_ids": len(mapped_photo_ids),
                "missing": missing_values(map_rows, map_headers),
                "duplicate_subject_photo_pairs": duplicate_count(map_rows, ["subject_id", "photo_id"]),
                "photos_per_subject": dict(sorted(Counter(photos_per_subject.values()).items())),
            },
        },
        "subjects": {
            "metadata": len(metadata_subjects),
            "measurements": len(measurement_subjects),
            "photo_map": len(map_subjects),
            "common_to_all_tables": len(metadata_subjects & measurement_subjects & map_subjects),
            "metadata_not_measurements": sorted(metadata_subjects - measurement_subjects),
            "metadata_not_photo_map": sorted(metadata_subjects - map_subjects),
            "measurements_not_metadata": sorted(measurement_subjects - metadata_subjects),
            "measurements_not_photo_map": sorted(measurement_subjects - map_subjects),
            "photo_map_not_metadata": sorted(map_subjects - metadata_subjects),
            "photo_map_not_measurements": sorted(map_subjects - measurement_subjects),
        },
        "images": {
            "front_masks": len(front_paths),
            "side_masks": len(side_paths),
            "paired_photo_ids": len(front_ids & side_ids),
            "mapped_missing_front": sorted(mapped_photo_ids - front_ids),
            "mapped_missing_side": sorted(mapped_photo_ids - side_ids),
            "orphan_front": sorted(front_ids - mapped_photo_ids),
            "orphan_side": sorted(side_ids - mapped_photo_ids),
            "dimensions": dict(sorted(dimensions.items())),
            "corrupt_png": corrupt_png,
        },
        "_subject_ids": sorted(metadata_subjects | measurement_subjects | map_subjects),
        "_photo_ids": sorted(mapped_photo_ids | front_ids | side_ids),
    }


def command_audit(args: argparse.Namespace) -> None:
    root = Path(args.root).resolve()
    split_names = args.split or ["train", "testA", "testB"]
    splits = {split: audit_split(root, split) for split in split_names}

    subject_overlaps: dict[str, dict[str, Any]] = {}
    photo_overlaps: dict[str, dict[str, Any]] = {}
    for index, left in enumerate(split_names):
        for right in split_names[index + 1 :]:
            label = f"{left}__{right}"
            shared_subjects = sorted(
                set(splits[left]["_subject_ids"]) & set(splits[right]["_subject_ids"])
            )
            shared_photos = sorted(set(splits[left]["_photo_ids"]) & set(splits[right]["_photo_ids"]))
            subject_overlaps[label] = {"count": len(shared_subjects), "ids": shared_subjects}
            photo_overlaps[label] = {"count": len(shared_photos), "ids": shared_photos}

    for split in splits.values():
        del split["_subject_ids"]
        del split["_photo_ids"]

    report = {
        "schema_version": 1,
        "generated_at": utc_now(),
        "dataset_root": str(root),
        "source": DATASET_SOURCE,
        "license": LICENSE,
        "splits": splits,
        "cross_split": {
            "subject_id_overlaps": subject_overlaps,
            "photo_id_overlaps": photo_overlaps,
        },
    }
    output = Path(args.output).resolve()
    write_json(output, report)
    compact = {
        "output": str(output),
        "subjects": {name: data["subjects"]["common_to_all_tables"] for name, data in splits.items()},
        "photo_pairs": {name: data["images"]["paired_photo_ids"] for name, data in splits.items()},
        "subject_overlaps": {key: value["count"] for key, value in subject_overlaps.items()},
        "photo_overlaps": {key: value["count"] for key, value in photo_overlaps.items()},
    }
    print(json.dumps(compact, indent=2))


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)

    inventory = commands.add_parser("inventory", help="Create a public S3 object inventory")
    inventory.add_argument("--output", required=True)
    inventory.set_defaults(handler=command_inventory)

    download = commands.add_parser("download", help="Download and validate inventory objects")
    download.add_argument("--inventory", required=True)
    download.add_argument("--root", required=True)
    download.add_argument("--split", action="append", help="Optional top-level split filter")
    download.add_argument("--workers", type=int, default=16)
    download.set_defaults(handler=command_download)

    verify = commands.add_parser("verify", help="Verify downloaded objects against the inventory")
    verify.add_argument("--inventory", required=True)
    verify.add_argument("--root", required=True)
    verify.add_argument("--split", action="append", help="Optional top-level split filter")
    verify.set_defaults(handler=command_verify)

    audit = commands.add_parser("audit", help="Audit BodyM CSV relations and PNG pairs")
    audit.add_argument("--root", required=True)
    audit.add_argument("--output", required=True)
    audit.add_argument("--split", action="append", help="Optional split filter")
    audit.set_defaults(handler=command_audit)

    return parser.parse_args()


if __name__ == "__main__":
    arguments = parse_args()
    arguments.handler(arguments)
