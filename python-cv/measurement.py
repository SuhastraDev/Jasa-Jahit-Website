"""
Multi-view body measurement estimation for ZRINTTAILOR.

The service requires front, side, and back photos. Each photo must contain a
standing calibration marker. The system rejects the request when the marker or
body pose cannot be detected, because guessing scale from image height makes
tailoring measurements unstable.
"""
import math
import os
import time
import json
import urllib.request

import cv2
import mediapipe as mp
import numpy as np

from bodym_inference import BodyMInferenceError, get_bodym_service
from utils import euclidean_distance, get_reference_dimensions, midpoint, pixel_to_cm

MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "pose_landmarker_lite.task")
if not os.path.exists(MODEL_PATH):
    url = "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task"
    urllib.request.urlretrieve(url, MODEL_PATH)

_POSE_LANDMARKER = None

MEASUREMENT_LIMITS_CM = {
    "neck": (20, 70),
    "chest": (45, 180),
    "waist": (40, 180),
    "hips": (45, 190),
    "shoulder_width": (20, 75),
    "shirt_length": (35, 120),
    "arm_length": (25, 95),
    "upper_arm": (15, 80),
    "wrist": (8, 35),
    "height": (90, 230),
    "pants_waist": (40, 180),
    "pants_hips": (45, 190),
    "thigh": (25, 110),
    "knee": (20, 80),
    "calf": (18, 80),
    "ankle": (10, 45),
    "inseam": (35, 120),
    "outseam": (55, 140),
    "rise": (12, 55),
}

MEASUREMENT_LABELS = {
    "neck": "leher",
    "chest": "dada",
    "waist": "pinggang",
    "hips": "pinggul",
    "shoulder_width": "lebar bahu",
    "shirt_length": "panjang baju",
    "arm_length": "panjang lengan",
    "upper_arm": "lengan atas",
    "wrist": "pergelangan",
    "height": "tinggi",
    "pants_waist": "pinggang celana",
    "pants_hips": "pinggul celana",
    "thigh": "paha",
    "knee": "lutut",
    "calf": "betis",
    "ankle": "bukaan bawah",
    "inseam": "inseam",
    "outseam": "outseam",
    "rise": "rise/pesak",
}

BODYM_RESPONSE_CONTRACT_VERSION = "bodym-response.v1"
BODYM_TO_LEGACY_FIELDS = {
    "ankle_girth": ("ankle",),
    "arm_length": ("arm_length",),
    "bicep_girth": ("upper_arm",),
    "calf_girth": ("calf",),
    "chest_girth": ("chest",),
    "height": ("height",),
    "hip_girth": ("hips", "pants_hips"),
    "shoulder_breadth": ("shoulder_width",),
    "thigh_girth": ("thigh",),
    "waist_girth": ("waist", "pants_waist"),
    "wrist_girth": ("wrist",),
}


def bodym_enabled():
    return os.getenv("BODYM_ENABLED", "false").lower() in ("1", "true", "yes", "on")


def decode_image(image_bytes):
    nparr = np.frombuffer(image_bytes, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)


def resize_for_measurement(image, max_dimension=1280):
    h, w = image.shape[:2]
    largest = max(h, w)
    if largest <= max_dimension:
        return image

    scale = max_dimension / largest
    target_w = max(1, int(round(w * scale)))
    target_h = max(1, int(round(h * scale)))
    return cv2.resize(image, (target_w, target_h), interpolation=cv2.INTER_AREA)


def detect_reference_object(image, real_width, real_height):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blurred, 50, 150)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    edges = cv2.morphologyEx(edges, cv2.MORPH_CLOSE, kernel, iterations=2)
    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    best = None
    best_score = 0.0
    image_area = image.shape[0] * image.shape[1]
    image_h, image_w = image.shape[:2]
    expected_ratio = max(real_width, real_height) / min(real_width, real_height)
    for contour in contours:
        area = cv2.contourArea(contour)
        if area < image_area * 0.005 or area > image_area * 0.3:
            continue

        peri = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.025 * peri, True)
        if not 4 <= len(approx) <= 8:
            continue

        x, y, bound_w, bound_h = cv2.boundingRect(contour)
        if x <= 1 or y <= 1 or x + bound_w >= image_w - 1 or y + bound_h >= image_h - 1:
            continue

        (_, _), (rect_w, rect_h), _ = cv2.minAreaRect(contour)
        if rect_w <= 0 or rect_h <= 0:
            continue

        observed_ratio = max(rect_w, rect_h) / min(rect_w, rect_h)
        ratio_quality = min(observed_ratio, expected_ratio) / max(observed_ratio, expected_ratio)
        rectangularity = min(1.0, area / max(1.0, rect_w * rect_h))
        center_x = x + bound_w / 2
        at_body_side = center_x < image_w * 0.43 or center_x > image_w * 0.57
        if ratio_quality < 0.72 or rectangularity < 0.62 or not at_body_side:
            continue

        area_quality = min(1.0, area / (image_area * 0.06))
        score = ratio_quality * 0.55 + rectangularity * 0.3 + area_quality * 0.15
        if score > best_score:
            best = contour
            best_score = score

    return best


VIEW_LABELS = {
    "front": "foto depan",
    "side": "foto samping",
    "back": "foto belakang",
}


