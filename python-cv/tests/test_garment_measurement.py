import sys
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

import numpy as np


CV_DIR = Path(__file__).resolve().parents[1]
if str(CV_DIR) not in sys.path:
    sys.path.insert(0, str(CV_DIR))

try:
    import cv2
    HAS_REAL_CV = True
except ModuleNotFoundError:
    sys.modules.setdefault("cv2", MagicMock())
    HAS_REAL_CV = False

import garment_measurement as gm


def make_shirt_mask():
    """Synthetic flat-lay shirt: collar top-center, shoulders at top
    corners, armpit notches, sleeves flared to the sides, hem at bottom."""
    mask = np.zeros((400, 300), np.uint8)
    poly = np.array([
        [130, 20], [170, 20], [220, 40], [280, 90], [260, 110],
        [210, 90], [210, 320], [90, 320], [90, 90], [40, 110],
        [20, 90], [80, 40],
    ], dtype=np.int32)
    cv2.fillPoly(mask, [poly], 255)
    return mask


def make_pants_mask():
    """Synthetic flat-lay pants: waistband block, two leg blocks below,
    with a triangular notch carved out to form a crotch point."""
    mask = np.zeros((400, 300), np.uint8)
    cv2.rectangle(mask, (90, 20), (210, 160), 255, -1)
    cv2.rectangle(mask, (90, 150), (145, 380), 255, -1)
    cv2.rectangle(mask, (155, 150), (210, 380), 255, -1)
    notch = np.array([[145, 150], [155, 150], [150, 190]], dtype=np.int32)
    cv2.fillPoly(mask, [notch], 0)
    return mask


def make_skirt_mask():
    """Synthetic flat-lay skirt: narrower waistband at top, flared A-line
    hem at the bottom, no crotch fork."""
    mask = np.zeros((400, 300), np.uint8)
    poly = np.array([
        [110, 20], [190, 20], [220, 40], [260, 360], [40, 360], [80, 40],
    ], dtype=np.int32)
    cv2.fillPoly(mask, [poly], 255)
    return mask


