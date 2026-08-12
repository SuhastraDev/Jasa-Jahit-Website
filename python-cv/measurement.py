"""
Multi-view body measurement estimation for ZRINTTAILOR.

The service uses front, side, and back photos. A calibration marker improves
scale, while the body silhouette remains the primary input for the estimator.
MediaPipe landmarks are an accelerator, not a hard dependency: OpenCV can
recover a silhouette and an approximate pose when the landmark worker fails.
"""
import math
import multiprocessing
import os
import time
import json
import urllib.request

import cv2
import numpy as np

try:
    import mediapipe as mp
except ModuleNotFoundError:  # OpenCV silhouette fallback remains usable without it.
    mp = None

from bodym_inference import BodyMInferenceError, get_bodym_service
from utils import euclidean_distance, get_reference_dimensions, midpoint, pixel_to_cm

MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "pose_landmarker_lite.task")
if not os.path.exists(MODEL_PATH):
    url = "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task"
    urllib.request.urlretrieve(url, MODEL_PATH)

try:
    POSE_SUBPROCESS_TIMEOUT_SECONDS = max(
        3,
        min(20, int(os.getenv("POSE_SUBPROCESS_TIMEOUT_SECONDS", "8"))),
    )
except ValueError:
    POSE_SUBPROCESS_TIMEOUT_SECONDS = 8

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
DEFAULT_ESTIMATED_STATURE_CM = 165.0
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
        # The box only narrows the search area. Its dimensions are user input,
        # so treating it as the physical reference would manufacture a scale.
        "contour": None,
        "source": "manual_roi_unresolved",
        "quality": 0.0,
        "processing": {
            "roi": [int(roi_x1), int(roi_y1), int(roi_x2 - roi_x1), int(roi_y2 - roi_y1)],
            "method": "reference_edges_not_found",
            "variants": ["clahe", "canny", "adaptive", "adaptive_inverse"],
            "refined": False,
        },
    }


def _ordered_reference_corners(contour):
    if contour is None:
        return None

    points = np.asarray(contour, dtype=np.float32).reshape((-1, 2))
    if len(points) != 4:
        perimeter = cv2.arcLength(np.asarray(contour, dtype=np.float32), True)
        approximated = cv2.approxPolyDP(np.asarray(contour, dtype=np.float32), 0.02 * perimeter, True)
        points = approximated.reshape((-1, 2))
    if len(points) != 4:
        points = cv2.boxPoints(cv2.minAreaRect(np.asarray(contour, dtype=np.float32)))

    sums = points.sum(axis=1)
    differences = np.diff(points, axis=1).reshape(-1)
    ordered = np.zeros((4, 2), dtype=np.float32)
    ordered[0] = points[np.argmin(sums)]
    ordered[1] = points[np.argmin(differences)]
    ordered[2] = points[np.argmax(sums)]
    ordered[3] = points[np.argmax(differences)]
    if len(np.unique(ordered, axis=0)) != 4:
        return None
    return ordered


def project_points_to_reference_plane(points, homography):
    if homography is None:
        return []
    source = np.asarray(points, dtype=np.float32).reshape((-1, 1, 2))
    projected = cv2.perspectiveTransform(source, homography).reshape((-1, 2))
    return [tuple(float(value) for value in point) for point in projected]


def reference_plane_calibration(contour, real_width, real_height):
    """Map the detected four-corner reference plane to real centimeters."""
    ordered = _ordered_reference_corners(contour)
    if ordered is None:
        return None

    top_left, top_right, bottom_right, bottom_left = ordered
    top_px = euclidean_distance(top_left, top_right)
    bottom_px = euclidean_distance(bottom_left, bottom_right)
    left_px = euclidean_distance(top_left, bottom_left)
    right_px = euclidean_distance(top_right, bottom_right)
    horizontal_px = average(top_px, bottom_px)
    vertical_px = average(left_px, right_px)
    if min(horizontal_px, vertical_px, real_width, real_height) <= 0:
        return None

    real_long = max(float(real_width), float(real_height))
    real_short = min(float(real_width), float(real_height))
    if horizontal_px >= vertical_px:
        plane_width_cm, plane_height_cm = real_long, real_short
    else:
        plane_width_cm, plane_height_cm = real_short, real_long

    destination = np.array([
        [0.0, 0.0],
        [plane_width_cm, 0.0],
        [plane_width_cm, plane_height_cm],
        [0.0, plane_height_cm],
    ], dtype=np.float32)
    homography = cv2.getPerspectiveTransform(ordered, destination)
    if not np.isfinite(homography).all():
        return None

    center = np.mean(ordered, axis=0)
    projected = project_points_to_reference_plane(
        [center, center + np.array([1.0, 0.0]), center + np.array([0.0, 1.0])],
        homography,
    )
    if len(projected) != 3:
        return None
    cm_per_horizontal_px = euclidean_distance(projected[0], projected[1])
    cm_per_vertical_px = euclidean_distance(projected[0], projected[2])
    if min(cm_per_horizontal_px, cm_per_vertical_px) <= 0:
        return None

    horizontal_pixels_per_cm = 1.0 / cm_per_horizontal_px
    vertical_pixels_per_cm = 1.0 / cm_per_vertical_px
    horizontal_edge_consistency = min(top_px, bottom_px) / max(top_px, bottom_px)
    vertical_edge_consistency = min(left_px, right_px) / max(left_px, right_px)
    perspective_consistency = math.sqrt(horizontal_edge_consistency * vertical_edge_consistency)
    axis_consistency = min(horizontal_pixels_per_cm, vertical_pixels_per_cm) / max(
        horizontal_pixels_per_cm,
        vertical_pixels_per_cm,
    )

    return {
        "homography": homography,
        "ordered_corners": ordered,
        "horizontal_pixels_per_cm": float(horizontal_pixels_per_cm),
        "vertical_pixels_per_cm": float(vertical_pixels_per_cm),
        "scale": float(average(horizontal_pixels_per_cm, vertical_pixels_per_cm)),
        "axis_consistency": float(axis_consistency),
        "perspective_consistency": float(perspective_consistency),
        "plane_size_cm": [plane_width_cm, plane_height_cm],
    }


