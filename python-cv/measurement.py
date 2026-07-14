"""
Multi-view body measurement estimation for ZRINTTAILOR.

The service requires front, side, and back photos. Each photo must contain a
standing calibration marker. The system rejects the request when the marker or
body pose cannot be detected, because guessing scale from image height makes
tailoring measurements unstable.
"""
import math
import os
import urllib.request

import cv2
import mediapipe as mp
import numpy as np

from utils import euclidean_distance, get_reference_dimensions, midpoint, pixel_to_cm

MODEL_PATH = "pose_landmarker_lite.task"
if not os.path.exists(MODEL_PATH):
    url = "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task"
    urllib.request.urlretrieve(url, MODEL_PATH)


def decode_image(image_bytes):
    nparr = np.frombuffer(image_bytes, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)


def detect_reference_object(image):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blurred, 50, 150)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    edges = cv2.morphologyEx(edges, cv2.MORPH_CLOSE, kernel, iterations=2)
    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    best = None
    best_area = 0
    image_area = image.shape[0] * image.shape[1]
    for contour in contours:
        area = cv2.contourArea(contour)
        if area < image_area * 0.005 or area > image_area * 0.45:
            continue

        peri = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.025 * peri, True)
        if 4 <= len(approx) <= 8 and area > best_area:
            best = contour
            best_area = area

    return best


def calculate_scale(image, ref_object, ref_width_cm=None, ref_height_cm=None):
    real_width, real_height = get_reference_dimensions(ref_object, ref_width_cm, ref_height_cm)
    contour = detect_reference_object(image)
    if contour is None:
        return None

    (_, _), (w, h), _ = cv2.minAreaRect(contour)
    if w <= 0 or h <= 0:
        return None

    pixel_long = max(w, h)
    pixel_short = min(w, h)
    real_long = max(real_width, real_height)
    real_short = min(real_width, real_height)
    scale = ((pixel_long / real_long) + (pixel_short / real_short)) / 2
    return {
        "scale": float(scale),
        "contour": contour,
        "area": float(cv2.contourArea(contour)),
    }


def detect_pose(image):
    BaseOptions = mp.tasks.BaseOptions
    PoseLandmarker = mp.tasks.vision.PoseLandmarker
    PoseLandmarkerOptions = mp.tasks.vision.PoseLandmarkerOptions
    VisionRunningMode = mp.tasks.vision.RunningMode

    options = PoseLandmarkerOptions(
        base_options=BaseOptions(model_asset_path=MODEL_PATH),
        running_mode=VisionRunningMode.IMAGE,
        num_poses=1,
    )

    with PoseLandmarker.create_from_options(options) as landmarker:
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=image_rgb)
        result = landmarker.detect(mp_image)

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
    return {
        "keypoints": keypoints,
        "confidence": round(visible_count / 33, 2),
    }


def point_xy(point):
    return (point[0], point[1])


def build_body_mask(image, ref_contour=None):
    h, w = image.shape[:2]
    mask = np.zeros((h, w), np.uint8)
    rect = (max(1, int(w * 0.05)), max(1, int(h * 0.02)), int(w * 0.9), int(h * 0.95))
    bgd = np.zeros((1, 65), np.float64)
    fgd = np.zeros((1, 65), np.float64)

    try:
        cv2.grabCut(image, mask, rect, bgd, fgd, 4, cv2.GC_INIT_WITH_RECT)
        body_mask = np.where((mask == 2) | (mask == 0), 0, 255).astype("uint8")
    except cv2.error:
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        _, body_mask = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)

    if ref_contour is not None:
        cv2.drawContours(body_mask, [ref_contour], -1, 0, thickness=cv2.FILLED)

    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
    body_mask = cv2.morphologyEx(body_mask, cv2.MORPH_OPEN, kernel, iterations=1)
    body_mask = cv2.morphologyEx(body_mask, cv2.MORPH_CLOSE, kernel, iterations=2)
    return body_mask


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


def process_measurement(front_bytes, side_bytes, back_bytes, ref_object, ref_width_cm=None, ref_height_cm=None):
    images = {
        "front": decode_image(front_bytes),
        "side": decode_image(side_bytes),
        "back": decode_image(back_bytes),
    }

    if any(image is None for image in images.values()):
        return {"success": False, "error": "Gagal membaca salah satu gambar. Pastikan format gambar benar."}

    scales = {}
    poses = {}
    masks = {}
    bounds = {}

    for view, image in images.items():
        scale_result = calculate_scale(image, ref_object, ref_width_cm, ref_height_cm)
        if scale_result is None:
            return {
                "success": False,
                "error": f"Marker kalibrasi tidak terdeteksi pada foto {view}. Gunakan marker berdiri sendiri yang terlihat penuh.",
                "failed_view": view,
            }

        pose = detect_pose(image)
        if pose is None:
            return {
                "success": False,
                "error": f"Pose tubuh tidak terdeteksi pada foto {view}. Pastikan seluruh badan terlihat jelas.",
                "failed_view": view,
            }

        mask = build_body_mask(image, scale_result["contour"])
        body_bounds = largest_body_bounds(mask)
        if body_bounds is None:
            return {
                "success": False,
                "error": f"Siluet tubuh tidak terbaca pada foto {view}. Gunakan background polos dan pencahayaan cukup.",
                "failed_view": view,
            }

        scales[view] = scale_result["scale"]
        poses[view] = pose
        masks[view] = mask
        bounds[view] = body_bounds

    front_scale = scales["front"]
    side_scale = scales["side"]
    back_scale = scales["back"]
    front_points = poses["front"]["keypoints"]
    front_levels = y_levels(front_points)
    side_levels = y_levels(poses["side"]["keypoints"])
    back_levels = y_levels(poses["back"]["keypoints"])

    def body_width_cm(view, level_name, scale):
        levels = front_levels if view == "front" else side_levels if view == "side" else back_levels
        return px_to_cm(width_at_y(masks[view], levels[level_name]), scale)

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
    height = px_to_cm(front_h, front_scale)
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

    confidence = round(average(poses["front"]["confidence"], poses["side"]["confidence"], poses["back"]["confidence"]), 2)
    scale_spread = max(scales.values()) - min(scales.values())
    scale_quality = max(0.0, 1.0 - (scale_spread / max(scales.values())))
    quality_score = round(average(confidence, scale_quality), 2)

    direct_fields = {"shoulder_width", "shirt_length", "arm_length", "height", "inseam", "outseam", "rise"}
    per_field_confidence = {
        field: round(min(0.95, quality_score + 0.08), 2) if field in direct_fields else round(max(0.45, quality_score - 0.08), 2)
        for field in data.keys()
    }

    return {
        "success": True,
        "data": data,
        "confidence": confidence,
        "quality_score": quality_score,
        "ref_detected": True,
        "measurement_method": "multiview_cv",
        "per_field_confidence": per_field_confidence,
        "debug": {
            "scales": {key: round(value, 4) for key, value in scales.items()},
            "body_bounds": {key: [int(v) for v in value] for key, value in bounds.items()},
        },
    }
