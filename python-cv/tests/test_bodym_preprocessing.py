import sys
import unittest
from pathlib import Path

import numpy as np


PYTHON_CV = Path(__file__).resolve().parents[1]
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_preprocessing import (  # noqa: E402
    CANVAS_HEIGHT,
    CANVAS_WIDTH,
    SilhouetteValidationError,
    extract_pair_features,
    preprocess_silhouette,
)


class BodyMPreprocessingTest(unittest.TestCase):
    def test_same_mask_produces_identical_normalized_output(self) -> None:
        mask = np.zeros((120, 100), dtype=np.uint8)
        mask[10:110, 35:65] = 255
        mask[55:70, 20:80] = 255
        mask[2, 2] = 255

        first = preprocess_silhouette(mask, view="front", cm_per_pixel=1.7)
        second = preprocess_silhouette(mask, view="front", cm_per_pixel=1.7)

        self.assertTrue(np.array_equal(first.mask, second.mask))
        self.assertEqual(first.mask.shape, (CANVAS_HEIGHT, CANVAS_WIDTH))
        self.assertEqual(first.body_bbox, (20, 10, 60, 100))
        self.assertEqual(first.body_height_cm, 170.0)
        self.assertEqual(first.diagnostics, second.diagnostics)
        self.assertEqual(set(np.unique(first.mask)), {False, True})

    def test_blank_mask_returns_explicit_error_code(self) -> None:
        with self.assertRaises(SilhouetteValidationError) as context:
            preprocess_silhouette(
                np.zeros((120, 100), dtype=np.uint8),
                view="side",
                cm_per_pixel=0.2,
            )

        self.assertEqual(context.exception.code, "empty_mask")

    def test_invalid_scale_returns_explicit_error_code(self) -> None:
        mask = np.zeros((120, 100), dtype=np.uint8)
        mask[10:110, 35:65] = 255

        with self.assertRaises(SilhouetteValidationError) as context:
            preprocess_silhouette(mask, view="front", cm_per_pixel=0)

        self.assertEqual(context.exception.code, "invalid_scale")

    def test_fragmented_body_returns_incomplete_silhouette_error(self) -> None:
        mask = np.zeros((120, 100), dtype=np.uint8)
        mask[10:35, 35:65] = 255
        mask[55:80, 30:70] = 255
        mask[95:110, 35:65] = 255

        with self.assertRaises(SilhouetteValidationError) as context:
            preprocess_silhouette(mask, view="front", cm_per_pixel=1.7)

        self.assertEqual(context.exception.code, "incomplete_silhouette")
        self.assertLess(context.exception.details["row_coverage"], 0.85)

    def test_pair_features_preserve_known_physical_widths_and_order(self) -> None:
        front_mask = np.zeros((120, 100), dtype=np.uint8)
        side_mask = np.zeros((120, 100), dtype=np.uint8)
        front_mask[10:110, 30:70] = 255
        side_mask[10:110, 40:60] = 255
        front = preprocess_silhouette(front_mask, view="front", cm_per_pixel=1.5)
        side = preprocess_silhouette(side_mask, view="side", cm_per_pixel=1.5)

        first = extract_pair_features(front, side)
        second = extract_pair_features(front, side)

        self.assertEqual(first.names, second.names)
        self.assertEqual(first.values, second.values)
        self.assertEqual(first.names[:5], (
            "body_height_mean_cm",
            "body_height_difference_ratio",
            "front_area_ratio",
            "side_area_ratio",
            "front_bbox_aspect_ratio",
        ))
        self.assertAlmostEqual(first.as_dict()["front_chest_width_cm"], 60.0, places=6)
        self.assertAlmostEqual(first.as_dict()["side_chest_depth_cm"], 30.0, places=6)
        self.assertGreater(first.as_dict()["ellipse_chest_circumference_cm"], 130.0)


if __name__ == "__main__":
    unittest.main()