def calibrated_distance_cm(start, end, calibration):
    if not calibration:
        return 0.0

    homography = calibration.get("homography")
    if homography is not None:
        projected = project_points_to_reference_plane([start, end], homography)
        if len(projected) == 2:
            factor = float(calibration.get("homography_distance_factor", 1.0) or 1.0)
            return euclidean_distance(projected[0], projected[1]) * factor

    horizontal_scale = float(calibration.get("horizontal_scale") or calibration.get("scale") or 0.0)
    vertical_scale = float(calibration.get("vertical_scale") or calibration.get("scale") or 0.0)
    if horizontal_scale <= 0 or vertical_scale <= 0:
        return 0.0
    delta_x_cm = (float(end[0]) - float(start[0])) / horizontal_scale
    delta_y_cm = (float(end[1]) - float(start[1])) / vertical_scale
    return math.hypot(delta_x_cm, delta_y_cm)


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
        if contour is not None and manual_result:
            source = "auto_after_manual_roi"
            detection_quality = 0.82
            processing = {
                **processing,
                "method": "full_image_contour_after_roi",
                "full_image_fallback": True,
            }
    if contour is None:
        return None

    calibration = reference_plane_calibration(contour, real_width, real_height)
    if calibration is None:
        return None

    horizontal_scale = calibration["horizontal_pixels_per_cm"]
    vertical_scale = calibration["vertical_pixels_per_cm"]
    scale = calibration["scale"]
    calibration_quality = min(
        calibration["axis_consistency"],
        0.7 + calibration["perspective_consistency"] * 0.3,
    )
    processing = {
        **processing,
        "perspective_rectified": True,
        "perspective_consistency": round(float(calibration["perspective_consistency"]), 4),
        "plane_size_cm": [round(float(value), 3) for value in calibration["plane_size_cm"]],
        "corners": [
            [round(float(point[0]), 2), round(float(point[1]), 2)]
            for point in calibration["ordered_corners"]
        ],
    }
    return {
        "scale": float(scale),
        "horizontal_scale": float(horizontal_scale),
        "vertical_scale": float(vertical_scale),
        "homography": calibration["homography"],
        "homography_distance_factor": 1.0,
        "contour": contour,
        "area": float(cv2.contourArea(contour)),
        "source": source,
        "quality": round(float(min(calibration_quality, detection_quality)), 4),
        "axis_scales": [round(float(horizontal_scale), 4), round(float(vertical_scale), 4)],
        "processing": processing,
    }


def _pose_worker(image, model_path, want_segmentation, queue):
    """Runs in a separate process. MediaPipe's segmentation output has a
    known upstream crash on some photos on the Linux CPU delegate
    (SIGABRT: "Check failed: 1 == ChannelSize()",
    google-ai-edge/mediapipe#5394, #4757) that Python cannot catch as an
    exception because it aborts the whole process. Isolating detection here
    means that abort only kills this worker, not the FastAPI service.
    """
    try:
        if mp is None:
            queue.put(("error", "mediapipe_unavailable"))
            return

        BaseOptions = mp.tasks.BaseOptions
        PoseLandmarker = mp.tasks.vision.PoseLandmarker
        PoseLandmarkerOptions = mp.tasks.vision.PoseLandmarkerOptions
        VisionRunningMode = mp.tasks.vision.RunningMode

        options = PoseLandmarkerOptions(
            base_options=BaseOptions(model_asset_path=model_path),
            running_mode=VisionRunningMode.IMAGE,
            num_poses=1,
            output_segmentation_masks=want_segmentation,
        )
        landmarker = PoseLandmarker.create_from_options(options)

        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=image_rgb)
        result = landmarker.detect(mp_image)

        if not result.pose_landmarks:
            queue.put(("ok", None))
            return

        landmarks = [(lm.x, lm.y, lm.visibility) for lm in result.pose_landmarks[0]]
        mask = None
        if result.segmentation_masks:
            mask = np.array(result.segmentation_masks[0].numpy_view(), copy=True)
        queue.put(("ok", {"landmarks": landmarks, "mask": mask}))
    except Exception as exc:  # noqa: BLE001 - report back instead of losing the worker silently
        queue.put(("error", str(exc)))


def _run_pose_detection(image, want_segmentation):
    ctx = multiprocessing.get_context("spawn")
    queue = ctx.Queue()
    process = ctx.Process(target=_pose_worker, args=(image, MODEL_PATH, want_segmentation, queue))
    process.start()

    # Drain the queue before join(): a large payload (segmentation mask, a
    # few MB) can exceed the OS pipe buffer, so the child's feeder thread
    # may still be flushing after the child process itself has exited.
    # join()-then-get_nowait() can race and miss data that arrives moments
    # later, which looks exactly like a crash. Poll get() with a short
    # timeout instead of one long blocking call, so a genuine crash (no
    # data will ever arrive) is noticed in ~1s rather than the full
    # timeout, while a slow-but-alive worker still gets the full budget.
    status, payload = None, None
    deadline = time.time() + POSE_SUBPROCESS_TIMEOUT_SECONDS
    while time.time() < deadline:
        try:
            status, payload = queue.get(timeout=0.5)
            break
        except Exception:
            if not process.is_alive():
                try:
                    status, payload = queue.get(timeout=0.5)
                except Exception:
                    status, payload = None, None
                break

    process.join(5)
    if process.is_alive():
        process.terminate()
        process.join()

    if status != "ok":
        return None

    return payload


def detect_pose(image):
    # Try with segmentation first for the best silhouette quality (the
    # BodyM ML model is trained on it). If the worker process crashes or
    # times out, retry without segmentation - build_body_mask() falls back
    # to OpenCV GrabCut when no mask is available.
    # The Linux CPU delegate can abort inside MediaPipe when native
    # segmentation output is requested. The downstream OpenCV mask builder
    # already handles the no-mask case, so pose detection only needs landmarks.
    payload = _run_pose_detection(image, want_segmentation=False)

    if payload is None:
        return None

    h, w = image.shape[:2]
    landmarks = payload["landmarks"]

    def point(idx):
        x, y, visibility = landmarks[idx]
        return (x * w, y * h, visibility)

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
    visible_count = sum(1 for _, _, visibility in landmarks if visibility > 0.5)

    return {
        "keypoints": keypoints,
        "confidence": round(visible_count / 33, 2),
        "segmentation_mask": payload["mask"],
    }


