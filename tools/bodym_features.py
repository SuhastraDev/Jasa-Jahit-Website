#!/usr/bin/env python3
"""Build BodyM v1 feature matrices and visual QA artifacts."""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PYTHON_CV = ROOT / "python-cv"
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_feature_pipeline import build_feature_matrix, verify_feature_matrix  # noqa: E402
from bodym_visual_qa import build_qa_gallery  # noqa: E402


def progress(index: int, split: str, photo_id: str) -> None:
    if index % 250 == 0:
        print(f"Processed {index} pairs ({split}/{photo_id})", flush=True)


def command_build(args: argparse.Namespace) -> None:
    manifest = build_feature_matrix(
        Path(args.dataset_root),
        Path(args.output_csv),
        Path(args.output_manifest),
        splits=tuple(args.split or ("train", "testA", "testB")),
        progress=progress,
        allow_invalid=args.allow_invalid,
    )
    print(json.dumps({
        "matrix_path": manifest["matrix_path"],
        "matrix_sha256": manifest["matrix_sha256"],
        "row_count": manifest["row_count"],
        "split_counts": manifest["split_counts"],
        "feature_count": manifest["feature_count"],
        "failure_count": manifest["failure_count"],
    }, indent=2))


def command_qa(args: argparse.Namespace) -> None:
    summary = build_qa_gallery(
        Path(args.dataset_root),
        Path(args.output_dir),
        samples_per_split=args.samples_per_split,
        splits=tuple(args.split or ("train", "testA", "testB")),
    )
    print(json.dumps({"output_dir": str(Path(args.output_dir).resolve()), **summary}, indent=2))


def command_verify(args: argparse.Namespace) -> None:
    result = verify_feature_matrix(Path(args.matrix), Path(args.manifest))
    print(json.dumps(result, indent=2))
    if result["errors"]:
        raise SystemExit(1)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)

    build = commands.add_parser("build", help="Generate deterministic features and targets")
    build.add_argument("--dataset-root", required=True)
    build.add_argument("--output-csv", required=True)
    build.add_argument("--output-manifest", required=True)
    build.add_argument("--split", action="append")
    build.add_argument(
        "--allow-invalid",
        action="store_true",
        help="Record invalid pairs in the manifest and continue building valid rows",
    )
    build.set_defaults(handler=command_build)

    qa = commands.add_parser("qa", help="Generate front/side visual QA gallery")
    qa.add_argument("--dataset-root", required=True)
    qa.add_argument("--output-dir", required=True)
    qa.add_argument("--samples-per-split", type=int, default=3)
    qa.add_argument("--split", action="append")
    qa.set_defaults(handler=command_qa)

    verify = commands.add_parser("verify", help="Verify matrix hash, schema, rows, and finite values")
    verify.add_argument("--matrix", required=True)
    verify.add_argument("--manifest", required=True)
    verify.set_defaults(handler=command_verify)
    return parser.parse_args()


if __name__ == "__main__":
    arguments = parse_args()
    arguments.handler(arguments)
