"""
measurement.py — Core Computer Vision logic for body measurement estimation.

Uses MediaPipe Pose for body keypoint detection and OpenCV for reference
object detection. Estimates body measurements by calculating distances
between keypoints and converting pixels to centimeters using a reference
object of known size.
"""
import cv2
import numpy as np
import mediapipe as mp
import urllib.request
import os
from utils import euclidean_distance, pixel_to_cm, get_reference_dimensions, midpoint

# Download MediaPipe task model if it doesn't exist
MODEL_PATH = "pose_landmarker_lite.task"
if not os.path.exists(MODEL_PATH):
    url = "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task"
    urllib.request.urlretrieve(url, MODEL_PATH)

def detect_reference_object(image):
    """
    Detect rectangle-shaped reference object (A4 paper, ATM card) in the image
    using contour detection. Returns the contour with the largest area that
    has approximately 4 corners.

    Returns:
        contour: The detected reference object contour, or None if not found.
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (7, 7), 0)

    # Adaptive threshold for varying lighting conditions
    thresh = cv2.adaptiveThreshold(
        blurred, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY_INV, 11, 2
    )

    # Morphological operations to clean up
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    thresh = cv2.morphologyEx(thresh, cv2.MORPH_CLOSE, kernel, iterations=2)

    contours, _ = cv2.findContours(thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    best_contour = None
    max_area = 0

    for contour in contours:
        area = cv2.contourArea(contour)
        if area < 1000:  # Too small, skip
            continue

        peri = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.02 * peri, True)

        # Rectangle should have ~4 corners
        if 4 <= len(approx) <= 6 and area > max_area:
            max_area = area
            best_contour = contour

    return best_contour


def calculate_scale(image, ref_object, ref_width_cm=None, ref_height_cm=None):
    """
    Calculate scale (pixels per cm) using the reference object.

    Returns:
        scale: pixels per cm, or a default estimate if detection fails.
        detected: whether the reference object was successfully detected.
    """
    real_width, real_height = get_reference_dimensions(
        ref_object, ref_width_cm, ref_height_cm
    )

    contour = detect_reference_object(image)

    if contour is not None:
        rect = cv2.minAreaRect(contour)
        (_, _), (w, h), _ = rect

        # Use the longer dimension matched with the longer real dimension
        pixel_long = max(w, h)
        pixel_short = min(w, h)
        real_long = max(real_width, real_height)
        real_short = min(real_width, real_height)

        # Average scale from both dimensions for accuracy
        scale = ((pixel_long / real_long) + (pixel_short / real_short)) / 2
        return scale, True
    else:
        # Fallback: estimate based on typical body proportions
        # Assume average image height represents ~200cm viewing area
        height = image.shape[0]
        scale = height / 200.0
        return scale, False


def estimate_body_measurements(image):
    """
    Use MediaPipe Pose to detect body keypoints and calculate distances.

    Returns:
        dict with keypoint pixel coordinates, or None if pose not detected.
    """
    BaseOptions = mp.tasks.BaseOptions
    PoseLandmarker = mp.tasks.vision.PoseLandmarker
    PoseLandmarkerOptions = mp.tasks.vision.PoseLandmarkerOptions
    VisionRunningMode = mp.tasks.vision.RunningMode

    options = PoseLandmarkerOptions(
        base_options=BaseOptions(model_asset_path=MODEL_PATH),
        running_mode=VisionRunningMode.IMAGE,
        num_poses=1
    )

    with PoseLandmarker.create_from_options(options) as landmarker:
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=image_rgb)
        
        result = landmarker.detect(mp_image)

        if not result.pose_landmarks:
            return None

        h, w = image.shape[:2]
        # We only configured it for 1 pose, so we take the first index
        landmarks = result.pose_landmarks[0]

        # Extract key points (convert normalized to pixel coordinates)
        def get_point(idx):
            lm = landmarks[idx]
            return (lm.x * w, lm.y * h)

        keypoints = {
            "left_shoulder": get_point(11),
            "right_shoulder": get_point(12),
            "left_elbow": get_point(13),
            "right_elbow": get_point(14),
            "left_wrist": get_point(15),
            "right_wrist": get_point(16),
            "left_hip": get_point(23),
            "right_hip": get_point(24),
            "left_knee": get_point(25),
            "right_knee": get_point(26),
            "left_ankle": get_point(27),
            "right_ankle": get_point(28),
            "nose": get_point(0),
        }

        # Calculate pixel distances
        measurements_px = {}

        # Shoulder width: distance between left and right shoulder
        measurements_px["shoulder_width"] = euclidean_distance(
            keypoints["left_shoulder"], keypoints["right_shoulder"]
        )

        # Chest estimate: shoulder width * 1.6 (anthropometric ratio)
        measurements_px["chest"] = measurements_px["shoulder_width"] * 1.6

        # Waist estimate: hip width * 1.2
        hip_width = euclidean_distance(
            keypoints["left_hip"], keypoints["right_hip"]
        )
        measurements_px["waist"] = hip_width * 1.2

        # Hips estimate: hip width * 1.5
        measurements_px["hips"] = hip_width * 1.5

        # Arm length: shoulder -> elbow -> wrist
        left_arm = (
            euclidean_distance(keypoints["left_shoulder"], keypoints["left_elbow"])
            + euclidean_distance(keypoints["left_elbow"], keypoints["left_wrist"])
        )
        right_arm = (
            euclidean_distance(keypoints["right_shoulder"], keypoints["right_elbow"])
            + euclidean_distance(keypoints["right_elbow"], keypoints["right_wrist"])
        )
        measurements_px["arm_length"] = (left_arm + right_arm) / 2

        # Height: nose to midpoint of ankles (approximate full body height)
        ankle_mid = midpoint(keypoints["left_ankle"], keypoints["right_ankle"])
        body_height = euclidean_distance(keypoints["nose"], ankle_mid)
        # Multiply by ~1.1 to account for head above nose + feet below ankles
        measurements_px["height"] = body_height * 1.1

        # Confidence based on landmark visibility
        visible_count = sum(
            1 for lm in landmarks if lm.visibility > 0.5
        )
        confidence = round(visible_count / 33, 2)

        return {
            "measurements_px": measurements_px,
            "keypoints": keypoints,
            "confidence": confidence,
        }


def process_measurement(image_bytes, ref_object, ref_width_cm=None, ref_height_cm=None):
    """
    Main processing function. Takes image bytes and reference info,
    returns body measurements in centimeters.

    Returns:
        dict with measurement results or error info.
    """
    # Decode image
    nparr = np.frombuffer(image_bytes, np.uint8)
    image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

    if image is None:
        return {"success": False, "error": "Gagal membaca gambar. Pastikan format gambar benar."}

    # Step 1: Calculate scale from reference object
    scale, ref_detected = calculate_scale(
        image, ref_object, ref_width_cm, ref_height_cm
    )

    # Step 2: Detect body pose and measure
    body_result = estimate_body_measurements(image)

    if body_result is None:
        return {
            "success": False,
            "error": "Tidak dapat mendeteksi pose tubuh. Pastikan foto menampilkan seluruh badan dengan jelas."
        }

    # Step 3: Convert pixel measurements to cm
    px = body_result["measurements_px"]
    data = {
        "chest": pixel_to_cm(px["chest"], scale),
        "waist": pixel_to_cm(px["waist"], scale),
        "hips": pixel_to_cm(px["hips"], scale),
        "shoulder_width": pixel_to_cm(px["shoulder_width"], scale),
        "arm_length": pixel_to_cm(px["arm_length"], scale),
        "height_estimate": pixel_to_cm(px["height"], scale),
    }

    return {
        "success": True,
        "data": data,
        "confidence": body_result["confidence"],
        "ref_detected": ref_detected,
    }
