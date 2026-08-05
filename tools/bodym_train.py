#!/usr/bin/env python3
"""Train and verify leakage-safe BodyM Phase 3 experiments."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys


ROOT = Path(__file__).resolve().parents[1]
PYTHON_CV = ROOT / "python-cv"
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_modeling import run_phase3_experiment, verify_phase3_artifacts  # noqa: E402


DEFAULT_MODELS = (
    "median",
    "nearest_neighbor",
    "random_forest",
    "extra_trees",
    "hist_gradient_boosting",
    "mlp",
)


def progress(event: str, model_name: str, value: float | None) -> None:
    if event == "fit_started":
        print(f"Training {model_name}...", flush=True)
        return
    print(f"Completed {model_name}: validation macro MAE {value:.4f} cm", flush=True)


def command_train(args: argparse.Namespace) -> None:
    report = run_phase3_experiment(
        Path(args.matrix),
        Path(args.manifest),
        Path(args.output_dir),
        model_names=tuple(args.model or DEFAULT_MODELS),
        progress=progress,
    )
    winner = report["selection"]["selected_model"]
    print(
        json.dumps(
            {
                "selected_model": winner,
                "validation_macro_mae_cm": report["selection"]["winner_validation_macro_mae_cm"],
                "test_macro_mae_cm": report["models"][winner]["metrics"]["test"]["subject_level"]["macro_mae_cm"],
                "target_win_count": report["selection"]["target_win_count"],
                "acceptance_passed": report["selection"]["acceptance_passed"],
                "report": report["artifacts"]["report_json"],
            },
            indent=2,
        )
    )


def command_verify(args: argparse.Namespace) -> None:
    result = verify_phase3_artifacts(Path(args.report))
    print(json.dumps(result, indent=2))
    if result["errors"]:
        raise SystemExit(1)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)

    train = commands.add_parser("train", help="Train baselines and candidate regressors")
    train.add_argument("--matrix", required=True)
    train.add_argument("--manifest", required=True)
    train.add_argument("--output-dir", required=True)
    train.add_argument(
        "--model",
        action="append",
        default=None,
        choices=(
            "median",
            "nearest_neighbor",
            "random_forest",
            "extra_trees",
            "hist_gradient_boosting",
            "mlp",
        ),
    )
    train.set_defaults(handler=command_train)

    verify = commands.add_parser("verify", help="Verify report, metrics, and selected model")
    verify.add_argument("--report", required=True)
    verify.set_defaults(handler=command_verify)
    return parser.parse_args()


if __name__ == "__main__":
    arguments = parse_args()
    arguments.handler(arguments)