def manual_reference_contour(image, box_payload):
    if not box_payload:
        return None

    try:
        box = json.loads(box_payload) if isinstance(box_payload, str) else box_payload
    except (TypeError, ValueError):
        return None

    required = ("x", "y", "w", "h", "image_width", "image_height")
    if not all(key in box for key in required):
        return None

    source_w = float(box.get("image_width") or 0)
    source_h = float(box.get("image_height") or 0)
    if source_w <= 0 or source_h <= 0:
        return None

    image_h, image_w = image.shape[:2]
    x = float(box["x"]) / source_w * image_w
    y = float(box["y"]) / source_h * image_h
    w = float(box["w"]) / source_w * image_w
    h = float(box["h"]) / source_h * image_h

    if w < image_w * 0.015 or h < image_h * 0.015:
        return None

    x1 = max(0, min(image_w - 1, x))
    y1 = max(0, min(image_h - 1, y))
    x2 = max(0, min(image_w - 1, x + w))
    y2 = max(0, min(image_h - 1, y + h))
    if x2 <= x1 or y2 <= y1:
        return None

    return np.array([
        [[int(round(x1)), int(round(y1))]],
        [[int(round(x2)), int(round(y1))]],
        [[int(round(x2)), int(round(y2))]],
        [[int(round(x1)), int(round(y2))]],
    ], dtype=np.int32)


def refine_manual_reference_contour(image, box_payload, real_width, real_height):
    """Find the physical reference edges inside a user-selected search area."""
    manual_contour = manual_reference_contour(image, box_payload)
    if manual_contour is None:
        return None

    image_h, image_w = image.shape[:2]
    x, y, width, height = cv2.boundingRect(manual_contour)
    padding = max(4, int(round(max(width, height) * 0.08)))
    roi_x1 = max(0, x - padding)
    roi_y1 = max(0, y - padding)
    roi_x2 = min(image_w, x + width + padding)
    roi_y2 = min(image_h, y + height + padding)
    roi = image[roi_y1:roi_y2, roi_x1:roi_x2]
    if roi.size == 0:
        return None

    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8)).apply(gray)
    blurred = cv2.GaussianBlur(clahe, (5, 5), 0)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))

    edge_variants = []
    canny = cv2.Canny(blurred, 35, 120)
    edge_variants.append(("canny", cv2.morphologyEx(canny, cv2.MORPH_CLOSE, kernel, iterations=2)))
    for threshold_type, name in (
        (cv2.THRESH_BINARY, "adaptive"),
        (cv2.THRESH_BINARY_INV, "adaptive_inverse"),
    ):
        thresholded = cv2.adaptiveThreshold(
            blurred,
            255,
            cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
            threshold_type,
            31,
            7,
        )
        edge_variants.append((name, cv2.morphologyEx(thresholded, cv2.MORPH_CLOSE, kernel, iterations=2)))

    expected_ratio = max(real_width, real_height) / min(real_width, real_height)
    roi_area = max(1, roi.shape[0] * roi.shape[1])
    target_center = np.array([x + width / 2 - roi_x1, y + height / 2 - roi_y1], dtype=np.float32)
    target_size = max(1.0, math.hypot(width, height))
    best = None
    best_score = 0.0
    best_method = None
    best_quality = 0.0

    for method, processed in edge_variants:
        contours, _ = cv2.findContours(processed, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
        for contour in contours:
            area = cv2.contourArea(contour)
            if area < roi_area * 0.12 or area > roi_area * 0.94:
                continue

            rect = cv2.minAreaRect(contour)
            (_, _), (rect_w, rect_h), _ = rect
            if rect_w <= 0 or rect_h <= 0:
                continue

            observed_ratio = max(rect_w, rect_h) / min(rect_w, rect_h)
            ratio_quality = min(observed_ratio, expected_ratio) / max(observed_ratio, expected_ratio)
            rectangularity = min(1.0, area / max(1.0, rect_w * rect_h))
            rect_center = np.array(rect[0], dtype=np.float32)
            center_quality = max(0.0, 1.0 - np.linalg.norm(rect_center - target_center) / target_size)
            area_quality = min(1.0, area / max(1.0, width * height))
            if ratio_quality < 0.72 or rectangularity < 0.58 or center_quality < 0.45:
                continue

            score = ratio_quality * 0.45 + rectangularity * 0.25 + center_quality * 0.2 + area_quality * 0.1
            if score <= best_score:
                continue

            points = cv2.boxPoints(rect)
            points[:, 0] += roi_x1
            points[:, 1] += roi_y1
            best = points.reshape((-1, 1, 2)).astype(np.int32)
            best_score = score
            best_method = method
            best_quality = min(0.98, max(0.72, score))

    if best is not None:
        return {
            "contour": best,
            "source": "manual_refined",
            "quality": round(float(best_quality), 4),
            "processing": {
                "roi": [int(roi_x1), int(roi_y1), int(roi_x2 - roi_x1), int(roi_y2 - roi_y1)],
                "method": best_method,
                "variants": ["clahe", "canny", "adaptive", "adaptive_inverse"],
                "refined": True,
            },
        }

    return {
        "contour": manual_contour,
        "source": "manual_fallback",
        "quality": 0.72,
        "processing": {
            "roi": [int(roi_x1), int(roi_y1), int(roi_x2 - roi_x1), int(roi_y2 - roi_y1)],
            "method": "manual_box",
            "variants": ["clahe", "canny", "adaptive", "adaptive_inverse"],
            "refined": False,
        },
    }


def calculate_scale(image, ref_object, ref_width_cm=None, ref_height_cm=None, manual_box=None):
    real_width, real_height = get_reference_dimensions(ref_object, ref_width_cm, ref_height_cm)
    manual_result = refine_manual_reference_contour(image, manual_box, real_width, real_height)
    contour = manual_result["contour"] if manual_result else None
    source = manual_result["source"] if manual_result else "auto"
    detection_quality = manual_result["quality"] if manual_result else 1.0
    processing = manual_result["processing"] if manual_result else {
        "method": "full_image_contour",
        "variants": ["grayscale", "gaussian_blur", "canny"],
        "refined": True,
    }
    if contour is None:
        contour = detect_reference_object(image, real_width, real_height)
    if contour is None:
        return None

    (_, _), (w, h), _ = cv2.minAreaRect(contour)
    if w <= 0 or h <= 0:
        return None

    pixel_long = max(w, h)
    pixel_short = min(w, h)
    real_long = max(real_width, real_height)
    real_short = min(real_width, real_height)
    long_scale = pixel_long / real_long
    short_scale = pixel_short / real_short
    scale = (long_scale + short_scale) / 2
    scale_consistency = min(long_scale, short_scale) / max(long_scale, short_scale)
    return {
        "scale": float(scale),
        "contour": contour,
        "area": float(cv2.contourArea(contour)),
        "source": source,
        "quality": round(float(min(scale_consistency, detection_quality)), 4),
        "axis_scales": [round(float(long_scale), 4), round(float(short_scale), 4)],
        "processing": processing,
    }


def detect_pose(image):
    global _POSE_LANDMARKER

    BaseOptions = mp.tasks.BaseOptions
    PoseLandmarker = mp.tasks.vision.PoseLandmarker
    PoseLandmarkerOptions = mp.tasks.vision.PoseLandmarkerOptions
    VisionRunningMode = mp.tasks.vision.RunningMode

    if _POSE_LANDMARKER is None:
        options = PoseLandmarkerOptions(
            base_options=BaseOptions(model_asset_path=MODEL_PATH),
            running_mode=VisionRunningMode.IMAGE,
            num_poses=1,
            output_segmentation_masks=True,
        )
        _POSE_LANDMARKER = PoseLandmarker.create_from_options(options)

    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=image_rgb)
    result = _POSE_LANDMARKER.detect(mp_image)

    if not result.pose_landmarks:
        return None

    h, w = image.shape[:2]
    landmarks = result.pose_landmarks[0]

    def point(idx):
        lm = landmarks[idx]
        return (lm.x * w, lm.y * h, lm.visibility)

    keypoints = {
        "nose": point(0),
        "left_shoulder": point(11),
        "right_shoulder": point(12),
        "left_elbow": point(13),
        "right_elbow": point(14),
        "left_wrist": point(15),
        "right_wrist": point(16),
        "left_hip": point(23),
        "right_hip": point(24),
        "left_knee": point(25),
        "right_knee": point(26),
        "left_ankle": point(27),
        "right_ankle": point(28),
    }
    visible_count = sum(1 for lm in landmarks if lm.visibility > 0.5)
    segmentation_mask = None
    if result.segmentation_masks:
        segmentation_mask = np.array(result.segmentation_masks[0].numpy_view(), copy=True)

    return {
        "keypoints": keypoints,
        "confidence": round(visible_count / 33, 2),
        "segmentation_mask": segmentation_mask,
    }