def point_xy(point):
    return (point[0], point[1])


def point_visibility(point):
    return float(point[2]) if len(point) >= 3 else 1.0


def reference_image_side(keypoints, ref_contour):
    if ref_contour is None:
        return None

    shoulder_center = midpoint(
        point_xy(keypoints["left_shoulder"]),
        point_xy(keypoints["right_shoulder"]),
    )
    hip_center = midpoint(
        point_xy(keypoints["left_hip"]),
        point_xy(keypoints["right_hip"]),
    )
    body_center_x = average(shoulder_center[0], hip_center[0])
    ref_x, _, ref_width, _ = cv2.boundingRect(ref_contour)
    reference_center_x = ref_x + ref_width / 2.0

    return {
        "held_image_side": "left" if reference_center_x < body_center_x else "right",
        "free_image_side": "right" if reference_center_x < body_center_x else "left",
        "reference_center_x": float(reference_center_x),
        "body_center_x": float(body_center_x),
    }


def _arm_path_length(keypoints, arm):
    shoulder = keypoints[f"{arm}_shoulder"]
    elbow = keypoints[f"{arm}_elbow"]
    wrist = keypoints[f"{arm}_wrist"]
    return (
        euclidean_distance(point_xy(shoulder), point_xy(elbow))
        + euclidean_distance(point_xy(elbow), point_xy(wrist))
    )


def select_arm_for_measurement(keypoints, ref_contour=None, reference_mode="fixed"):
    candidates = {}
    side_info = reference_image_side(keypoints, ref_contour)
    for arm in ("left", "right"):
        shoulder = keypoints[f"{arm}_shoulder"]
        elbow = keypoints[f"{arm}_elbow"]
        wrist = keypoints[f"{arm}_wrist"]
        length_px = _arm_path_length(keypoints, arm)
        visibility = min(
            point_visibility(shoulder),
            point_visibility(elbow),
            point_visibility(wrist),
        )
        vertical_order = 1.0 if elbow[1] >= shoulder[1] and wrist[1] >= elbow[1] else 0.35
        horizontal_drift = abs(wrist[0] - shoulder[0]) / max(1.0, length_px)
        relaxed_score = max(0.0, 1.0 - min(1.0, horizontal_drift)) * vertical_order
        distance_from_reference = (
            abs(wrist[0] - side_info["reference_center_x"])
            if side_info
            else 0.0
        )
        candidates[arm] = {
            "length_px": float(length_px),
            "visibility": float(visibility),
            "relaxed_score": float(relaxed_score),
            "distance_from_reference_px": float(distance_from_reference),
        }

    if reference_mode != "handheld":
        return {
            "selected_arm": "average",
            "length_px": average(
                candidates["left"]["length_px"],
                candidates["right"]["length_px"],
            ),
            "candidates": candidates,
            **(side_info or {}),
        }

    max_reference_distance = max(
        1.0,
        candidates["left"]["distance_from_reference_px"],
        candidates["right"]["distance_from_reference_px"],
    )
    for candidate in candidates.values():
        distance_score = candidate["distance_from_reference_px"] / max_reference_distance
        candidate["selection_score"] = (
            distance_score * 0.55
            + candidate["visibility"] * 0.25
            + candidate["relaxed_score"] * 0.20
        )

    selected_arm = max(
        candidates,
        key=lambda arm: candidates[arm]["selection_score"],
    )
    return {
        "selected_arm": selected_arm,
        "length_px": candidates[selected_arm]["length_px"],
        "candidates": candidates,
        **(side_info or {}),
    }


def normalize_handheld_mask(mask, keypoints, ref_contour, view):
    """Mirror the unobstructed body half over the arm holding the reference.

    Only frontal views are normalized. A side view must keep its genuine depth,
    so the capture guide places the held reference below and outside the torso.
    """
    side_info = reference_image_side(keypoints, ref_contour)
    if view not in ("front", "back") or side_info is None or mask is None or mask.size == 0:
        return mask, {
            "applied": False,
            "reason": "side_view_preserved" if view == "side" else "reference_side_unavailable",
        }

    height, width = mask.shape[:2]
    center_x = int(round(side_info["body_center_x"]))
    center_x = max(1, min(width - 2, center_x))
    shoulder_y = average(
        keypoints["left_shoulder"][1],
        keypoints["right_shoulder"][1],
    )
    hip_y = average(keypoints["left_hip"][1], keypoints["right_hip"][1])
    held_semantic_arm = min(
        ("left", "right"),
        key=lambda arm: abs(
            keypoints[f"{arm}_wrist"][0] - side_info["reference_center_x"]
        ),
    )
    held_wrist_y = keypoints[f"{held_semantic_arm}_wrist"][1]
    y_start = max(0, int(round(shoulder_y - height * 0.035)))
    y_end = min(height, int(round(max(hip_y, held_wrist_y) + height * 0.045)))
    if y_end <= y_start:
        return mask, {"applied": False, "reason": "invalid_normalization_band"}

    normalized = np.where(mask > 0, 255, 0).astype(np.uint8)
    if side_info["held_image_side"] == "right":
        target_x = np.arange(center_x, width)
    else:
        target_x = np.arange(0, center_x + 1)
    source_x = (2 * center_x - target_x).astype(int)
    valid = (source_x >= 0) & (source_x < width)
    normalized[y_start:y_end, target_x[valid]] = mask[y_start:y_end, source_x[valid]]

    if ref_contour is not None:
        cv2.drawContours(normalized, [ref_contour], -1, 0, thickness=cv2.FILLED)
    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (5, 5))
    normalized = cv2.morphologyEx(normalized, cv2.MORPH_CLOSE, kernel, iterations=1)
    normalized = isolate_pose_component(normalized, keypoints)

    return normalized, {
        "applied": True,
        "held_image_side": side_info["held_image_side"],
        "free_image_side": side_info["free_image_side"],
        "held_semantic_arm": held_semantic_arm,
        "body_center_x": round(float(side_info["body_center_x"]), 2),
        "reference_center_x": round(float(side_info["reference_center_x"]), 2),
        "y_band": [int(y_start), int(y_end)],
    }


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


