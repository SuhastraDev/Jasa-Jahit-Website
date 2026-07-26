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
    import mediapipe
    HAS_REAL_CV = True
except ModuleNotFoundError:
    sys.modules.setdefault("cv2", MagicMock())
    sys.modules.setdefault("mediapipe", MagicMock())
    HAS_REAL_CV = False

import measurement


def pose(keypoints):
    return {"keypoints": keypoints, "confidence": 0.96}


def front_pose():
    return {
        "nose": (100, 40, 1),
        "left_shoulder": (75, 80, 1),
        "right_shoulder": (125, 80, 1),
        "left_elbow": (50, 135, 1),
        "right_elbow": (150, 135, 1),
        "left_wrist": (48, 190, 1),
        "right_wrist": (152, 190, 1),
        "left_hip": (82, 210, 1),
        "right_hip": (118, 210, 1),
        "left_knee": (85, 300, 1),
        "right_knee": (115, 300, 1),
        "left_ankle": (87, 390, 1),
        "right_ankle": (113, 390, 1),
    }


def side_pose():
    return {
        "nose": (99, 40, 1),
        "left_shoulder": (92, 80, 1),
        "right_shoulder": (102, 82, 1),
        "left_elbow": (50, 135, 1),
        "right_elbow": (150, 135, 1),
        "left_wrist": (67, 190, 1),
        "right_wrist": (137, 190, 1),
        "left_hip": (94, 210, 1),
        "right_hip": (102, 210, 1),
        "left_knee": (96, 300, 1),
        "right_knee": (101, 300, 1),
        "left_ankle": (97, 390, 1),
        "right_ankle": (100, 390, 1),
    }


def make_front_mask():
    mask = np.zeros((420, 220), dtype=np.uint8)
    mask[30:80, 90:111] = 255
    mask[80:221, 70:131] = 255
    for y in range(90, 205):
        if y <= 135:
            left_center = 75 + (50 - 75) * ((y - 80) / 55)
            right_center = 125 + (150 - 125) * ((y - 80) / 55)
        else:
            left_center = 50 + (48 - 50) * ((y - 135) / 55)
            right_center = 150 + (152 - 150) * ((y - 135) / 55)
        mask[y, int(left_center - 7):int(left_center + 8)] = 255
        mask[y, int(right_center - 7):int(right_center + 8)] = 255
    mask[221:400, 75:96] = 255
    mask[221:400, 105:126] = 255
    return mask


def make_side_mask():
    mask = np.zeros((420, 220), dtype=np.uint8)
    mask[30:80, 91:108] = 255
    mask[80:221, 84:120] = 255
    for y in range(90, 205):
        if y <= 135:
            left_center = 92 + (50 - 92) * ((y - 80) / 55)
            right_center = 102 + (150 - 102) * ((y - 80) / 55)
        else:
            left_center = 50 + (67 - 50) * ((y - 135) / 55)
            right_center = 150 + (137 - 150) * ((y - 135) / 55)
        mask[y, int(left_center - 7):int(left_center + 8)] = 255
        mask[y, int(right_center - 7):int(right_center + 8)] = 255
    mask[221:400, 89:110] = 255
    return mask


class MeasurementGeometryTest(unittest.TestCase):
    @unittest.skipUnless(HAS_REAL_CV, "OpenCV/MediaPipe tidak tersedia di runtime lokal")
    def test_reference_detector_prefers_a4_proportion_at_body_side(self):
        image = np.zeros((500, 400, 3), dtype=np.uint8)
        cv2.rectangle(image, (130, 90), (270, 430), (255, 255, 255), 5)
        cv2.rectangle(image, (20, 150), (104, 269), (255, 255, 255), 5)

        contour = measurement.detect_reference_object(image, 21.0, 29.7)

        self.assertIsNotNone(contour)
        x, _, w, _ = cv2.boundingRect(contour)
        self.assertLess(x + w, 130)

    @unittest.skipUnless(HAS_REAL_CV, "OpenCV/MediaPipe tidak tersedia di runtime lokal")
    def test_pose_component_is_selected_instead_of_larger_background_region(self):
        mask = np.zeros((240, 180), dtype=np.uint8)
        mask[10:70, 5:175] = 255
        mask[85:225, 70:111] = 255
        keypoints = front_pose()
        scaled_keypoints = {
            name: (point[0] * 0.9, point[1] * 0.55, point[2])
            for name, point in keypoints.items()
        }

        isolated = measurement.isolate_pose_component(mask, scaled_keypoints)

        self.assertEqual(0, int(isolated[30, 30]))
        self.assertEqual(255, int(isolated[150, 90]))

    def test_process_measurement_samples_each_limb_instead_of_body_midline(self):
        dummy_image = np.zeros((420, 220, 3), dtype=np.uint8)
        poses = [pose(front_pose()), pose(side_pose()), pose(front_pose())]
        masks = [make_front_mask(), make_side_mask(), make_front_mask()]
        scale_result = {
            "scale": 2.0,
            "contour": None,
            "area": 1200.0,
            "source": "manual",
            "quality": 1.0,
            "axis_scales": [2.0, 2.0],
        }

        with (
            patch.object(measurement, "decode_image", side_effect=[dummy_image.copy() for _ in range(3)]),
            patch.object(measurement, "resize_for_measurement", side_effect=lambda image: image),
            patch.object(measurement, "calculate_scale", side_effect=[scale_result.copy() for _ in range(3)]),
            patch.object(measurement, "detect_pose", side_effect=poses),
            patch.object(measurement, "build_body_mask", side_effect=masks),
            patch.object(measurement, "largest_body_bounds", return_value=(40, 30, 120, 360)),
        ):
            result = measurement.process_measurement(
                b"front",
                b"side",
                b"back",
                "a4",
                21.0,
                29.7,
            )

        self.assertTrue(result["success"], result.get("error"))
        self.assertAlmostEqual(76.2, result["data"]["chest"], delta=3.0)
        self.assertAlmostEqual(31.4, result["data"]["thigh"], delta=3.0)
        self.assertAlmostEqual(23.6, result["data"]["upper_arm"], delta=3.0)
        self.assertAlmostEqual(23.6, result["data"]["wrist"], delta=3.0)
        self.assertEqual(result["quality_score"], result["confidence"])
        self.assertLessEqual(result["per_field_confidence"]["thigh"], 0.9)
        self.assertGreater(result["per_field_confidence"]["thigh"], 0.5)

    def test_process_measurement_rejects_reference_box_with_wrong_proportion(self):
        dummy_image = np.zeros((420, 220, 3), dtype=np.uint8)
        distorted_scale = {
            "scale": 3.0,
            "contour": None,
            "area": 1200.0,
            "source": "manual",
            "quality": 0.55,
            "axis_scales": [4.0, 2.2],
        }

        with (
            patch.object(measurement, "decode_image", side_effect=[dummy_image.copy() for _ in range(3)]),
            patch.object(measurement, "resize_for_measurement", side_effect=lambda image: image),
            patch.object(measurement, "calculate_scale", return_value=distorted_scale),
        ):
            result = measurement.process_measurement(
                b"front",
                b"side",
                b"back",
                "a4",
                21.0,
                29.7,
            )

        self.assertFalse(result["success"])
        self.assertEqual("front", result["failed_view"])
        self.assertEqual("invalid_reference_proportion", result["failed_reason"])


if __name__ == "__main__":
    unittest.main()