def point_xy(point):
    return (point[0], point[1])


def build_body_mask(image, keypoints, ref_contour=None, pose_segmentation=None):
    h, w = image.shape[:2]
    mask = np.zeros((h, w), np.uint8)
    visible_points = [
        point
        for point in keypoints.values()
        if len(point) >= 3 and point[2] > 0.35
    ]
    if not visible_points:
        return mask

    xs = [point[0] for point in visible_points]
    ys = [point[1] for point in visible_points]
    shoulder_y = average(keypoints["left_shoulder"][1], keypoints["right_shoulder"][1])
    nose_y = keypoints["nose"][1]
    head_margin = max(shoulder_y - nose_y, h * 0.035)
    pose_width = max(xs) - min(xs)
    pose_height = max(ys) - min(ys)
    pad_x = max(w * 0.025, pose_width * 0.08)
    pad_bottom = max(h * 0.02, pose_height * 0.025)
    x1 = max(1, int(round(min(xs) - pad_x)))
    y1 = max(1, int(round(min(ys) - head_margin)))
    x2 = min(w - 2, int(round(max(xs) + pad_x)))
    y2 = min(h - 2, int(round(max(ys) + pad_bottom)))
    if x2 <= x1 or y2 <= y1:
        return mask

    body_mask = None
    if pose_segmentation is not None:
        probability = pose_segmentation
        if probability.shape[:2] != (h, w):
            probability = cv2.resize(probability, (w, h), interpolation=cv2.INTER_LINEAR)
        candidate = np.where(probability >= 0.5, 255, 0).astype("uint8")
        coverage = cv2.countNonZero(candidate) / max(1, h * w)
        if 0.015 <= coverage <= 0.65:
            body_mask = candidate

    if body_mask is None:
        rect = (x1, y1, x2 - x1, y2 - y1)
        bgd = np.zeros((1, 65), np.float64)
        fgd = np.zeros((1, 65), np.float64)

        try:
            cv2.grabCut(image, mask, rect, bgd, fgd, 2, cv2.GC_INIT_WITH_RECT)
            body_mask = np.where((mask == 2) | (mask == 0), 0, 255).astype("uint8")
        except cv2.error:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            _, body_mask = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)

    if ref_contour is not None:
        cv2.drawContours(body_mask, [ref_contour], -1, 0, thickness=cv2.FILLED)

    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
    body_mask = cv2.morphologyEx(body_mask, cv2.MORPH_OPEN, kernel, iterations=1)
    body_mask = cv2.morphologyEx(body_mask, cv2.MORPH_CLOSE, kernel, iterations=2)
    return isolate_pose_component(body_mask, keypoints)