@unittest.skipUnless(HAS_REAL_CV, "OpenCV is required")
class GarmentMeasurementGeometryTest(unittest.TestCase):
    def test_shirt_keypoints_locate_shoulders_above_armpits_not_on_sleeves(self):
        mask = make_shirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_shirt_keypoints(contour, mask)

        self.assertIsNotNone(keypoints["left_armpit"])
        self.assertIsNotNone(keypoints["right_armpit"])
        # Shoulder points must sit above the armpit line, not out on the
        # sleeve (a prior bug picked a point on the sleeve's slanted edge).
        self.assertLess(keypoints["left_shoulder"][1], keypoints["left_armpit"][1])
        self.assertLess(keypoints["right_shoulder"][1], keypoints["right_armpit"][1])
        # And clearly inward of the sleeve cuffs.
        self.assertGreater(keypoints["left_shoulder"][0], keypoints["left_cuff"][0])
        self.assertLess(keypoints["right_shoulder"][0], keypoints["right_cuff"][0])

    def test_shirt_measurements_are_positive_and_length_matches_bbox_height(self):
        mask = make_shirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_shirt_keypoints(contour, mask)
        scale = 10.0  # 10 px per cm
        data = gm.measure_shirt(keypoints, mask, scale)

        for field in ("chest", "shoulder_width", "shirt_length", "arm_length"):
            self.assertIn(field, data)
            self.assertGreater(data[field], 0)

        # Collar (y=20) to hem (y=320) is 300px -> 30cm at 10px/cm.
        self.assertAlmostEqual(data["shirt_length"], 30.0, delta=0.5)

    def test_shirt_measures_waist_hips_sleeve_opening_and_wrist(self):
        mask = make_shirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_shirt_keypoints(contour, mask)
        # A lower scale (fewer px/cm) than the other shirt tests use, so this
        # mask's short synthetic sleeve clears MIN_SLEEVE_LENGTH_FOR_BICEP_CM
        # and the cuff-opening scan actually runs.
        scale = 5.0
        data = gm.measure_shirt(keypoints, mask, scale)

        for field in ("shirt_waist", "shirt_hips", "sleeve_opening", "wrist"):
            self.assertIn(field, data)
            self.assertGreater(data[field], 0)

        # "Lobang tangan" and "wrist" are the same physical edge on a
        # flat-lay photo (see measure_shirt) - they must report identically.
        self.assertEqual(data["sleeve_opening"], data["wrist"])

        overlay = gm.build_overlay_geometry("shirt", keypoints, data, mask, scale)
        lines_by_field = {line["field"]: line for line in overlay["lines"]}
        for field in ("shirt_waist", "shirt_hips", "sleeve_opening", "wrist"):
            self.assertIn(field, lines_by_field)
            self.assertEqual(lines_by_field[field]["multiplier"], 2)
        # sleeve_opening and wrist point at the exact same two points -
        # deliberately no duplicate points for what's one measured edge.
        self.assertEqual(lines_by_field["sleeve_opening"]["point_ids"], lines_by_field["wrist"]["point_ids"])

    def test_pants_keypoints_find_crotch_between_waist_and_hem(self):
        mask = make_pants_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_pants_keypoints(contour, mask)

        self.assertIsNotNone(keypoints["crotch"])
        waist_y = (keypoints["left_waist"][1] + keypoints["right_waist"][1]) / 2
        hem_y = keypoints["bounding_box"][1] + keypoints["bounding_box"][3]
        self.assertGreater(keypoints["crotch"][1], waist_y)
        self.assertLess(keypoints["crotch"][1], hem_y)

    def test_pants_outseam_equals_inseam_plus_rise(self):
        mask = make_pants_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_pants_keypoints(contour, mask)
        scale = 10.0
        data = gm.measure_pants(keypoints, mask, scale)

        for field in ("pants_waist", "pants_hips", "inseam", "outseam", "rise"):
            self.assertIn(field, data)
            self.assertGreater(data[field], 0)

        # outseam (waist-to-hem) = inseam (crotch-to-hem) + rise (waist-to-crotch)
        self.assertAlmostEqual(data["outseam"], data["inseam"] + data["rise"], delta=0.5)

    def test_impossible_garment_measurements_flags_out_of_range_values(self):
        invalid = gm.impossible_garment_measurements({"chest": 5.0, "shirt_length": 60.0})
        fields = {item["field"] for item in invalid}
        self.assertIn("chest", fields)
        self.assertNotIn("shirt_length", fields)

    def test_skirt_keypoints_find_waist_narrower_than_hem(self):
        mask = make_skirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_skirt_keypoints(contour, mask)

        for key in ("left_waist", "right_waist", "left_hem", "right_hem"):
            self.assertIsNotNone(keypoints[key])

        waist_width = keypoints["right_waist"][0] - keypoints["left_waist"][0]
        hem_width = keypoints["right_hem"][0] - keypoints["left_hem"][0]
        self.assertLess(waist_width, hem_width)

    def test_skirt_length_matches_waist_to_hem_distance(self):
        mask = make_skirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_skirt_keypoints(contour, mask)
        scale = 10.0
        data = gm.measure_skirt(keypoints, mask, scale)

        for field in ("waist", "hips", "hem_width", "skirt_length"):
            self.assertIn(field, data)
            self.assertGreater(data[field], 0)

        # Waistband (y=20) to hem (y=360) is 340px -> 34cm at 10px/cm;
        # the top/bottom band scans sample a few rows of the sloped A-line
        # edges rather than the exact extreme y, so allow some slack.
        self.assertAlmostEqual(data["skirt_length"], 34.0, delta=3.0)

    def test_overlay_geometry_exposes_scale_and_point_referenced_lines(self):
        mask = make_shirt_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_shirt_keypoints(contour, mask)
        scale = 10.0
        data = gm.measure_shirt(keypoints, mask, scale)

        overlay = gm.build_overlay_geometry("shirt", keypoints, data, mask, scale)

        self.assertEqual(overlay["scale"], scale)
        point_ids = {p["id"] for p in overlay["points"]}
        self.assertIn("left_shoulder", point_ids)

        lines_by_field = {line["field"]: line for line in overlay["lines"]}
        self.assertIn("chest", lines_by_field)
        chest_line = lines_by_field["chest"]
        # A circumference field (flat width x2) must carry multiplier=2 so
        # the frontend can recompute value_cm after a drag without
        # duplicating this file's doubling convention.
        self.assertEqual(chest_line["multiplier"], 2)
        self.assertEqual(set(chest_line["point_ids"]), {"left_armpit", "right_armpit"})
        for point_id in chest_line["point_ids"]:
            self.assertIn(point_id, point_ids)

        shoulder_line = lines_by_field["shoulder_width"]
        self.assertEqual(shoulder_line["multiplier"], 1)

    def test_overlay_geometry_marks_derived_points_non_draggable(self):
        mask = make_pants_mask()
        contour = gm.extract_contour(mask)
        keypoints = gm.detect_pants_keypoints(contour, mask)
        scale = 10.0
        data = gm.measure_pants(keypoints, mask, scale)

        overlay = gm.build_overlay_geometry("pants", keypoints, data, mask, scale)
        points_by_id = {p["id"]: p for p in overlay["points"]}

        # waist_mid is the midpoint of left/right waist - the frontend must
        # recompute it from those sources rather than let it be dragged as
        # independent state.
        self.assertIn("waist_mid", points_by_id)
        self.assertFalse(points_by_id["waist_mid"]["draggable"])
        self.assertEqual(set(points_by_id["waist_mid"]["derived_from"]), {"left_waist", "right_waist"})

        # A real detected keypoint stays freely draggable.
        self.assertTrue(points_by_id["left_waist"]["draggable"])

        # The ankle line is a decorative position marker (real value is an
        # average of two separate per-leg scans, not this line's length) -
        # it must be flagged non-draggable so the frontend doesn't let a
        # drag silently produce a number that contradicts how it's computed.
        lines_by_field = {line["field"]: line for line in overlay["lines"]}
        self.assertIn("ankle", lines_by_field)
        self.assertFalse(lines_by_field["ankle"]["draggable"])