def _select_silhouette_component(candidate, ref_contour=None):
    """Keep the most plausible full-body component from an OpenCV mask."""
    if candidate is None or candidate.size == 0:
        return np.zeros((0, 0), dtype=np.uint8)

    mask = np.where(candidate > 0, 255, 0).astype(np.uint8)
    h, w = mask.shape[:2]
    if ref_contour is not None:
        cv2.drawContours(mask, [ref_contour], -1, 0, thickness=cv2.FILLED)

    kernel_size = max(3, int(round(min(h, w) * 0.012)))
    if kernel_size % 2 == 0:
        kernel_size += 1
    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (kernel_size, kernel_size))
    mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel, iterations=1)
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel, iterations=2)

    count, labels, stats, _ = cv2.connectedComponentsWithStats(mask, connectivity=8)
    if count <= 1:
        return np.zeros_like(mask)

    image_area = float(max(1, h * w))
    best_label = 0
    best_score = 0.0
    for label in range(1, count):
        x, y, bound_w, bound_h, area = stats[label]
        height_ratio = bound_h / max(1.0, float(h))
        area_ratio = area / image_area
        center_x = (x + bound_w / 2) / max(1.0, float(w))
        center_quality = max(0.0, 1.0 - abs(center_x - 0.5) * 2.0)
        edge_penalty = 0.35 if x <= 1 or x + bound_w >= w - 1 else 1.0

        if area_ratio < 0.002 or height_ratio < 0.28:
            continue
        score = (
            min(1.0, height_ratio / 0.82) * 0.55
            + min(1.0, area_ratio / 0.18) * 0.25
            + center_quality * 0.20
        ) * edge_penalty
        if score > best_score:
            best_label = label
            best_score = score

    if best_label == 0:
        return np.zeros_like(mask)

    selected = np.where(labels == best_label, 255, 0).astype(np.uint8)
    return cv2.morphologyEx(selected, cv2.MORPH_CLOSE, kernel, iterations=1)


def extract_silhouette_without_pose(image, ref_contour=None):
    """Extract a full-body silhouette without relying on landmark detection.

    The normal protocol keeps the person near the center of the frame. GrabCut
    uses that geometry first, then a border-color contrast mask is used for
    plain backgrounds where GrabCut cannot separate the subject reliably.
    """
    if image is None or image.ndim != 3 or image.shape[0] < 32 or image.shape[1] < 32:
        return np.zeros((0, 0), dtype=np.uint8)

    height, width = image.shape[:2]
    candidates = []
    rect_x = max(1, int(round(width * 0.08)))
    rect_y = max(1, int(round(height * 0.015)))
    rect_w = max(2, min(width - rect_x - 1, int(round(width * 0.84))))
    rect_h = max(2, min(height - rect_y - 1, int(round(height * 0.965))))
    grabcut_mask = np.zeros((height, width), dtype=np.uint8)
    bgd_model = np.zeros((1, 65), np.float64)
    fgd_model = np.zeros((1, 65), np.float64)
    try:
        cv2.grabCut(
            image,
            grabcut_mask,
            (rect_x, rect_y, rect_w, rect_h),
            bgd_model,
            fgd_model,
            4,
            cv2.GC_INIT_WITH_RECT,
        )
        candidates.append(np.where((grabcut_mask == 1) | (grabcut_mask == 3), 255, 0).astype(np.uint8))
    except cv2.error:
        pass

    border = np.concatenate(
        (
            image[0, :, :],
            image[-1, :, :],
            image[:, 0, :],
            image[:, -1, :],
        ),
        axis=0,
    ).astype(np.float32)
    background_color = np.median(border, axis=0)
    color_distance = np.linalg.norm(image.astype(np.float32) - background_color, axis=2)
    distance_threshold = max(12.0, float(np.percentile(color_distance, 68)))
    contrast_mask = np.where(color_distance >= distance_threshold, 255, 0).astype(np.uint8)
    candidates.append(contrast_mask)

    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    gray = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8)).apply(gray)
    _, dark_mask = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    candidates.append(dark_mask)

    best = np.zeros((height, width), dtype=np.uint8)
    best_area = 0
    best_height = 0
    for candidate in candidates:
        selected = _select_silhouette_component(candidate, ref_contour)
        if selected.size == 0:
            continue
        body_bounds = largest_body_bounds(selected)
        if body_bounds is None:
            continue
        _, _, bound_w, bound_h = body_bounds
        area = cv2.countNonZero(selected)
        # Prefer a tall subject, then prefer the larger clean component.
        if bound_h > best_height or (bound_h == best_height and area > best_area):
            best = selected
            best_area = area
            best_height = bound_h

    return best


def _silhouette_row_span(mask, y, band=4):
    """Return the outer foreground span around one body level."""
    if mask is None or mask.size == 0:
        return None
    height, _ = mask.shape[:2]
    y = int(max(0, min(height - 1, round(y))))
    top = max(0, y - band)
    bottom = min(height, y + band + 1)
    columns = np.where(mask[top:bottom, :].max(axis=0) > 0)[0]
    if len(columns) < 2:
        return None
    return float(columns[0]), float(columns[-1])


