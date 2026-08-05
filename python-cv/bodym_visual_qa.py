"""Visual quality-assurance artifacts for BodyM preprocessing."""

from __future__ import annotations

import csv
import html
import json
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFont

from bodym_preprocessing import (
    ANATOMY_BANDS,
    CANVAS_HEIGHT,
    CANVAS_WIDTH,
    FeatureVector,
    PreprocessedSilhouette,
    extract_pair_features,
    preprocess_silhouette,
    scale_from_known_height,
)


QA_WIDTH = 920
QA_HEIGHT = 690
PANEL_SCALE = 2
PANEL_TOP = 104
PANEL_X = {"front": 34, "side": 478}
COLORS = {
    "background": (241, 245, 249),
    "header": (15, 23, 42),
    "panel": (15, 23, 42),
    "body": (226, 232, 240),
    "contour": (34, 211, 238),
    "text": (15, 23, 42),
    "muted": (71, 85, 105),
}
ANATOMY_COLORS = {
    "neck": (236, 72, 153),
    "shoulder": (168, 85, 247),
    "chest": (37, 99, 235),
    "waist": (14, 165, 233),
    "hip": (16, 185, 129),
    "thigh": (234, 179, 8),
    "calf": (249, 115, 22),
    "ankle": (239, 68, 68),
}


def _edge(mask: np.ndarray) -> np.ndarray:
    interior = mask.copy()
    interior[1:, :] &= mask[:-1, :]
    interior[:-1, :] &= mask[1:, :]
    interior[:, 1:] &= mask[:, :-1]
    interior[:, :-1] &= mask[:, 1:]
    return mask & ~interior


def _render_mask(mask: np.ndarray) -> Image.Image:
    rgb = np.empty((CANVAS_HEIGHT, CANVAS_WIDTH, 3), dtype=np.uint8)
    rgb[:] = COLORS["panel"]
    rgb[mask] = COLORS["body"]
    rgb[_edge(mask)] = COLORS["contour"]
    return Image.fromarray(rgb, mode="RGB").resize(
        (CANVAS_WIDTH * PANEL_SCALE, CANVAS_HEIGHT * PANEL_SCALE),
        Image.Resampling.NEAREST,
    )


def _mask_bbox(mask: np.ndarray) -> tuple[int, int, int, int]:
    ys, xs = np.nonzero(mask)
    return int(xs.min()), int(ys.min()), int(xs.max()), int(ys.max())


def _draw_anatomy(draw: ImageDraw.ImageDraw, result: PreprocessedSilhouette) -> None:
    panel_x = PANEL_X[result.view]
    x0, y0, x1, y1 = _mask_bbox(result.mask)
    body_height = y1 - y0 + 1
    for label, (start, end, _) in ANATOMY_BANDS.items():
        fraction = (start + end) / 2
        row = min(result.mask.shape[0] - 1, y0 + int(round(fraction * (body_height - 1))))
        xs = np.flatnonzero(result.mask[row])
        if xs.size == 0:
            continue
        left = panel_x + int(xs.min()) * PANEL_SCALE
        right = panel_x + int(xs.max() + 1) * PANEL_SCALE
        y = PANEL_TOP + row * PANEL_SCALE
        color = ANATOMY_COLORS[label]
        draw.line((left, y, right, y), fill=color, width=4)
        draw.ellipse((left - 4, y - 4, left + 4, y + 4), fill=color)
        draw.ellipse((right - 4, y - 4, right + 4, y + 4), fill=color)
        draw.text((panel_x + 6, y - 12), label, fill=color)


