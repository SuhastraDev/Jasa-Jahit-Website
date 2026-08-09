#!/usr/bin/env python3
"""Evaluate one frozen BodyM artifact on the official validation and test splits."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys

import joblib


ROOT = Path(__file__).resolve().parents[1]
PYTHON_CV = ROOT / "python-cv"
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_modeling import evaluate_predictions, load_modeling_dataset  # noqa: E402


def evaluate(args: argparse.Namespace) -> None:
    dataset = load_modeling_dataset(
        Path(args.matrix),
        Path(args.manifest),
    )
    bundle = joblib.load(Path(args.model))
    estimator = bundle["estimator"]
    result = {
        "model": str(Path(args.model).resolve()),
        "model_version": bundle.get("model_version"),
        "selected_model": bundle.get("selected_model"),
        "validation": evaluate_predictions(
            dataset.validation,
            estimator.predict(dataset.validation.X),
        )["subject_level"],
        "test": evaluate_predictions(
            dataset.test,
            estimator.predict(dataset.test.X),
        )["subject_level"],
    }
    base = getattr(estimator, "base_estimator_", None)
    if base is not None:
        result["base_validation"] = evaluate_predictions(
            dataset.validation,
            base.predict(dataset.validation.X),
        )["subject_level"]
        result["base_test"] = evaluate_predictions(
            dataset.test,
            base.predict(dataset.test.X),
        )["subject_level"]
    print(json.dumps(result, indent=2, ensure_ascii=True))


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--matrix", required=True)
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--model", required=True)
    return parser.parse_args()


if __name__ == "__main__":
    evaluate(parse_args())