def infer_pose_from_silhouette(mask, view):
    """Build stable proxy landmarks from a valid silhouette.

    These points are used only for level sampling and scale fallback. The
    learned estimator still receives the complete front/side masks.
    """
    body_bounds = largest_body_bounds(mask)
    if body_bounds is None:
        return None

    x, y, width, height = body_bounds
    center_x = x + width / 2.0

    def span_at(ratio, default_width):
        span = _silhouette_row_span(mask, y + height * ratio)
        if span is None:
            half = default_width / 2.0
            return center_x - half, center_x + half
        return span

    def point_from_span(ratio, side, default_width):
        left, right = span_at(ratio, default_width)
        return (left if side == "left" else right, y + height * ratio, 0.58)

    shoulder_span = span_at(0.18, max(8.0, width * 0.36))
    hip_span = span_at(0.49, max(8.0, width * 0.28))
    knee_span = span_at(0.74, max(6.0, width * 0.14))
    ankle_span = span_at(0.98, max(4.0, width * 0.10))
    elbow_span = span_at(0.35, max(8.0, width * 0.32))
    wrist_span = span_at(0.49, max(6.0, width * 0.28))

    # A side view still needs two x positions for the existing width sampler;
    # the narrow span correctly represents body depth rather than inventing a
    # front-view shoulder width.
    keypoints = {
        "nose": (center_x, y + height * 0.08, 0.58),
        "left_shoulder": (shoulder_span[0], y + height * 0.18, 0.58),
        "right_shoulder": (shoulder_span[1], y + height * 0.18, 0.58),
        "left_elbow": (elbow_span[0], y + height * 0.35, 0.58),
        "right_elbow": (elbow_span[1], y + height * 0.35, 0.58),
        "left_wrist": (wrist_span[0], y + height * 0.49, 0.58),
        "right_wrist": (wrist_span[1], y + height * 0.49, 0.58),
        "left_hip": (hip_span[0], y + height * 0.49, 0.58),
        "right_hip": (hip_span[1], y + height * 0.49, 0.58),
        "left_knee": (knee_span[0], y + height * 0.74, 0.58),
        "right_knee": (knee_span[1], y + height * 0.74, 0.58),
        "left_ankle": (ankle_span[0], y + height * 0.98, 0.58),
        "right_ankle": (ankle_span[1], y + height * 0.98, 0.58),
    }
    return {
        "keypoints": keypoints,
        "confidence": 0.58,
        "segmentation_mask": None,
        "detector": "opencv_silhouette",
        "view": view,
    }


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


def constrained_span_at_y(mask, y, center_x, max_width_px, band=4):
    h, w = mask.shape
    y = int(max(0, min(h - 1, y)))
    max_width_px = max(8.0, min(float(max_width_px), float(w)))
    center_x = float(max(0, min(w - 1, center_x)))
    x_min = int(max(0, round(center_x - max_width_px / 2)))
    x_max = int(min(w - 1, round(center_x + max_width_px / 2)))
    if x_max <= x_min:
        return 0.0

    band = max(1, int(round(band)))
    band_top = max(0, y - band)
    band_bottom = min(h, y + band + 1)
    rows = mask[band_top:band_bottom, x_min:x_max + 1]
    local_center = center_x - x_min
    row_spans = []
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
            row_spans.append((float(best[0] + x_min), float(best[1] + x_min)))

    if not row_spans:
        return None
    left = float(np.median([span[0] for span in row_spans]))
    right = float(np.median([span[1] for span in row_spans]))
    return (left, right) if right > left else None


def constrained_width_at_y(mask, y, center_x, max_width_px, band=4):
    span = constrained_span_at_y(mask, y, center_x, max_width_px, band)
    if span is None:
        return 0.0

    return float(span[1] - span[0] + 1)


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


def estimated_scale_from_pose(points, assumed_stature_cm=DEFAULT_ESTIMATED_STATURE_CM):
    ankle_y = average(points["left_ankle"][1], points["right_ankle"][1])
    pose_span_px = max(0.0, ankle_y - points["nose"][1])
    if pose_span_px <= 0:
        return None
    pixels_per_cm = (pose_span_px * 1.08) / assumed_stature_cm
    if pixels_per_cm <= 0:
        return None
    return {
        "scale": float(pixels_per_cm),
        "stature_cm": float(assumed_stature_cm),
    }


def anthropometric_scale_result(points, reason, assumed_stature_cm=DEFAULT_ESTIMATED_STATURE_CM):
    estimated = estimated_scale_from_pose(points, assumed_stature_cm)
    if estimated is None:
        return None
    return {
        "scale": estimated["scale"],
        "horizontal_scale": estimated["scale"],
        "vertical_scale": estimated["scale"],
        "homography": None,
        "homography_distance_factor": 1.0,
        "contour": None,
        "area": 0.0,
        "source": "anthropometric_pose_estimate",
        "quality": 0.56,
        "axis_scales": [round(float(estimated["scale"]), 4), round(float(estimated["scale"]), 4)],
        "estimated_stature_cm": round(float(estimated["stature_cm"]), 2),
        "processing": {
            "method": "pose_stature_fallback",
            "reason": reason,
            "assumed_stature_cm": assumed_stature_cm,
            "refined": False,
        },
    }


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


def usable_geometric_measurements(data):
    """Return whether the non-model silhouette measurements are usable."""
    if impossible_measurements(data):
        return False
    return all(float(data.get(field, 0.0) or 0.0) > 0 for field in MEASUREMENT_LIMITS_CM)


