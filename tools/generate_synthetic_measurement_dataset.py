"""Generate a controlled synthetic measurement dataset for local MAE testing.

This dataset is not a replacement for real human validation. It creates known
"actual" tailoring measurements, simulates front/side/back rendered poses, and
adds deterministic measurement noise to produce predicted values. The output is
useful for validating the evaluation pipeline and documenting an initial,
repeatable synthetic test.
"""

from __future__ import annotations

import csv
import math
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "storage" / "app" / "synthetic_measurements"
SAMPLE_DIR = OUT_DIR / "samples"
CSV_PATH = OUT_DIR / "synthetic_measurement_eval.csv"
README_PATH = OUT_DIR / "README.md"

FIELDS = [
    "neck",
    "chest",
    "waist",
    "hips",
    "shoulder_width",
    "shirt_length",
    "arm_length",
    "upper_arm",
    "wrist",
    "height",
    "pants_waist",
    "pants_hips",
    "thigh",
    "knee",
    "calf",
    "ankle",
    "inseam",
    "outseam",
    "rise",
]


def base_sample(index: int) -> dict[str, float | str]:
    height = 158 + index * 2.8
    shoulder = 38 + (index % 5) * 1.1
    chest = 84 + index * 1.75 + (index % 3) * 1.2
    waist = chest - 13 + (index % 4) * 0.9
    hips = waist + 15 + (index % 3) * 1.4

    return {
        "sample_id": f"S{index + 1:03d}",
        "neck": 33 + index * 0.35,
        "chest": chest,
        "waist": waist,
        "hips": hips,
        "shoulder_width": shoulder,
        "shirt_length": height * 0.405,
        "arm_length": height * 0.335,
        "upper_arm": chest * 0.34,
        "wrist": 15.2 + (index % 4) * 0.45,
        "height": height,
        "pants_waist": waist,
        "pants_hips": hips,
        "thigh": hips * 0.565,
        "knee": hips * 0.395,
        "calf": hips * 0.365,
        "ankle": 20.2 + (index % 5) * 0.55,
        "inseam": height * 0.455,
        "outseam": height * 0.595,
        "rise": height * 0.14,
    }


def deterministic_error(index: int, field: str) -> float:
    field_weight = (sum(ord(ch) for ch in field) % 9) / 10
    wave = math.sin(index * 1.73 + field_weight) * 0.9

    if field in {"height", "shoulder_width", "shirt_length", "arm_length", "inseam", "outseam"}:
        limit = 1.8
    elif field in {"chest", "waist", "hips", "pants_waist", "pants_hips"}:
        limit = 3.2
    else:
        limit = 4.0

    return round(max(-limit, min(limit, wave + (field_weight - 0.4))), 2)


def predicted_value(actual: float, index: int, field: str) -> float:
    return round(actual + deterministic_error(index, field), 2)


