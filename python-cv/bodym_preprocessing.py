"""Deterministic silhouette preprocessing shared by BodyM training and inference."""

from __future__ import annotations

from dataclasses import dataclass
import math
from typing import Any

import numpy as np
from PIL import Image


PREPROCESSING_VERSION = "bodym-preprocess.v1"
CANVAS_WIDTH = 192
CANVAS_HEIGHT = 256
BODY_TARGET_HEIGHT = 240
BODY_MAX_WIDTH = 176
VALID_VIEWS = {"front", "side"}
PROFILE_POINTS = 32
PROFILE_FRACTIONS = tuple(np.linspace(0.05, 0.95, PROFILE_POINTS).tolist())
ANATOMY_BANDS = {
    "neck": (0.10, 0.17, "median"),
    "shoulder": (0.18, 0.27, "max"),
    "chest": (0.28, 0.38, "median"),
    "waist": (0.43, 0.53, "min"),
    "hip": (0.54, 0.64, "max"),
    "thigh": (0.65, 0.74, "median"),
    "calf": (0.77, 0.86, "median"),
    "ankle": (0.89, 0.96, "median"),
}


class SilhouetteValidationError(ValueError):
    """A user-correctable silhouette error with a stable diagnostic code."""

    def __init__(self, code: str, message: str, details: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.code = code
        self.details = details or {}


@dataclass(frozen=True)
class PreprocessedSilhouette:
    mask: np.ndarray
    view: str
    body_bbox: tuple[int, int, int, int]
    cm_per_pixel: float
    body_height_cm: float
    diagnostics: dict[str, Any]


@dataclass(frozen=True)
class FeatureVector:
    names: tuple[str, ...]
    values: tuple[float, ...]

    def as_dict(self) -> dict[str, float]:
        return dict(zip(self.names, self.values, strict=True))


def _as_binary(mask: np.ndarray) -> np.ndarray:
    array = np.asarray(mask)
    if array.ndim == 3:
        array = array[..., :3].mean(axis=2)
    if array.ndim != 2:
        raise SilhouetteValidationError(
            "invalid_dimensions",
            "Mask siluet harus berupa gambar grayscale atau RGB.",
            {"shape": list(array.shape)},
        )
    return array.astype(np.float32) >= 127.5


def _robust_bbox(binary: np.ndarray) -> tuple[int, int, int, int]:
    row_support = binary.sum(axis=1)
    column_support = binary.sum(axis=0)
    valid_rows = np.flatnonzero(row_support >= 2)
    valid_columns = np.flatnonzero(column_support >= 2)
    if valid_rows.size == 0 or valid_columns.size == 0:
        raise SilhouetteValidationError(
            "empty_mask",
            "Siluet tubuh tidak ditemukan pada mask.",
        )

    x0 = int(valid_columns[0])
    x1 = int(valid_columns[-1])
    y0 = int(valid_rows[0])
    y1 = int(valid_rows[-1])
    return x0, y0, x1 - x0 + 1, y1 - y0 + 1


def _resize_to_canvas(crop: np.ndarray) -> np.ndarray:
    source_height, source_width = crop.shape
    scale = min(BODY_TARGET_HEIGHT / source_height, BODY_MAX_WIDTH / source_width)
    target_width = max(1, int(round(source_width * scale)))
    target_height = max(1, int(round(source_height * scale)))
    image = Image.fromarray((crop.astype(np.uint8) * 255), mode="L")
    resized = np.asarray(
        image.resize((target_width, target_height), resample=Image.Resampling.NEAREST)
    ) >= 128

    canvas = np.zeros((CANVAS_HEIGHT, CANVAS_WIDTH), dtype=bool)
    x = (CANVAS_WIDTH - target_width) // 2
    y = (CANVAS_HEIGHT - target_height) // 2
    canvas[y : y + target_height, x : x + target_width] = resized
    return canvas


def preprocess_silhouette(
    mask: np.ndarray,
    *,
    view: str,
    cm_per_pixel: float,
) -> PreprocessedSilhouette:
    """Normalize one binary body silhouette onto the frozen BodyM v1 canvas."""

    if view not in VALID_VIEWS:
        raise SilhouetteValidationError(
            "invalid_view",
            "View siluet harus front atau side.",
            {"view": view},
        )
    if not np.isfinite(cm_per_pixel) or cm_per_pixel <= 0:
        raise SilhouetteValidationError(
            "invalid_scale",
            "Skala sentimeter per pixel harus lebih besar dari nol.",
            {"cm_per_pixel": cm_per_pixel},
        )

    binary = _as_binary(mask)
    foreground_pixels = int(binary.sum())
    if foreground_pixels < 16:
        raise SilhouetteValidationError(
            "empty_mask",
            "Siluet tubuh tidak ditemukan pada mask.",
            {"foreground_pixels": foreground_pixels},
        )

    x, y, width, height = _robust_bbox(binary)
    if width < 4 or height < 20:
        raise SilhouetteValidationError(
            "body_too_small",
            "Siluet tubuh terlalu kecil untuk diekstrak.",
            {"bbox": [x, y, width, height]},
        )

    crop = binary[y : y + height, x : x + width]
    row_coverage = float(np.mean(crop.sum(axis=1) >= 2))
    if row_coverage < 0.85:
        raise SilhouetteValidationError(
            "incomplete_silhouette",
            "Siluet tubuh terputus atau tidak penuh dari kepala sampai kaki.",
            {"row_coverage": round(row_coverage, 8), "minimum": 0.85},
        )

    body_height_cm = round(height * float(cm_per_pixel), 8)
    if body_height_cm < 50 or body_height_cm > 260:
        raise SilhouetteValidationError(
            "implausible_body_scale",
            "Skala menghasilkan tinggi tubuh yang tidak masuk akal.",
            {"body_height_cm": body_height_cm},
        )

    normalized = _resize_to_canvas(crop)
    diagnostics = {
        "preprocessing_version": PREPROCESSING_VERSION,
        "source_shape": [int(binary.shape[0]), int(binary.shape[1])],
        "foreground_pixels": foreground_pixels,
        "foreground_ratio": round(foreground_pixels / binary.size, 8),
        "bbox_aspect_ratio": round(width / height, 8),
        "row_coverage": round(row_coverage, 8),
        "normalized_foreground_pixels": int(normalized.sum()),
    }
    return PreprocessedSilhouette(
        mask=normalized,
        view=view,
        body_bbox=(x, y, width, height),
        cm_per_pixel=round(float(cm_per_pixel), 12),
        body_height_cm=body_height_cm,
        diagnostics=diagnostics,
    )


def _mask_bbox(mask: np.ndarray) -> tuple[int, int, int, int]:
    ys, xs = np.nonzero(mask)
    if ys.size == 0:
        raise SilhouetteValidationError("empty_mask", "Siluet hasil normalisasi kosong.")
    x0, x1 = int(xs.min()), int(xs.max())
    y0, y1 = int(ys.min()), int(ys.max())
    return x0, y0, x1 - x0 + 1, y1 - y0 + 1


def _runs(row: np.ndarray) -> list[tuple[int, int]]:
    padded = np.pad(row.astype(np.int8), (1, 1))
    changes = np.diff(padded)
    starts = np.flatnonzero(changes == 1)
    ends = np.flatnonzero(changes == -1) - 1
    return list(zip(starts.tolist(), ends.tolist(), strict=True))


def _row_width(row: np.ndarray, *, central: bool, center_x: float) -> int:
    components = _runs(row)
    if not components:
        return 0
    if not central:
        return components[-1][1] - components[0][0] + 1
    start, end = min(
        components,
        key=lambda component: (
            abs(((component[0] + component[1]) / 2) - center_x),
            -(component[1] - component[0] + 1),
        ),
    )
    return end - start + 1


def _profile(mask: np.ndarray, fractions: tuple[float, ...], *, central: bool) -> np.ndarray:
    x, y, width, height = _mask_bbox(mask)
    center_x = x + ((width - 1) / 2)
    values = []
    for fraction in fractions:
        row_index = min(mask.shape[0] - 1, y + int(round(fraction * (height - 1))))
        values.append(_row_width(mask[row_index], central=central, center_x=center_x) / height)
    return np.asarray(values, dtype=np.float64)


def _band_width(mask: np.ndarray, start: float, end: float, reducer: str) -> float:
    x, y, width, height = _mask_bbox(mask)
    center_x = x + ((width - 1) / 2)
    first_row = y + int(round(start * (height - 1)))
    last_row = y + int(round(end * (height - 1)))
    widths = [
        _row_width(mask[row], central=True, center_x=center_x) / height
        for row in range(first_row, last_row + 1)
    ]
    nonzero = np.asarray([value for value in widths if value > 0], dtype=np.float64)
    if nonzero.size == 0:
        raise SilhouetteValidationError(
            "missing_anatomy_band",
            "Siluet kosong pada salah satu area anatomi.",
            {"start": start, "end": end},
        )
    if reducer == "min":
        return float(nonzero.min())
    if reducer == "max":
        return float(nonzero.max())
    return float(np.median(nonzero))


def _area_ratio(mask: np.ndarray) -> float:
    _, _, width, height = _mask_bbox(mask)
    return float(mask.sum() / (width * height))


def _ellipse_circumference(width: float, depth: float) -> float:
    a = max(width, depth) / 2
    b = min(width, depth) / 2
    if a <= 0 or b <= 0:
        return 0.0
    h = ((a - b) ** 2) / ((a + b) ** 2)
    return math.pi * (a + b) * (1 + (3 * h) / (10 + math.sqrt(4 - (3 * h))))


def feature_names() -> tuple[str, ...]:
    names = [
        "body_height_mean_cm",
        "body_height_difference_ratio",
        "front_area_ratio",
        "side_area_ratio",
        "front_bbox_aspect_ratio",
        "side_bbox_aspect_ratio",
        "front_body_height_cm",
        "side_body_height_cm",
    ]
    for prefix in (
        "front_width_norm",
        "front_center_width_norm",
        "side_depth_norm",
        "side_center_depth_norm",
        "front_width_cm",
        "side_depth_cm",
    ):
        names.extend(f"{prefix}_{index:02d}" for index in range(PROFILE_POINTS))
    for label in ANATOMY_BANDS:
        names.extend(
            (
                f"front_{label}_width_cm",
                f"side_{label}_depth_cm",
                f"ellipse_{label}_circumference_cm",
            )
        )
    return tuple(names)


def scale_from_known_height(mask: np.ndarray, height_cm: float) -> float:
    if not np.isfinite(height_cm) or height_cm <= 0:
        raise SilhouetteValidationError(
            "invalid_known_height",
            "Tinggi ground truth harus lebih besar dari nol.",
            {"height_cm": height_cm},
        )
    binary = _as_binary(mask)
    _, _, _, body_height_pixels = _robust_bbox(binary)
    return float(height_cm) / body_height_pixels


def extract_pair_features(
    front: PreprocessedSilhouette,
    side: PreprocessedSilhouette,
) -> FeatureVector:
    """Extract a stable ordered vector from one front/side silhouette pair."""

    if front.view != "front" or side.view != "side":
        raise SilhouetteValidationError(
            "view_pair_mismatch",
            "Ekstraksi fitur membutuhkan pasangan front lalu side.",
        )

    front_full = _profile(front.mask, PROFILE_FRACTIONS, central=False)
    front_central = _profile(front.mask, PROFILE_FRACTIONS, central=True)
    side_full = _profile(side.mask, PROFILE_FRACTIONS, central=False)
    side_central = _profile(side.mask, PROFILE_FRACTIONS, central=True)

    names: list[str] = list(feature_names()[:8])
    mean_height = (front.body_height_cm + side.body_height_cm) / 2
    values: list[float] = [
        mean_height,
        abs(front.body_height_cm - side.body_height_cm) / mean_height,
        _area_ratio(front.mask),
        _area_ratio(side.mask),
        float(front.diagnostics["bbox_aspect_ratio"]),
        float(side.diagnostics["bbox_aspect_ratio"]),
        front.body_height_cm,
        side.body_height_cm,
    ]

    profile_groups = (
        ("front_width_norm", front_full),
        ("front_center_width_norm", front_central),
        ("side_depth_norm", side_full),
        ("side_center_depth_norm", side_central),
        ("front_width_cm", front_full * front.body_height_cm),
        ("side_depth_cm", side_full * side.body_height_cm),
    )
    for prefix, profile_values in profile_groups:
        for index, value in enumerate(profile_values):
            names.append(f"{prefix}_{index:02d}")
            values.append(float(value))

    for label, (start, end, reducer) in ANATOMY_BANDS.items():
        front_width = _band_width(front.mask, start, end, reducer) * front.body_height_cm
        side_depth = _band_width(side.mask, start, end, reducer) * side.body_height_cm
        names.extend(
            (
                f"front_{label}_width_cm",
                f"side_{label}_depth_cm",
                f"ellipse_{label}_circumference_cm",
            )
        )
        values.extend((front_width, side_depth, _ellipse_circumference(front_width, side_depth)))

    expected_names = feature_names()
    if tuple(names) != expected_names:
        raise RuntimeError("BodyM feature order drift detected")
    return FeatureVector(
        names=expected_names,
        values=tuple(round(float(value), 8) for value in values),
    )