def process_measurement(
    front_bytes,
    side_bytes,
    back_bytes,
    ref_object,
    ref_width_cm=None,
    ref_height_cm=None,
    reference_boxes=None,
    progress_callback=None,
    reference_mode="fixed",
):
    started_at = time.perf_counter()
    reference_mode = reference_mode if reference_mode in ("fixed", "handheld") else "fixed"

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
    calibrations = {}
    scale_sources = {}
    scale_qualities = {}
    reference_axis_scales = {}
    reference_processing = {}
    reference_contours = {}
    poses = {}
    masks = {}
    bounds = {}
    pose_statures = {}
    pose_fallback_views = []
    handheld_mask_diagnostics = {}
    model_fallback_diagnostics = None

    view_progress = {
        "front": (16, 25),
        "side": (32, 41),
        "back": (48, 57),
    }

    for view, image in images.items():
        reference_percent, body_percent = view_progress[view]
        label = VIEW_LABELS.get(view, f"foto {view}")
        progress(
            "reference_roi",
            reference_percent,
            f"Mendeteksi empat tepi dan mengoreksi perspektif benda patokan pada {label}",
            view,
        )
        scale_result = calculate_scale(
            image,
            ref_object,
            ref_width_cm,
            ref_height_cm,
            (reference_boxes or {}).get(view),
        )
        detected_reference_contour = scale_result.get("contour") if scale_result else None

        progress("body_segmentation", body_percent, f"Mendeteksi pose dan siluet pada {label}", view)
        pose = detect_pose(image)
        mask = None
        if pose is None:
            mask = extract_silhouette_without_pose(
                image,
                detected_reference_contour,
            )
            if largest_body_bounds(mask) is None:
                return {
                    "success": False,
                    "error": f"Tubuh dan siluet tidak terbaca pada {label}. Pastikan seluruh badan terlihat jelas dan background cukup kontras.",
                    "failed_view": view,
                    "failed_reason": "pose_not_detected",
                    "correction": "Ambil ulang foto dengan kepala sampai kaki terlihat, tubuh berada di tengah, dan background tidak menyatu dengan pakaian.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                }
            pose = infer_pose_from_silhouette(mask, view)
            if pose is None:
                return {
                    "success": False,
                    "error": f"Siluet tubuh tidak cukup utuh pada {label} untuk menghitung ukuran.",
                    "failed_view": view,
                    "failed_reason": "silhouette_not_detected",
                    "correction": "Pastikan kepala sampai kaki terlihat penuh dan tidak tertutup benda lain.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                }
            pose_fallback_views.append(view)

        points = pose["keypoints"]
        scale_reason = None
        if scale_result is None:
            scale_reason = "reference_not_detected"
        elif scale_result.get("quality", 0.0) < 0.72:
            scale_reason = "low_reference_quality"

        if scale_reason:
            fallback_scale = anthropometric_scale_result(points, scale_reason)
            if fallback_scale is None:
                return {
                    "success": False,
                    "error": f"Pose tubuh pada {label} tidak cukup lengkap untuk estimasi ukuran. Pastikan kepala sampai kaki terlihat jelas.",
                    "failed_view": view,
                    "failed_reason": "pose_scale_not_available",
                    "correction": "Ambil ulang foto dengan kepala sampai kaki terlihat penuh.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                }
            scale_result = fallback_scale

        ankle_x = average(points["left_ankle"][0], points["right_ankle"][0])
        ankle_y = average(points["left_ankle"][1], points["right_ankle"][1])
        pose_span_px = max(0.0, ankle_y - points["nose"][1])
        stature_cm = calibrated_distance_cm(
            point_xy(points["nose"]),
            (ankle_x, ankle_y),
            scale_result,
        ) * 1.08
        if stature_cm < 110 or stature_cm > 230:
            fallback_scale = anthropometric_scale_result(points, "invalid_reference_scale")
            if fallback_scale is None:
                return {
                    "success": False,
                    "error": f"Pose tubuh pada {label} tidak cukup lengkap untuk estimasi ukuran. Pastikan kepala sampai kaki terlihat jelas.",
                    "failed_view": view,
                    "failed_reason": "pose_scale_not_available",
                    "correction": "Ambil ulang foto dengan kepala sampai kaki terlihat penuh.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                }
            scale_result = fallback_scale
            stature_cm = float(scale_result["estimated_stature_cm"])

        if mask is None:
            mask = build_body_mask(
                image,
                points,
                detected_reference_contour,
                pose.get("segmentation_mask"),
            )
        if reference_mode == "handheld":
            mask, handheld_mask_diagnostics[view] = normalize_handheld_mask(
                mask,
                points,
                detected_reference_contour,
                view,
            )
        body_bounds = largest_body_bounds(mask)
        if body_bounds is None:
            fallback_mask = extract_silhouette_without_pose(
                image,
                detected_reference_contour,
            )
            if reference_mode == "handheld":
                fallback_mask, handheld_mask_diagnostics[view] = normalize_handheld_mask(
                    fallback_mask,
                    points,
                    detected_reference_contour,
                    view,
                )
            fallback_bounds = largest_body_bounds(fallback_mask)
            if fallback_bounds is None:
                return {
                    "success": False,
                    "error": f"Siluet tubuh tidak terbaca pada {label}. Gunakan background polos dan pencahayaan cukup.",
                    "failed_view": view,
                    "failed_reason": "silhouette_not_detected",
                    "correction": "Ambil ulang foto dengan kontras tubuh dan background yang lebih jelas.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                }
            mask = fallback_mask
            body_bounds = fallback_bounds
            if view not in pose_fallback_views:
                pose_fallback_views.append(view)

        scales[view] = scale_result["scale"]
        calibrations[view] = scale_result
        scale_sources[view] = scale_result["source"]
        scale_qualities[view] = scale_result.get("quality", 0.5)
        reference_axis_scales[view] = scale_result.get("axis_scales", [])
        reference_processing[view] = scale_result.get("processing", {})
        reference_contours[view] = detected_reference_contour
        poses[view] = pose
        masks[view] = mask
        bounds[view] = body_bounds
        pose_statures[view] = stature_cm

    progress(
        "cross_view_scale",
        64,
        "Menyelaraskan skala fisik dan level anatomi foto depan, samping, dan belakang",
    )
    raw_scale_consistency = min(scales.values()) / max(scales.values())
    raw_stature_consistency = min(pose_statures.values()) / max(pose_statures.values())
    calibrated_scale_count = sum(
        1
        for source in scale_sources.values()
        if source != "anthropometric_pose_estimate"
    )
    handheld_target_stature_cm = None
    if reference_mode == "handheld":
        progress(
            "handheld_normalization",
            68,
            "Menetralkan pengaruh tangan pemegang dan menyelaraskan tiga foto",
        )
        calibrated_statures = [
            pose_statures[view]
            for view in ("front", "side", "back")
            if scale_sources[view] != "anthropometric_pose_estimate"
            and 125 <= pose_statures[view] <= 215
        ]
        stature_candidates = calibrated_statures or list(pose_statures.values())
        handheld_target_stature_cm = float(np.median(stature_candidates))
        handheld_target_stature_cm = max(125.0, min(215.0, handheld_target_stature_cm))
        quality_cap = 0.74 if ref_object == "ktp" else 0.82
        for view in ("front", "side", "back"):
            points = poses[view]["keypoints"]
            ankle_y = average(points["left_ankle"][1], points["right_ankle"][1])
            pose_span_px = max(1.0, ankle_y - points["nose"][1])
            old_scale = float(scales[view])
            scales[view] = (pose_span_px * 1.08) / handheld_target_stature_cm
            calibration = calibrations[view]
            if calibration.get("homography") is not None:
                calibration["homography_distance_factor"] = (
                    float(calibration.get("homography_distance_factor", 1.0))
                    * old_scale
                    / scales[view]
                )
            else:
                calibration["horizontal_scale"] = scales[view]
                calibration["vertical_scale"] = scales[view]
            calibration["scale"] = scales[view]
            pose_statures[view] = handheld_target_stature_cm
            scale_qualities[view] = min(scale_qualities[view], quality_cap)
            reference_processing[view] = {
                **reference_processing[view],
                "handheld_cross_view_normalized": True,
                "normalized_stature_cm": round(handheld_target_stature_cm, 2),
            }

    scale_consistency = min(scales.values()) / max(scales.values())
    stature_consistency = min(pose_statures.values()) / max(pose_statures.values())
    if (
        reference_mode == "fixed"
        and calibrated_scale_count >= 2
        and (scale_consistency < 0.72 or stature_consistency < 0.82)
    ):
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
    arm_selections = {
        view: select_arm_for_measurement(
            poses[view]["keypoints"],
            reference_contours[view],
            reference_mode,
        )
        for view in ("front", "side", "back")
    }

    width_debug = {}

    def body_width_cm(view, level_name, scale):
        levels = front_levels if view == "front" else side_levels if view == "side" else back_levels
        keypoints = poses[view]["keypoints"]
        target_y = levels[level_name]
        selected_arm = arm_selections[view].get("selected_arm")
        if reference_mode == "handheld" and selected_arm in ("left", "right") and level_name in ("upper_arm", "wrist"):
            if level_name == "upper_arm":
                centers_x = [x_at_y(
                    keypoints[f"{selected_arm}_shoulder"],
                    keypoints[f"{selected_arm}_elbow"],
                    target_y,
                )]
            else:
                target_y = keypoints[f"{selected_arm}_wrist"][1]
                centers_x = [keypoints[f"{selected_arm}_wrist"][0]]
        else:
            centers_x = level_centers_x(keypoints, level_name, target_y)
        max_width = level_window_px(keypoints, level_name, view)
        body_height_px = max(
            1.0,
            average(keypoints["left_ankle"][1], keypoints["right_ankle"][1])
            - keypoints["nose"][1],
        )
        band_factor = 0.012 if level_name in ("neck", "chest", "waist", "hips") else 0.008
        sampling_band = max(4, min(16, int(round(body_height_px * band_factor))))
        raw_width = width_at_y(masks[view], target_y)
        sampled_spans = [
            constrained_span_at_y(
                masks[view],
                target_y,
                center_x,
                max_width,
                sampling_band,
            )
            for center_x in centers_x
        ]
        sampled_widths = [
            float(span[1] - span[0] + 1) if span is not None else 0.0
            for span in sampled_spans
        ]
        sampled_widths_cm = [
            calibrated_distance_cm(
                (span[0], target_y),
                (span[1] + 1, target_y),
                calibrations[view],
            )
            if span is not None
            else 0.0
            for span in sampled_spans
        ]
        valid_widths = [width for width in sampled_widths if width > 0]
        valid_widths_cm = [width for width in sampled_widths_cm if width > 0]
        constrained_width = average(*valid_widths)
        constrained_width_cm = average(*valid_widths_cm)
        if constrained_width_cm <= 0 and len(centers_x) == 1:
            raw_span = _silhouette_row_span(masks[view], target_y, sampling_band)
            if raw_span is not None:
                constrained_width = min(float(raw_span[1] - raw_span[0] + 1), max_width)
                half_width = constrained_width / 2.0
                constrained_width_cm = calibrated_distance_cm(
                    (centers_x[0] - half_width, target_y),
                    (centers_x[0] + half_width, target_y),
                    calibrations[view],
                )

        width_debug[f"{view}_{level_name}"] = {
            "raw_px": round(float(raw_width), 2),
            "used_px": round(float(constrained_width), 2),
            "used_cm": round(float(constrained_width_cm), 2),
            "window_px": round(float(max_width), 2),
            "sampling_band_px": int(sampling_band),
            "centers_x": [round(float(center_x), 2) for center_x in centers_x],
            "samples_px": [round(float(width), 2) for width in sampled_widths],
            "samples_cm": [round(float(width), 2) for width in sampled_widths_cm],
            "selected_arm": selected_arm,
        }

        return constrained_width_cm

    def circumference(level_name):
        front_width = average(
            body_width_cm("front", level_name, front_scale),
            body_width_cm("back", level_name, back_scale),
        )
        side_depth = body_width_cm("side", level_name, side_scale)
        return rounded(ellipse_circumference(front_width, side_depth))

    def arm_path_cm(view):
        keypoints = poses[view]["keypoints"]
        selected_arm = arm_selections[view]["selected_arm"]
        arms = ("left", "right") if selected_arm == "average" else (selected_arm,)
        lengths = []
        for arm in arms:
            shoulder = point_xy(keypoints[f"{arm}_shoulder"])
            elbow = point_xy(keypoints[f"{arm}_elbow"])
            wrist = point_xy(keypoints[f"{arm}_wrist"])
            lengths.append(
                calibrated_distance_cm(shoulder, elbow, calibrations[view])
                + calibrated_distance_cm(elbow, wrist, calibrations[view])
            )
        return average(*lengths)

    back_points = poses["back"]["keypoints"]
    shoulder_width = average(
        calibrated_distance_cm(
            point_xy(front_points["left_shoulder"]),
            point_xy(front_points["right_shoulder"]),
            calibrations["front"],
        ),
        calibrated_distance_cm(
            point_xy(back_points["left_shoulder"]),
            point_xy(back_points["right_shoulder"]),
            calibrations["back"],
        ),
    )
    arm_length = arm_path_cm("front")

    front_x, front_y, front_w, front_h = bounds["front"]
    height = average(pose_statures["front"], pose_statures["back"])
    hip_mid = midpoint(point_xy(front_points["left_hip"]), point_xy(front_points["right_hip"]))
    ankle_mid = midpoint(point_xy(front_points["left_ankle"]), point_xy(front_points["right_ankle"]))
    shoulder_mid = midpoint(point_xy(front_points["left_shoulder"]), point_xy(front_points["right_shoulder"]))
    inseam_start_y = hip_mid[1] + front_h * 0.08
    inseam_cm = calibrated_distance_cm(
        (ankle_mid[0], inseam_start_y),
        ankle_mid,
        calibrations["front"],
    )
    outseam_cm = calibrated_distance_cm(
        (ankle_mid[0], front_levels["waist"]),
        ankle_mid,
        calibrations["front"],
    )
    shirt_length_cm = calibrated_distance_cm(
        (shoulder_mid[0], shoulder_mid[1]),
        (shoulder_mid[0], front_levels["hips"]),
        calibrations["front"],
    )

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
        "shirt_length": rounded(shirt_length_cm),
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
        "inseam": rounded(inseam_cm),
        "outseam": rounded(outseam_cm),
        "rise": rounded(max(0.0, outseam_cm - inseam_cm)),
    }

    bodym_result = None
    if bodym_enabled():
        progress("bodym_features", 82, "Menyusun fitur siluet dari bentuk tubuh")
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
            if not usable_geometric_measurements(data):
                return {
                    "success": False,
                    "error": str(exc),
                    "failed_view": failed_view,
                    "failed_reason": exc.code,
                    "correction": "Pastikan siluet foto depan dan samping utuh, tubuh penuh terlihat, serta background cukup kontras.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                    "diagnostic_details": exc.details,
                }
            model_fallback_diagnostics = {
                "status": "geometric_fallback",
                "reason": exc.code,
                "details": exc.details,
            }
            progress("model_fallback", 87, "Validasi model perlu ditinjau; hasil geometri siluet disiapkan")

        progress("bodym_inference", 87, "Memprediksi 14 indikator ukuran tubuh")
        if bodym_result and bodym_result["status"] == "rejected":
            if not usable_geometric_measurements(data):
                return {
                    "success": False,
                    "error": "Fitur siluet berada di luar pola valid dan geometri foto juga belum cukup untuk menghasilkan ukuran.",
                    "failed_view": "front_side",
                    "failed_reason": "bodym_prediction_rejected",
                    "correction": "Ulangi foto depan dan samping dengan tubuh penuh, background kontras, dan kamera sejajar tubuh.",
                    "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
                    "bodym": bodym_result,
                    "geometric_invalid_measurements": impossible_measurements(data),
                }
            model_fallback_diagnostics = {
                "status": "geometric_fallback",
                "reason": "model_rejected",
                "diagnostic_codes": bodym_result.get("diagnostic_codes", []),
                "implausible_fields": bodym_result.get("implausible_fields", []),
                "ood": bodym_result.get("ood", {}),
            }
            bodym_result = None
            progress("model_fallback", 87, "Siluet model perlu ditinjau; hasil geometri siluet disiapkan")

        if bodym_result:
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
            "error": f"Hasil ukuran tidak masuk akal ({fields}). Ulangi foto dengan tubuh penuh, pose tegak, dan background yang membuat siluet terbaca jelas.",
            "failed_reason": "unrealistic_measurements",
            "failed_view": "multiple",
            "correction": "Periksa kembali siluet pada ketiga foto. A4/KTP hanya menjadi bantuan visual, bukan syarat utama pengukuran.",
            "response_contract_version": BODYM_RESPONSE_CONTRACT_VERSION,
            "invalid_measurements": invalid_measurements,
            "debug": {
                "duration_seconds": round(time.perf_counter() - started_at, 3),
                "scales": {key: round(value, 4) for key, value in scales.items()},
                "body_bounds": {key: [int(v) for v in value] for key, value in bounds.items()},
                "reference_scale_sources": scale_sources,
                "reference_mode": reference_mode,
                "arm_selections": arm_selections,
                "handheld_mask_normalization": handheld_mask_diagnostics,
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

    uses_estimated_scale = any(
        source == "anthropometric_pose_estimate"
        for source in scale_sources.values()
    )
    uses_silhouette_fallback = bool(pose_fallback_views)
    uses_model_fallback = model_fallback_diagnostics is not None
    confidence_caps = []
    if uses_estimated_scale:
        confidence_caps.append(0.72)
    if uses_silhouette_fallback:
        confidence_caps.append(0.78)
    if uses_model_fallback:
        confidence_caps.append(0.68)
    if reference_mode == "handheld":
        confidence_caps.append(0.78 if ref_object == "ktp" else 0.84)
    if confidence_caps:
        confidence_cap = min(confidence_caps)
        quality_score = round(min(quality_score, confidence_cap), 2)
        per_field_confidence = {
            field: round(min(confidence, confidence_cap), 2)
            for field, confidence in per_field_confidence.items()
        }

    photo_diagnostics = {
        view: {
            "reference_detected": scale_sources[view] != "anthropometric_pose_estimate",
            "reference_source": scale_sources[view],
            "reference_quality": round(float(scale_qualities[view]), 4),
            "reference_axis_scales": reference_axis_scales[view],
            "reference_processing": reference_processing[view],
            "pixels_per_cm": round(float(scales[view]), 6),
            "pose_confidence": poses[view]["confidence"],
            "pose_detector": poses[view].get("detector", "mediapipe"),
            "pose_fallback": view in pose_fallback_views,
            "body_bounds": [int(value) for value in bounds[view]],
            "used_by_bodym": view in ("front", "side"),
            "arm_selection": arm_selections[view],
            "handheld_mask_normalization": handheld_mask_diagnostics.get(view, {}),
        }
        for view in ("front", "side", "back")
    }

    result = {
        "success": True,
        "data": data,
        "confidence": quality_score,
        "quality_score": quality_score,
        "ref_detected": not uses_estimated_scale,
        "reference_mode": reference_mode,
        "measurement_method": (
            "bodym_ml"
            if bodym_result
            else "multiview_cv_guarded_fallback"
            if uses_model_fallback
            else "multiview_pose_estimate"
            if uses_estimated_scale
            else "multiview_cv"
        ),
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
            "pose_detectors": {
                key: poses[key].get("detector", "mediapipe")
                for key in poses
            },
            "pose_fallback_views": pose_fallback_views,
            "model_fallback": uses_model_fallback,
            "reference_mode": reference_mode,
            "raw_scale_consistency": round(float(raw_scale_consistency), 4),
            "raw_stature_consistency": round(float(raw_stature_consistency), 4),
            "scale_consistency": round(float(scale_consistency), 4),
            "stature_consistency": round(float(stature_consistency), 4),
            "handheld_target_stature_cm": (
                round(float(handheld_target_stature_cm), 2)
                if handheld_target_stature_cm is not None
                else None
            ),
            "arm_selections": arm_selections,
            "handheld_mask_normalization": handheld_mask_diagnostics,
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
    if model_fallback_diagnostics:
        result["model_fallback"] = model_fallback_diagnostics
    progress("completed", 100, "Analisis selesai dan hasil siap diperiksa")
    return result