def svg_person(sample: dict[str, float | str], view: str) -> str:
    height = float(sample["height"])
    shoulder = float(sample["shoulder_width"])
    chest = float(sample["chest"])
    hips = float(sample["hips"])
    scale = 170 / height
    torso_width = shoulder * 2.05 * scale
    hip_width = hips * 0.62 * scale
    body_depth = chest * 0.24 * scale
    center_x = 110

    if view == "side":
        torso_path = (
            f"M{center_x-10} 62 Q{center_x+body_depth} 80 {center_x+body_depth-6} 138 "
            f"Q{center_x+8} 154 {center_x-8} 140 Q{center_x-22} 95 {center_x-10} 62 Z"
        )
        arm_left = center_x - 18
        arm_right = center_x + 25
        leg_left = center_x - 8
        leg_right = center_x + 17
    else:
        torso_path = (
            f"M{center_x-torso_width/2} 66 Q{center_x} 52 {center_x+torso_width/2} 66 "
            f"L{center_x+hip_width/2} 140 Q{center_x} 154 {center_x-hip_width/2} 140 Z"
        )
        arm_left = center_x - torso_width / 2 - 22
        arm_right = center_x + torso_width / 2 + 22
        leg_left = center_x - 18
        leg_right = center_x + 18

    guide = (
        '<path d="M77 116 Q110 128 143 116" fill="none" stroke="#16a34a" '
        'stroke-width="4" stroke-linecap="round"/>'
        if view != "back"
        else '<path d="M77 66 L77 140 L143 140 L143 66" fill="none" stroke="#16a34a" stroke-width="3" stroke-dasharray="6 5"/>'
    )

    return f"""<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 260 230">
  <rect width="260" height="230" fill="#f8fafc"/>
  <rect x="0" y="198" width="260" height="32" fill="#e2e8f0"/>
  <rect x="185" y="42" width="42" height="118" rx="4" fill="#e0f2fe" stroke="#0284c7" stroke-width="3"/>
  <path d="M193 60 H219 M193 78 H219 M193 96 H219 M193 114 H219 M193 132 H219 M193 150 H219 M199 50 V154 M213 50 V154" stroke="#38bdf8" stroke-width="1.2"/>
  <text x="206" y="176" text-anchor="middle" fill="#0369a1" font-family="Arial" font-size="9" font-weight="700">A4</text>
  <circle cx="{center_x}" cy="38" r="18" fill="#334155"/>
  <path d="{torso_path}" fill="#475569"/>
  <path d="M{center_x-22} 84 L{arm_left} 145" stroke="#475569" stroke-width="12" stroke-linecap="round"/>
  <path d="M{center_x+22} 84 L{arm_right} 145" stroke="#475569" stroke-width="12" stroke-linecap="round"/>
  <path d="M{center_x-12} 142 L{leg_left} 205" stroke="#334155" stroke-width="13" stroke-linecap="round"/>
  <path d="M{center_x+12} 142 L{leg_right} 205" stroke="#334155" stroke-width="13" stroke-linecap="round"/>
  {guide}
  <text x="16" y="22" fill="#0f172a" font-family="Arial" font-size="12" font-weight="700">{sample["sample_id"]} - {view}</text>
</svg>
"""


def write_outputs(samples: list[dict[str, float | str]]) -> None:
    SAMPLE_DIR.mkdir(parents=True, exist_ok=True)

    headers = ["sample_id"]
    for field in FIELDS:
        headers.extend([f"predicted_{field}", f"actual_{field}"])

    with CSV_PATH.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=headers)
        writer.writeheader()
        for index, sample in enumerate(samples):
            row: dict[str, str | float] = {"sample_id": str(sample["sample_id"])}
            for field in FIELDS:
                actual = round(float(sample[field]), 2)
                row[f"actual_{field}"] = actual
                row[f"predicted_{field}"] = predicted_value(actual, index, field)
            writer.writerow(row)

            for view in ("front", "side", "back"):
                (SAMPLE_DIR / f"{sample['sample_id']}_{view}.svg").write_text(
                    svg_person(sample, view),
                    encoding="utf-8",
                )

    README_PATH.write_text(
        """# Synthetic Measurement Dataset

Dataset ini dibuat otomatis untuk pengujian lokal awal. Data ini tidak berasal dari orang asli.

Isi folder:

- `synthetic_measurement_eval.csv`: pasangan `predicted_*` dan `actual_*` untuk command MAE.
- `samples/*.svg`: contoh visual depan, samping, belakang dengan papan patokan A4.

Jalankan evaluasi:

```bash
php artisan measurement:evaluate storage/app/synthetic_measurements/synthetic_measurement_eval.csv
```

Catatan:

- Dataset ini menguji pipeline evaluasi dan kewajaran error sintetis.
- Klaim akurasi tubuh manusia nyata tetap membutuhkan dataset ground truth nyata atau dataset 3D publik.
""",
        encoding="utf-8",
    )


def main() -> None:
    samples = [base_sample(index) for index in range(12)]
    write_outputs(samples)
    print(f"Generated {len(samples)} synthetic samples")
    print(f"CSV: {CSV_PATH}")
    print(f"SVG samples: {SAMPLE_DIR}")


if __name__ == "__main__":
    main()