def isolate_pose_component(mask, keypoints):
    count, labels, stats, _ = cv2.connectedComponentsWithStats(mask, connectivity=8)
    if count <= 1:
        return mask

    h, w = mask.shape
    votes = {}
    anchors = (
        keypoints["left_shoulder"],
        keypoints["right_shoulder"],
        keypoints["left_hip"],
        keypoints["right_hip"],
        keypoints["left_knee"],
        keypoints["right_knee"],
    )
    radius = max(2, int(round(min(h, w) * 0.012)))
    for point in anchors:
        x = int(max(0, min(w - 1, round(point[0]))))
        y = int(max(0, min(h - 1, round(point[1]))))
        patch = labels[max(0, y - radius):min(h, y + radius + 1), max(0, x - radius):min(w, x + radius + 1)]
        foreground = patch[patch > 0]
        if foreground.size:
            label = int(np.bincount(foreground).argmax())
            votes[label] = votes.get(label, 0) + 1

    if votes:
        selected = max(votes, key=lambda label: (votes[label], stats[label, cv2.CC_STAT_AREA]))
    else:
        selected = 1 + int(np.argmax(stats[1:, cv2.CC_STAT_AREA]))

    return np.where(labels == selected, 255, 0).astype(np.uint8)


def largest_body_bounds(mask):
    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not contours:
        return None
    contour = max(contours, key=cv2.contourArea)
    if cv2.contourArea(contour) < mask.shape[0] * mask.shape[1] * 0.02:
        return None
    return cv2.boundingRect(contour)


def width_at_y(mask, y):
    h, _ = mask.shape
    y = int(max(0, min(h - 1, y)))
    band_top = max(0, y - 3)
    band_bottom = min(h, y + 4)
    rows = mask[band_top:band_bottom, :]
    cols = np.where(rows.max(axis=0) > 0)[0]
    if len(cols) < 2:
        return 0.0
    return float(cols[-1] - cols[0])


def constrained_width_at_y(mask, y, center_x, max_width_px):
    h, w = mask.shape
    y = int(max(0, min(h - 1, y)))
    max_width_px = max(8.0, min(float(max_width_px), float(w)))
    center_x = float(max(0, min(w - 1, center_x)))
    x_min = int(max(0, round(center_x - max_width_px / 2)))
    x_max = int(min(w - 1, round(center_x + max_width_px / 2)))
    if x_max <= x_min:
        return 0.0

    band_top = max(0, y - 4)
    band_bottom = min(h, y + 5)
    rows = mask[band_top:band_bottom, x_min:x_max + 1]
    local_center = center_x - x_min
    row_widths = []
    for row in rows:
        cols = np.where(row > 0)[0]
        if len(cols) < 2:
            continue

        segments = []
        start = cols[0]
        previous = cols[0]
        for col in cols[1:]:
            if col > previous + 1:
                segments.append((start, previous))
                start = col
            previous = col
        segments.append((start, previous))

        best = min(
            segments,
            key=lambda segment: 0 if segment[0] <= local_center <= segment[1] else min(abs(local_center - segment[0]), abs(local_center - segment[1])),
        )
        distance = 0 if best[0] <= local_center <= best[1] else min(abs(local_center - best[0]), abs(local_center - best[1]))
        if distance <= max_width_px * 0.3:
            row_widths.append(float(best[1] - best[0] + 1))

    return float(np.median(row_widths)) if row_widths else 0.0


def x_at_y(start, end, target_y):
    delta_y = end[1] - start[1]
    if abs(delta_y) < 1:
        return average(start[0], end[0])
    fraction = max(0.0, min(1.0, (target_y - start[1]) / delta_y))
    return start[0] + (end[0] - start[0]) * fraction


def level_centers_x(keypoints, level_name, target_y):
    shoulder_mid = midpoint(point_xy(keypoints["left_shoulder"]), point_xy(keypoints["right_shoulder"]))
    hip_mid = midpoint(point_xy(keypoints["left_hip"]), point_xy(keypoints["right_hip"]))

    if level_name in ("neck", "chest"):
        return [shoulder_mid[0]]
    if level_name == "waist":
        return [midpoint(shoulder_mid, hip_mid)[0]]
    if level_name == "hips":
        return [hip_mid[0]]
    if level_name == "upper_arm":
        return [
            x_at_y(keypoints["left_shoulder"], keypoints["left_elbow"], target_y),
            x_at_y(keypoints["right_shoulder"], keypoints["right_elbow"], target_y),
        ]
    if level_name == "wrist":
        return [keypoints["left_wrist"][0], keypoints["right_wrist"][0]]
    if level_name == "thigh":
        return [
            x_at_y(keypoints["left_hip"], keypoints["left_knee"], target_y),
            x_at_y(keypoints["right_hip"], keypoints["right_knee"], target_y),
        ]
    if level_name == "knee":
        return [keypoints["left_knee"][0], keypoints["right_knee"][0]]
    if level_name == "calf":
        return [
            x_at_y(keypoints["left_knee"], keypoints["left_ankle"], target_y),
            x_at_y(keypoints["right_knee"], keypoints["right_ankle"], target_y),
        ]
    if level_name == "ankle":
        return [
            x_at_y(keypoints["left_knee"], keypoints["left_ankle"], target_y),
            x_at_y(keypoints["right_knee"], keypoints["right_ankle"], target_y),
        ]
    return [average(shoulder_mid[0], hip_mid[0])]