def render_visual_qa(
    front: PreprocessedSilhouette,
    side: PreprocessedSilhouette,
    features: FeatureVector,
    output_path: Path,
    *,
    title: str,
) -> Path:
    output_path = Path(output_path).resolve()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    image = Image.new("RGB", (QA_WIDTH, QA_HEIGHT), COLORS["background"])
    draw = ImageDraw.Draw(image)
    font = ImageFont.load_default()

    draw.rectangle((0, 0, QA_WIDTH, 74), fill=COLORS["header"])
    draw.text((28, 20), title, fill=(255, 255, 255), font=font)
    draw.text(
        (28, 44),
        "Kontur cyan, anchor anatomi berwarna, garis menunjukkan lebar/depth siluet",
        fill=(203, 213, 225),
        font=font,
    )

    for result in (front, side):
        x = PANEL_X[result.view]
        draw.rounded_rectangle(
            (x - 10, PANEL_TOP - 28, x + (CANVAS_WIDTH * PANEL_SCALE) + 10, PANEL_TOP + 522),
            radius=8,
            fill=(255, 255, 255),
            outline=(203, 213, 225),
            width=2,
        )
        draw.text((x, PANEL_TOP - 22), result.view.upper(), fill=COLORS["text"], font=font)
        image.paste(_render_mask(result.mask), (x, PANEL_TOP))
        _draw_anatomy(draw, result)

    feature_map = features.as_dict()
    footer = (
        f"height front/side: {front.body_height_cm:.2f}/{side.body_height_cm:.2f} cm  |  "
        f"chest W/D: {feature_map['front_chest_width_cm']:.2f}/"
        f"{feature_map['side_chest_depth_cm']:.2f} cm  |  features: {len(features.names)}"
    )
    draw.text((28, 659), footer, fill=COLORS["muted"], font=font)
    image.save(output_path, format="PNG", optimize=False)
    return output_path


def _read_csv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        return list(csv.DictReader(handle))


def build_qa_gallery(
    dataset_root: Path,
    output_dir: Path,
    *,
    samples_per_split: int = 3,
    splits: tuple[str, ...] = ("train", "testA", "testB"),
) -> dict[str, object]:
    dataset_root = Path(dataset_root).resolve()
    output_dir = Path(output_dir).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)
    entries: list[dict[str, str]] = []

    for split_name in splits:
        split_root = dataset_root / split_name
        metadata = {row["subject_id"]: row for row in _read_csv(split_root / "hwg_metadata.csv")}
        relations = sorted(
            _read_csv(split_root / "subject_to_photo_map.csv"),
            key=lambda row: (row["subject_id"], row["photo_id"]),
        )[:samples_per_split]
        for index, relation in enumerate(relations, start=1):
            subject_id = relation["subject_id"]
            photo_id = relation["photo_id"]
            height_cm = float(metadata[subject_id]["height_cm"])
            with Image.open(split_root / "mask" / f"{photo_id}.png") as source:
                front_mask = np.asarray(source.convert("L"), dtype=np.uint8)
            with Image.open(split_root / "mask_left" / f"{photo_id}.png") as source:
                side_mask = np.asarray(source.convert("L"), dtype=np.uint8)
            front = preprocess_silhouette(
                front_mask,
                view="front",
                cm_per_pixel=scale_from_known_height(front_mask, height_cm),
            )
            side = preprocess_silhouette(
                side_mask,
                view="side",
                cm_per_pixel=scale_from_known_height(side_mask, height_cm),
            )
            vector = extract_pair_features(front, side)
            filename = f"{split_name}-{index:02d}-{photo_id}.png"
            render_visual_qa(
                front,
                side,
                vector,
                output_dir / filename,
                title=f"BodyM QA | {split_name} | sample {index}",
            )
            entries.append({"split": split_name, "photo_id": photo_id, "file": filename})

    cards = "\n".join(
        f'<article><h2>{html.escape(entry["split"])}</h2><img src="{html.escape(entry["file"])}" '
        f'alt="BodyM QA {html.escape(entry["split"])}"></article>'
        for entry in entries
    )
    index_html = f"""<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BodyM Visual QA</title><style>
body{{font-family:Arial,sans-serif;margin:0;background:#e2e8f0;color:#0f172a}}main{{max-width:1100px;margin:auto;padding:24px}}
article{{background:white;border:1px solid #cbd5e1;margin:0 0 20px;padding:16px;border-radius:8px}}img{{display:block;width:100%;height:auto}}
</style></head><body><main><h1>BodyM Visual QA</h1>{cards}</main></body></html>"""
    (output_dir / "index.html").write_text(index_html, encoding="utf-8")
    summary = {"samples_per_split": samples_per_split, "sample_count": len(entries), "entries": entries}
    (output_dir / "summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=True) + "\n",
        encoding="utf-8",
    )
    return summary
