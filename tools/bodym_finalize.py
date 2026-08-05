#!/usr/bin/env python3
"""Finalize, calibrate, and verify the BodyM v1 model bundle."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys


ROOT = Path(__file__).resolve().parents[1]
PYTHON_CV = ROOT / "python-cv"
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_finalization import run_phase4_finalization, verify_phase4_artifacts  # noqa: E402


def progress(event: str, seed: int, value: float | None) -> None:
    if event == "seed_started":
        print(f"Training MLP seed {seed}...", flush=True)
        return
    print(f"Completed seed {seed}: validation macro MAE {value:.4f} cm", flush=True)


def command_finalize(args: argparse.Namespace) -> None:
    report = run_phase4_finalization(
        Path(args.matrix),
        Path(args.manifest),
        Path(args.phase3_report),
        Path(args.output_dir),
        stability_seeds=tuple(args.seed),
        progress=progress,
    )
    print(
        json.dumps(
            {
                "model_version": report["model_version"],
                "selected_seed": report["selection"]["random_seed"],
                "validation_macro_mae_cm": report["selection"]["validation_subject_macro_mae_cm"],
                "test_macro_mae_cm": report["final_test"]["metrics"]["subject_level"]["macro_mae_cm"],
                "model": report["artifacts"]["model"],
                "report": report["artifacts"]["report_json"],
            },
            indent=2,
        )
    )


def command_verify(args: argparse.Namespace) -> None:
    result = verify_phase4_artifacts(Path(args.report))
    print(json.dumps(result, indent=2))
    if result["errors"]:
        raise SystemExit(1)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)

    finalize = commands.add_parser("finalize", help="Run stability, calibration, and final export")
    finalize.add_argument("--matrix", required=True)
    finalize.add_argument("--manifest", required=True)
    finalize.add_argument("--phase3-report", required=True)
    finalize.add_argument("--output-dir", required=True)
    finalize.add_argument(
        "--seed",
        action="append",
        type=int,
        default=None,
        help="Repeat for at least three seeds; defaults to 20260803, 20260804, 20260805",
    )
    finalize.set_defaults(handler=command_finalize)

    verify = commands.add_parser("verify", help="Reload and verify all final artifacts")
    verify.add_argument("--report", required=True)
    verify.set_defaults(handler=command_verify)
    arguments = parser.parse_args()
    if arguments.command == "finalize" and arguments.seed is None:
        arguments.seed = [20260803, 20260804, 20260805]
    return arguments


if __name__ == "__main__":
    parsed = parse_args()
    parsed.handler(parsed)