def level_window_px(keypoints, level_name, view):
    shoulder_px = euclidean_distance(point_xy(keypoints["left_shoulder"]), point_xy(keypoints["right_shoulder"]))
    hip_px = euclidean_distance(point_xy(keypoints["left_hip"]), point_xy(keypoints["right_hip"]))
    nose_y = keypoints["nose"][1]
    ankle_y = average(keypoints["left_ankle"][1], keypoints["right_ankle"][1])
    body_height = max(1.0, ankle_y - nose_y)
    torso_base = max(shoulder_px, hip_px, body_height * (0.18 if view == "side" else 0.24))

    if level_name == "neck":
        return max(body_height * 0.12, torso_base * 0.55)
    if level_name in ("chest", "waist", "hips"):
        factor = 1.35 if view != "side" else 1.2
        return max(torso_base * factor, body_height * (0.25 if view != "side" else 0.22))

    body_factors = {
        "upper_arm": 0.12,
        "wrist": 0.075,
        "thigh": 0.16,
        "knee": 0.13,
        "calf": 0.12,
        "ankle": 0.085,
    }
    return body_height * body_factors.get(level_name, 0.12)


def ellipse_circumference(width_cm, depth_cm):
    if width_cm <= 0 or depth_cm <= 0:
        return 0.0
    a = width_cm / 2
    b = depth_cm / 2
    return math.pi * (3 * (a + b) - math.sqrt((3 * a + b) * (a + 3 * b)))


def average(*values):
    valid = [v for v in values if v and v > 0]
    return sum(valid) / len(valid) if valid else 0.0


def y_levels(keypoints):
    shoulder_y = average(keypoints["left_shoulder"][1], keypoints["right_shoulder"][1])
    hip_y = average(keypoints["left_hip"][1], keypoints["right_hip"][1])
    knee_y = average(keypoints["left_knee"][1], keypoints["right_knee"][1])
    ankle_y = average(keypoints["left_ankle"][1], keypoints["right_ankle"][1])
    torso = max(1.0, hip_y - shoulder_y)
    leg = max(1.0, ankle_y - hip_y)
    return {
        "neck": shoulder_y - torso * 0.22,
        "chest": shoulder_y + torso * 0.28,
        "waist": shoulder_y + torso * 0.62,
        "hips": hip_y,
        "upper_arm": shoulder_y + torso * 0.25,
        "wrist": average(keypoints["left_wrist"][1], keypoints["right_wrist"][1]),
        "thigh": hip_y + leg * 0.22,
        "knee": knee_y,
        "calf": knee_y + (ankle_y - knee_y) * 0.45,
        "ankle": ankle_y - (ankle_y - knee_y) * 0.08,
    }


def px_to_cm(value, scale):
    return pixel_to_cm(value, scale)


def rounded(value):
    return round(float(value), 2) if value and value > 0 else 0.0


def impossible_measurements(data):
    invalid = []
    for field, value in data.items():
        limits = MEASUREMENT_LIMITS_CM.get(field)
        if not limits:
            continue
        minimum, maximum = limits
        if value and (value < minimum or value > maximum):
            invalid.append({
                "field": field,
                "value": value,
                "min": minimum,
                "max": maximum,
            })
    return invalid