@unittest.skipUnless(HAS_REAL_CV, "OpenCV is required")
class ProcessGarmentMeasurementTest(unittest.TestCase):
    def _scale_result(self):
        return {
            "scale": 10.0,
            "contour": None,
            "area": 100.0,
            "source": "auto",
            "quality": 0.9,
            "axis_scales": [10.0, 10.0],
            "processing": {
                "plane_size_cm": [21.0, 29.7],
                "corners": [[0, 0], [210, 0], [210, 297], [0, 297]],
            },
        }

    def test_rejects_when_reference_marker_not_detected(self):
        image = np.zeros((100, 100, 3), dtype=np.uint8)
        with (
            patch.object(gm, "decode_image", return_value=image),
            patch.object(gm, "resize_for_measurement", side_effect=lambda img: img),
            patch.object(gm, "calculate_scale", return_value=None),
        ):
            result = gm.process_garment_measurement(b"fake", "shirt", "a4")

        self.assertFalse(result["success"])
        self.assertEqual(result["failed_reason"], "reference_not_detected")

    def test_successful_shirt_flow_returns_garment_measurements(self):
        image = np.zeros((100, 100, 3), dtype=np.uint8)
        mask = make_shirt_mask()
        with (
            patch.object(gm, "decode_image", return_value=image),
            patch.object(gm, "resize_for_measurement", side_effect=lambda img: img),
            patch.object(gm, "calculate_scale", return_value=self._scale_result()),
            patch.object(gm, "segment_garment", return_value=mask),
            # The synthetic mask's proportions are only built to exercise
            # keypoint-finding, not to be a realistic garment size at this
            # scale — sanity-range validation is covered separately by
            # test_impossible_garment_measurements_flags_out_of_range_values.
            patch.object(gm, "impossible_garment_measurements", return_value=[]),
        ):
            result = gm.process_garment_measurement(b"fake", "shirt", "a4")

        self.assertTrue(result["success"])
        self.assertEqual(result["garment_type"], "shirt")
        self.assertIn("chest", result["data"])
        self.assertEqual(result["measurement_method"], "garment_flat_lay")

        # The detected reference marker (which object, its measured vs
        # expected size) must be surfaced so the result page can show the
        # user what was actually read, not just used it internally.
        box = result["overlay"]["reference_box"]
        self.assertEqual(box["label"], "Kertas A4")
        self.assertEqual(box["measured_size_cm"], [21.0, 29.7])
        self.assertEqual(box["expected_size_cm"], [21.0, 29.7])
        self.assertTrue(box["size_ok"])
        self.assertEqual(len(box["corners"]), 4)

    def test_rejects_unknown_garment_type(self):
        result = gm.process_garment_measurement(b"fake", "hat", "a4")
        self.assertFalse(result["success"])


class ReferenceSizeMatchesTest(unittest.TestCase):
    def test_matches_within_tolerance(self):
        self.assertTrue(gm.reference_size_matches((8.6, 5.3), (8.56, 5.398)))

    def test_matches_when_orientation_is_swapped(self):
        # Marker photographed rotated 90 degrees - still a correct read.
        self.assertTrue(gm.reference_size_matches((5.4, 8.5), (8.56, 5.398)))

    def test_rejects_a_clearly_different_object(self):
        # e.g. selected "KTP" but an A4 sheet was actually in frame.
        self.assertFalse(gm.reference_size_matches((21.0, 29.7), (8.56, 5.398)))


if __name__ == "__main__":
    unittest.main()