def process_measurement(
    front_bytes,
    side_bytes,
    back_bytes,
    ref_object,
    ref_width_cm=None,
    ref_height_cm=None,
    reference_boxes=None,
    progress_callback=None,
):
    started_at = time.perf_counter()

    def progress(stage, percent, message, view=None):
        if progress_callback:
            progress_callback({
                "stage": stage,
                "percent": int(percent),
                "message": message,
                "view": view,
            })

    progress("prepare_photos", 5, "Membaca dan menyiapkan tiga foto")
    images = {
        "front": decode_image(front_bytes),
        "side": decode_image(side_bytes),
        "back": decode_image(back_bytes),
    }

    invalid_views = [view for view, image in images.items() if image is None]
    if invalid_views:
        return {
            "success": False,
            "error": f"Gagal membaca {', '.join(VIEW_LABELS[view] for view in invalid_views)}.",
            "failed_view": invalid_views[0] if len(invalid_views) == 1 else "multiple",
            "failed_reason": "invalid_image",
            "correction": "Gunakan gambar JPG, PNG, atau WEBP yang tidak rusak.",
            "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
        }

    images = {
        view: resize_for_measurement(image)
        for view, image in images.items()
    }
    progress("prepare_photos", 10, "Foto siap diproses")

    scales = {}
    scale_sources = {}
    scale_qualities = {}
    reference_axis_scales = {}
    reference_processing = {}
    poses = {}
    masks = {}
    bounds = {}
    pose_statures = {}

    view_progress = {
        "front": (16, 25),
        "side": (32, 41),
        "back": (48, 57),
    }

    for view, image in images.items():
        reference_percent, body_percent = view_progress[view]
        label = VIEW_LABELS.get(view, f"foto {view}")
        progress("reference_roi", reference_percent, f"Memproses area benda patokan pada {label}", view)
        scale_result = calculate_scale(
            image,
            ref_object,
            ref_width_cm,
            ref_height_cm,
            (reference_boxes or {}).get(view),
        )
        if scale_result is None:
            return {
                "success": False,
                "error": f"Benda patokan ukuran tidak terdeteksi pada {label}. Pilih area A4/KTP secara manual di preview, atau pastikan benda terlihat penuh, tegak, dan berada di samping tubuh.",
                "failed_view": view,
                "failed_reason": "reference_not_detected",
                "correction": "Atur kotak merah tepat pada empat tepi A4/KTP di foto ini.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            }
        if scale_result.get("quality", 0.0) < 0.72:
            return {
                "success": False,
                "error": f"Proporsi kotak A4/KTP pada {label} tidak sesuai. Atur kotak tepat pada empat tepi benda patokan, jangan menyertakan background, lalu ulangi analisis.",
                "failed_view": view,
                "failed_reason": "invalid_reference_proportion",
                "correction": "Kecilkan atau geser kotak sampai hanya membungkus empat tepi A4/KTP.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                "reference_quality": round(float(scale_result.get("quality", 0.0)), 3),
                "reference_axis_scales": scale_result.get("axis_scales", []),
            }

        progress("body_segmentation", body_percent, f"Mendeteksi pose dan siluet pada {label}", view)
        pose = detect_pose(image)
        if pose is None:
            return {
                "success": False,
                "error": f"Pose tubuh tidak terdeteksi pada {label}. Pastikan seluruh badan terlihat jelas.",
                "failed_view": view,
                "failed_reason": "pose_not_detected",
                "correction": "Ambil ulang foto dengan kepala sampai kaki terlihat dan tangan tidak menutup badan.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            }

        points = pose["keypoints"]
        ankle_y = average(points["left_ankle"][1], points["right_ankle"][1])
        pose_span_px = max(0.0, ankle_y - points["nose"][1])
        stature_cm = px_to_cm(pose_span_px * 1.08, scale_result["scale"])
        if stature_cm < 110 or stature_cm > 230:
            return {
                "success": False,
                "error": (
                    f"Ukuran kotak A4/KTP pada {label} menghasilkan skala tubuh {stature_cm:.1f} cm, "
                    "sehingga kotak kemungkinan tidak mengikuti benda patokan. Atur kotak tepat pada "
                    "empat tepi A4/KTP, bukan pada area kosong di sekitarnya."
                ),
                "failed_view": view,
                "failed_reason": "invalid_reference_scale",
                "correction": "Atur kotak merah tepat pada empat tepi benda patokan dan jangan menyertakan background.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                "estimated_stature_cm": round(float(stature_cm), 2),
                "reference_source": scale_result.get("source"),
                "reference_processing": scale_result.get("processing", {}),
                "reference_axis_scales": scale_result.get("axis_scales", []),
            }

        mask = build_body_mask(
            image,
            points,
            scale_result["contour"],
            pose.get("segmentation_mask"),
        )
        body_bounds = largest_body_bounds(mask)
        if body_bounds is None:
            return {
                "success": False,
                "error": f"Siluet tubuh tidak terbaca pada {label}. Gunakan background polos dan pencahayaan cukup.",
                "failed_view": view,
                "failed_reason": "silhouette_not_detected",
                "correction": "Ambil ulang foto dengan kontras tubuh dan background yang lebih jelas.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            }

        scales[view] = scale_result["scale"]
        scale_sources[view] = scale_result["source"]
        scale_qualities[view] = scale_result.get("quality", 0.5)
        reference_axis_scales[view] = scale_result.get("axis_scales", [])
        reference_processing[view] = scale_result.get("processing", {})
        poses[view] = pose
        masks[view] = mask
        bounds[view] = body_bounds
        pose_statures[view] = stature_cm

    progress("cross_view_scale", 64, "Membandingkan skala foto depan, samping, dan belakang")
    scale_consistency = min(scales.values()) / max(scales.values())
    stature_consistency = min(pose_statures.values()) / max(pose_statures.values())
    if scale_consistency < 0.72 or stature_consistency < 0.82:
        return {
            "success": False,
            "error": (
                "Skala benda patokan atau posisi tubuh tidak konsisten pada tiga foto. "
                "Pastikan kamera tetap, tubuh berdiri di titik yang sama, dan kotak merah "
                "mengikuti tepi A4/KTP pada setiap foto."
            ),
            "failed_reason": "inconsistent_multiview_scale",
            "failed_view": "multiple",
            "correction": "Gunakan kamera dan titik berdiri yang sama pada foto depan, samping, dan belakang.",
            "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            "scale_consistency": round(float(scale_consistency), 4),
            "stature_consistency": round(float(stature_consistency), 4),
        }

    progress("calculate_measurements", 72, "Mengambil lebar, kedalaman, dan panjang tubuh")
    front_scale = scales["front"]
    side_scale = scales["side"]
    back_scale = scales["back"]
    front_points = poses["front"]["keypoints"]
    front_levels = y_levels(front_points)
    side_levels = y_levels(poses["side"]["keypoints"])
    back_levels = y_levels(poses["back"]["keypoints"])

    width_debug = {}

    def body_width_cm(view, level_name, scale):
        levels = front_levels if view == "front" else side_levels if view == "side" else back_levels
        keypoints = poses[view]["keypoints"]
        centers_x = level_centers_x(keypoints, level_name, levels[level_name])
        max_width = level_window_px(keypoints, level_name, view)
        raw_width = width_at_y(masks[view], levels[level_name])
        sampled_widths = [
            constrained_width_at_y(masks[view], levels[level_name], center_x, max_width)
            for center_x in centers_x
        ]
        valid_widths = [width for width in sampled_widths if width > 0]
        constrained_width = average(*valid_widths)
        if constrained_width <= 0 and len(centers_x) == 1:
            constrained_width = min(raw_width, max_width)

        width_debug[f"{view}_{level_name}"] = {
            "raw_px": round(float(raw_width), 2),
            "used_px": round(float(constrained_width), 2),
            "window_px": round(float(max_width), 2),
            "centers_x": [round(float(center_x), 2) for center_x in centers_x],
            "samples_px": [round(float(width), 2) for width in sampled_widths],
        }

        return px_to_cm(constrained_width, scale)

    def circumference(level_name):
        front_width = average(
            body_width_cm("front", level_name, front_scale),
            body_width_cm("back", level_name, back_scale),
        )
        side_depth = body_width_cm("side", level_name, side_scale)
        return rounded(ellipse_circumference(front_width, side_depth))

    shoulder_width = px_to_cm(
        euclidean_distance(point_xy(front_points["left_shoulder"]), point_xy(front_points["right_shoulder"])),
        front_scale,
    )
    left_arm = euclidean_distance(point_xy(front_points["left_shoulder"]), point_xy(front_points["left_elbow"])) + euclidean_distance(point_xy(front_points["left_elbow"]), point_xy(front_points["left_wrist"]))
    right_arm = euclidean_distance(point_xy(front_points["right_shoulder"]), point_xy(front_points["right_elbow"])) + euclidean_distance(point_xy(front_points["right_elbow"]), point_xy(front_points["right_wrist"]))
    arm_length = px_to_cm((left_arm + right_arm) / 2, front_scale)

    front_x, front_y, front_w, front_h = bounds["front"]
    height = average(pose_statures["front"], pose_statures["back"])
    hip_mid = midpoint(point_xy(front_points["left_hip"]), point_xy(front_points["right_hip"]))
    ankle_mid = midpoint(point_xy(front_points["left_ankle"]), point_xy(front_points["right_ankle"]))
    shoulder_mid = midpoint(point_xy(front_points["left_shoulder"]), point_xy(front_points["right_shoulder"]))
    inseam_px = max(0, ankle_mid[1] - (hip_mid[1] + front_h * 0.08))
    outseam_px = max(0, ankle_mid[1] - front_levels["waist"])
    shirt_length_px = max(0, front_levels["hips"] - shoulder_mid[1])

    chest = circumference("chest")
    waist = circumference("waist")
    hips = circumference("hips")
    thigh = circumference("thigh")
    knee = circumference("knee")
    calf = circumference("calf")
    ankle = circumference("ankle")

    data = {
        "neck": rounded(circumference("neck")),
        "chest": chest,
        "waist": waist,
        "hips": hips,
        "shoulder_width": rounded(shoulder_width),
        "shirt_length": rounded(px_to_cm(shirt_length_px, front_scale)),
        "arm_length": rounded(arm_length),
        "upper_arm": rounded(circumference("upper_arm")),
        "wrist": rounded(circumference("wrist")),
        "height": rounded(height),
        "pants_waist": waist,
        "pants_hips": hips,
        "thigh": thigh,
        "knee": knee,
        "calf": calf,
        "ankle": ankle,
        "inseam": rounded(px_to_cm(inseam_px, front_scale)),
        "outseam": rounded(px_to_cm(outseam_px, front_scale)),
        "rise": rounded(px_to_cm(max(0, outseam_px - inseam_px), front_scale)),
    }

    bodym_result = None
    if bodym_enabled():
        progress("bodym_features", 82, "Menyusun fitur siluet BodyM dari skala A4/KTP")
        try:
            bodym_result = get_bodym_service().predict_masks(
                masks["front"],
                masks["side"],
                front_pixels_per_cm=scales["front"],
                side_pixels_per_cm=scales["side"],
                coverage=0.90,
            )
        except BodyMInferenceError as exc:
            failed_view = exc.details.get("failed_view", "front_side")
            return {
                "success": False,
                "error": str(exc),
                "failed_view": failed_view,
                "failed_reason": exc.code,
                "correction": "Periksa kotak A4/KTP dan pastikan siluet depan serta samping utuh.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                "diagnostic_details": exc.details,
            }

        progress("bodym_inference", 87, "Memprediksi 14 indikator ukuran dengan BodyM v1")
        if bodym_result["status"] == "rejected":
            return {
                "success": False,
                "error": "Fitur siluet berada di luar pola BodyM atau menghasilkan ukuran yang tidak masuk akal.",
                "failed_view": "front_side",
                "failed_reason": "bodym_prediction_rejected",
                "correction": "Ulangi foto depan dan samping dengan pose, skala, serta siluet yang lebih jelas.",
                "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                "bodym": bodym_result,
            }

        for bodym_field, legacy_fields in BODYM_TO_LEGACY_FIELDS.items():
            value = rounded(bodym_result["predictions_cm"][bodym_field])
            for legacy_field in legacy_fields:
                data[legacy_field] = value

    invalid_measurements = impossible_measurements(data)
    progress("anatomical_validation", 88, "Memeriksa konsistensi anatomi hasil ukuran")
    if invalid_measurements:
        fields = ", ".join(f"{MEASUREMENT_LABELS.get(item['field'], item['field'])} {item['value']}cm" for item in invalid_measurements[:5])
        return {
            "success": False,
            "error": f"Hasil ukuran tidak masuk akal ({fields}). Periksa kembali kotak A4/KTP manual, pastikan kotak hanya mengikuti pinggir benda patokan, lalu ulangi analisis.",
            "failed_reason": "unrealistic_measurements",
            "failed_view": "multiple",
            "correction": "Periksa kembali kotak A4/KTP dan siluet pada ketiga foto.",
            "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            "invalid_measurements": invalid_measurements,
            "debug": {
                "duration_seconds": round(time.perf_counter() - started_at, 3),
                "scales": {key: round(value, 4) for key, value in scales.items()},
                "body_bounds": {key: [int(v) for v in value] for key, value in bounds.items()},
                "reference_scale_sources": scale_sources,
                "width_samples": width_debug,
            },
        }

    progress("confidence", 95, "Menghitung confidence dan kualitas setiap ukuran")
    pose_confidence = round(average(poses["front"]["confidence"], poses["side"]["confidence"], poses["back"]["confidence"]), 2)
    scale_quality = average(*scale_qualities.values())

    def width_sample_quality(sample):
        raw_px = sample["raw_px"]
        used_px = sample["used_px"]
        window_px = sample["window_px"]
        samples_px = sample.get("samples_px", [])
        valid_samples = [value for value in samples_px if value > 0]
        expected_samples = max(1, len(sample.get("centers_x", [])))
        coverage = len(valid_samples) / expected_samples
        if used_px <= 0 or coverage <= 0:
            return 0.25

        quality = 0.92 * coverage
        if used_px >= window_px * 0.95:
            quality *= 0.68
        if expected_samples == 1 and raw_px > used_px * 1.8:
            quality *= max(0.5, used_px / raw_px)
        if len(valid_samples) > 1:
            consistency = min(valid_samples) / max(valid_samples)
            quality *= max(0.65, consistency)
        return max(0.25, min(0.95, quality))

    width_quality_by_level = {}
    for level_name in front_levels.keys():
        qualities = [
            width_sample_quality(width_debug[f"{view}_{level_name}"])
            for view in ("front", "side", "back")
        ]
        width_quality_by_level[level_name] = average(*qualities)

    width_quality_values = list(width_quality_by_level.values())
    width_quality = average(*width_quality_values)
    quality_score = round(average(pose_confidence, scale_quality, width_quality), 2)

    level_by_field = {
        "neck": "neck",
        "chest": "chest",
        "waist": "waist",
        "hips": "hips",
        "upper_arm": "upper_arm",
        "wrist": "wrist",
        "pants_waist": "waist",
        "pants_hips": "hips",
        "thigh": "thigh",
        "knee": "knee",
        "calf": "calf",
        "ankle": "ankle",
    }
    direct_confidence = round(min(0.95, average(pose_confidence, scale_quality)), 2)
    per_field_confidence = {}
    for field in data.keys():
        level_name = level_by_field.get(field)
        if level_name:
            field_quality = average(pose_confidence, scale_quality, width_quality_by_level[level_name])
            per_field_confidence[field] = round(max(0.3, min(0.9, field_quality)), 2)
        else:
            per_field_confidence[field] = direct_confidence

    if bodym_result:
        for bodym_field, legacy_fields in BODYM_TO_LEGACY_FIELDS.items():
            confidence = round(float(bodym_result["per_field_confidence"][bodym_field]), 4)
            for legacy_field in legacy_fields:
                per_field_confidence[legacy_field] = confidence

    photo_diagnostics = {
        view: {
            "reference_detected": True,
            "reference_source": scale_sources[view],
            "reference_quality": round(float(scale_qualities[view]), 4),
            "reference_axis_scales": reference_axis_scales[view],
            "reference_processing": reference_processing[view],
            "pixels_per_cm": round(float(scales[view]), 6),
            "pose_confidence": poses[view]["confidence"],
            "body_bounds": [int(value) for value in bounds[view]],
            "used_by_bodym": view in ("front", "side"),
        }
        for view in ("front", "side", "back")
    }

    result = {
        "success": True,
        "data": data,
        "confidence": quality_score,
        "quality_score": quality_score,
        "ref_detected": True,
        "measurement_method": "bodym_ml" if bodym_result else "multiview_cv",
        "per_field_confidence": per_field_confidence,
        "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
        "photo_diagnostics": photo_diagnostics,
        "debug": {
            "duration_seconds": round(time.perf_counter() - started_at, 3),
            "image_shapes": {key: [int(v) for v in image.shape[:2]] for key, image in images.items()},
            "scales": {key: round(value, 4) for key, value in scales.items()},
            "reference_axis_scales": reference_axis_scales,
            "reference_quality": round(scale_quality, 4),
            "pose_confidence": pose_confidence,
            "width_quality": round(width_quality, 4),
            "width_quality_by_level": {key: round(value, 4) for key, value in width_quality_by_level.items()},
            "body_bounds": {key: [int(v) for v in value] for key, value in bounds.items()},
            "reference_scale_sources": scale_sources,
            "reference_processing": reference_processing,
            "scale_consistency": round(float(scale_consistency), 4),
            "stature_consistency": round(float(stature_consistency), 4),
            "width_samples": width_debug,
        },
    }
    if bodym_result:
        result["bodym_data"] = bodym_result["predictions_cm"]
        result["bodym_per_field_confidence"] = bodym_result["per_field_confidence"]
        result["bodym_prediction_intervals_cm"] = bodym_result["prediction_intervals_cm"]
        result["bodym"] = {
            key: value
            for key, value in bodym_result.items()
            if key not in ("predictions_cm", "per_field_confidence", "prediction_intervals_cm")
        }
        result["bodym"]["silent_clipping"] = bodym_result["silent_clipping"]
        result["legacy_fallback_fields"] = sorted(
            set(data) - {legacy for fields in BODYM_TO_LEGACY_FIELDS.values() for legacy in fields}
        )
    progress("completed", 100, "Analisis selesai dan hasil siap diperiksa")
    return result
