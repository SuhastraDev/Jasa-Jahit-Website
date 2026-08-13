"""
garment_measurement.py — Flat-lay garment (shirt/pants) measurement for ZRINTTAILOR.

Instead of photographing a person's body, the customer photographs an
already well-fitting shirt or pair of pants laid FLAT next to a calibration
marker (A4/KTP), both resting on the same surface. This sidesteps the
structural problems that made body-photo measurement unreliable: no pose
variance, marker and garment are naturally coplanar (both lying flat, unlike
a marker held next to a 3D body), and a garment's silhouette is far cleaner
than a human silhouette (no hair, shadows, or loose-clothing bulk).

Circumference convention: a garment measured flat only gives HALF of its
finished circumference (e.g. armpit-to-armpit width on a shirt is half of
the chest circumference), which is the same convention tailors use when
sizing from an existing garment. Every circumference-type field below is
therefore the scanned flat width multiplied by 2.
"""
import cv2
import numpy as np

from measurement import (
    calculate_scale,
    constrained_width_at_y,
    decode_image,
    resize_for_measurement,
    rounded,
    width_at_y,
)
from utils import euclidean_distance, pixel_to_cm

GARMENT_RESPONSE_CONTRACT_VERSION = "garment-response.v1"

# Garment measurements include sewing ease, so ranges run wider than the
# bare-body limits used for the (now dormant) body-photo pipeline.
GARMENT_MEASUREMENT_LIMITS_CM = {
    "neck": (25, 60),
    "chest": (60, 200),
    "shoulder_width": (30, 70),
    "shirt_length": (35, 100),
    "arm_length": (30, 90),
    "wrist": (12, 35),
    "pants_waist": (50, 180),
    "pants_hips": (60, 200),
    "thigh": (30, 100),
    "inseam": (40, 120),
    "outseam": (60, 140),
    "rise": (15, 55),
    "ankle": (18, 60),
}

GARMENT_LABELS = {
    "neck": "leher",
    "chest": "dada",
    "shoulder_width": "lebar bahu",
    "shirt_length": "panjang baju",
    "arm_length": "panjang lengan",
    "wrist": "pergelangan",
    "pants_waist": "pinggang celana",
    "pants_hips": "pinggul celana",
    "thigh": "paha",
    "inseam": "inseam",
    "outseam": "outseam",
    "rise": "rise/pesak",
    "ankle": "bukaan bawah",
}


def segment_garment(image, ref_contour=None):
    """Separate the garment from a roughly plain background via GrabCut,
    excluding the reference marker's own footprint from the result."""
    h, w = image.shape[:2]
    margin_x = max(1, int(w * 0.04))
    margin_y = max(1, int(h * 0.04))
    rect = (margin_x, margin_y, w - 2 * margin_x, h - 2 * margin_y)

    mask = np.zeros((h, w), np.uint8)
    bgd = np.zeros((1, 65), np.float64)
    fgd = np.zeros((1, 65), np.float64)
    try:
        cv2.grabCut(image, mask, rect, bgd, fgd, 5, cv2.GC_INIT_WITH_RECT)
        garment_mask = np.where((mask == 2) | (mask == 0), 0, 255).astype("uint8")
    except cv2.error:
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        _, garment_mask = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)

    if ref_contour is not None:
        cv2.drawContours(garment_mask, [ref_contour], -1, 0, thickness=cv2.FILLED)

    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
    garment_mask = cv2.morphologyEx(garment_mask, cv2.MORPH_OPEN, kernel, iterations=1)
    garment_mask = cv2.morphologyEx(garment_mask, cv2.MORPH_CLOSE, kernel, iterations=2)
    return isolate_largest_component(garment_mask)


def isolate_largest_component(mask):
    count, labels, stats, _ = cv2.connectedComponentsWithStats(mask, connectivity=8)
    if count <= 1:
        return mask
    largest_label = 1 + int(np.argmax(stats[1:, cv2.CC_STAT_AREA]))
    return np.where(labels == largest_label, 255, 0).astype(np.uint8)


def extract_contour(mask):
    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not contours:
        return None
    contour = max(contours, key=cv2.contourArea)
    if cv2.contourArea(contour) < mask.shape[0] * mask.shape[1] * 0.02:
        return None
    return contour


def convexity_defect_points(contour):
    """Points on the contour where it dips inward from its convex hull —
    for a shirt these are dominated by the two armpit notches, for pants by
    the crotch notch."""
    hull_indices = cv2.convexHull(contour, returnPoints=False)
    if hull_indices is None or len(hull_indices) < 4:
        return []
    try:
        defects = cv2.convexityDefects(contour, hull_indices)
    except cv2.error:
        return []
    if defects is None:
        return []

    points = []
    for i in range(defects.shape[0]):
        _, _, far_idx, depth = defects[i, 0]
        far_point = tuple(int(v) for v in contour[far_idx][0])
        points.append({"point": far_point, "depth": depth / 256.0})
    return points


def points_in_y_band(contour_points, y_min, y_max):
    band = contour_points[(contour_points[:, 1] >= y_min) & (contour_points[:, 1] <= y_max)]
    return [tuple(int(v) for v in p) for p in band]


def detect_shirt_keypoints(contour, mask):
    x, y, w, h = cv2.boundingRect(contour)
    cx = x + w / 2
    contour_points = contour.reshape(-1, 2)

    topmost = tuple(int(v) for v in contour_points[contour_points[:, 1].argmin()])
    bottommost = tuple(int(v) for v in contour_points[contour_points[:, 1].argmax()])
    leftmost = tuple(int(v) for v in contour_points[contour_points[:, 0].argmin()])
    rightmost = tuple(int(v) for v in contour_points[contour_points[:, 0].argmax()])

    defects = convexity_defect_points(contour)
    upper_limit = y + h * 0.75
    left_candidates = [d for d in defects if d["point"][0] < cx and d["point"][1] < upper_limit]
    right_candidates = [d for d in defects if d["point"][0] >= cx and d["point"][1] < upper_limit]
    left_armpit = max(left_candidates, key=lambda d: d["depth"])["point"] if left_candidates else None
    right_armpit = max(right_candidates, key=lambda d: d["depth"])["point"] if right_candidates else None

    # Shoulder corners: the widest convex-hull vertices above the armpit
    # line. Using hull points (rather than a raw contour y-band) avoids
    # picking a point on the sleeve's slanted edge instead of the actual
    # shoulder-seam corner.
    armpit_y = min(
        (p[1] for p in (left_armpit, right_armpit) if p is not None),
        default=y + h * 0.3,
    )
    hull_points = [tuple(int(v) for v in p[0]) for p in cv2.convexHull(contour)]
    cuff_margin = w * 0.05
    shoulder_zone = [
        p for p in hull_points
        if p[1] < armpit_y and leftmost[0] + cuff_margin < p[0] < rightmost[0] - cuff_margin
    ]
    left_zone = [p for p in shoulder_zone if p[0] < cx]
    right_zone = [p for p in shoulder_zone if p[0] >= cx]
    left_shoulder = max(left_zone, key=lambda p: p[1]) if left_zone else topmost
    right_shoulder = max(right_zone, key=lambda p: p[1]) if right_zone else topmost

    return {
        "collar": topmost,
        "hem": bottommost,
        "left_cuff": leftmost,
        "right_cuff": rightmost,
        "left_shoulder": left_shoulder,
        "right_shoulder": right_shoulder,
        "left_armpit": left_armpit,
        "right_armpit": right_armpit,
        "bounding_box": (x, y, w, h),
    }


def measure_shirt(keypoints, mask, scale):
    data = {}
    left_armpit = keypoints.get("left_armpit")
    right_armpit = keypoints.get("right_armpit")
    left_shoulder = keypoints.get("left_shoulder")
    right_shoulder = keypoints.get("right_shoulder")
    collar = keypoints["collar"]
    hem = keypoints["hem"]

    if left_armpit and right_armpit:
        chest_width_px = abs(right_armpit[0] - left_armpit[0])
        data["chest"] = rounded(pixel_to_cm(chest_width_px * 2, scale))

    if left_shoulder and right_shoulder:
        shoulder_width_px = euclidean_distance(left_shoulder, right_shoulder)
        data["shoulder_width"] = rounded(pixel_to_cm(shoulder_width_px, scale))

    shirt_length_px = abs(hem[1] - collar[1])
    data["shirt_length"] = rounded(pixel_to_cm(shirt_length_px, scale))

    if left_shoulder and left_armpit:
        # Sleeve runs from the shoulder seam out to the cuff; approximate
        # with the shoulder-to-cuff straight-line distance on the side with
        # a detected armpit notch (more reliable than picking whichever
        # side happens to have the further-out cuff pixel).
        cuff = keypoints["left_cuff"] if keypoints["left_cuff"][0] <= left_armpit[0] else keypoints["right_cuff"]
        arm_length_px = euclidean_distance(left_shoulder, cuff)
        data["arm_length"] = rounded(pixel_to_cm(arm_length_px, scale))

    neck_width_px = width_at_y(mask, collar[1] + (keypoints["bounding_box"][3] * 0.03))
    if neck_width_px:
        data["neck"] = rounded(pixel_to_cm(neck_width_px * 2, scale))

    return data


def detect_pants_keypoints(contour, mask):
    x, y, w, h = cv2.boundingRect(contour)
    cx = x + w / 2
    contour_points = contour.reshape(-1, 2)

    top_band = points_in_y_band(contour_points, y, y + h * 0.08)
    left_waist = min(top_band, key=lambda p: p[0]) if top_band else None
    right_waist = max(top_band, key=lambda p: p[0]) if top_band else None

    defects = convexity_defect_points(contour)
    crotch_candidates = [
        d for d in defects
        if abs(d["point"][0] - cx) < w * 0.25 and d["point"][1] > y + h * 0.3
    ]
    crotch = max(crotch_candidates, key=lambda d: d["depth"])["point"] if crotch_candidates else None

    bottom_band = points_in_y_band(contour_points, y + h * 0.92, y + h)
    left_ankle = min(bottom_band, key=lambda p: p[0]) if bottom_band else None
    right_ankle = max(bottom_band, key=lambda p: p[0]) if bottom_band else None

    return {
        "left_waist": left_waist,
        "right_waist": right_waist,
        "crotch": crotch,
        "left_ankle": left_ankle,
        "right_ankle": right_ankle,
        "bounding_box": (x, y, w, h),
    }


def measure_pants(keypoints, mask, scale):
    data = {}
    x, y, w, h = keypoints["bounding_box"]
    left_waist = keypoints.get("left_waist")
    right_waist = keypoints.get("right_waist")
    crotch = keypoints.get("crotch")

    if left_waist and right_waist:
        waist_width_px = euclidean_distance(left_waist, right_waist)
        data["pants_waist"] = rounded(pixel_to_cm(waist_width_px * 2, scale))
        waist_y = (left_waist[1] + right_waist[1]) / 2
    else:
        waist_y = y

    hip_y = y + h * 0.18
    hip_width_px = width_at_y(mask, hip_y)
    if hip_width_px:
        data["pants_hips"] = rounded(pixel_to_cm(hip_width_px * 2, scale))

    if crotch:
        rise_px = abs(crotch[1] - waist_y)
        data["rise"] = rounded(pixel_to_cm(rise_px, scale))

        outseam_px = abs(waist_y - (y + h))
        data["outseam"] = rounded(pixel_to_cm(outseam_px, scale))

        inseam_px = abs(crotch[1] - (y + h))
        data["inseam"] = rounded(pixel_to_cm(inseam_px, scale))

        # Just below the crotch fork both legs are still one connected
        # blob in the mask, so a single width scan there already spans
        # both legs — which is numerically equivalent to one leg's flat
        # width doubled (the circumference convention), so no extra x2.
        thigh_y = crotch[1] + h * 0.05
        thigh_width_px = width_at_y(mask, thigh_y)
        if thigh_width_px:
            data["thigh"] = rounded(pixel_to_cm(thigh_width_px, scale))

    ankle_widths = []
    ankle_y = y + h * 0.97
    max_leg_window = w * 0.4
    for ankle_point in (keypoints.get("left_ankle"), keypoints.get("right_ankle")):
        if not ankle_point:
            continue
        leg_width_px = constrained_width_at_y(mask, ankle_y, ankle_point[0], max_leg_window)
        if leg_width_px:
            ankle_widths.append(leg_width_px)
    if ankle_widths:
        data["ankle"] = rounded(pixel_to_cm((sum(ankle_widths) / len(ankle_widths)) * 2, scale))

    return data


def impossible_garment_measurements(data):
    invalid = []
    for field, value in data.items():
        limits = GARMENT_MEASUREMENT_LIMITS_CM.get(field)
        if not limits:
            continue
        minimum, maximum = limits
        if value and (value < minimum or value > maximum):
            invalid.append({"field": field, "value": value, "min": minimum, "max": maximum})
    return invalid


def process_garment_measurement(
    image_bytes,
    garment_type,
    ref_object,
    ref_width_cm=None,
    ref_height_cm=None,
    reference_box=None,
):
    if garment_type not in ("shirt", "pants"):
        return {
            "success": False,
            "error": "Jenis pakaian harus 'shirt' atau 'pants'.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
        }

    image = decode_image(image_bytes)
    if image is None:
        return {
            "success": False,
            "error": "Gagal membaca foto. Gunakan gambar JPG, PNG, atau WEBP yang tidak rusak.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
        }
    image = resize_for_measurement(image)

    scale_result = calculate_scale(image, ref_object, ref_width_cm, ref_height_cm, reference_box)
    if scale_result is None or scale_result.get("quality", 0.0) < 0.6:
        return {
            "success": False,
            "error": (
                "Benda patokan (KTP/A4) tidak terdeteksi dengan jelas. Pastikan "
                "diletakkan rata di sebelah pakaian dan terlihat penuh."
            ),
            "failed_reason": "reference_not_detected",
            "correction": "Ratakan KTP/A4 di permukaan yang sama dengan pakaian, lalu foto ulang dari atas.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
        }

    mask = segment_garment(image, scale_result["contour"])
    contour = extract_contour(mask)
    if contour is None:
        return {
            "success": False,
            "error": "Siluet pakaian tidak terbaca dari foto.",
            "failed_reason": "garment_not_detected",
            "correction": "Gunakan alas polos yang kontras dengan warna pakaian, pastikan pencahayaan cukup.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
        }

    scale = scale_result["scale"]
    if garment_type == "shirt":
        keypoints = detect_shirt_keypoints(contour, mask)
        data = measure_shirt(keypoints, mask, scale)
        expected_fields = ("shoulder_width", "chest", "shirt_length", "arm_length")
    else:
        keypoints = detect_pants_keypoints(contour, mask)
        data = measure_pants(keypoints, mask, scale)
        expected_fields = ("pants_waist", "pants_hips", "inseam", "outseam")

    if not data:
        return {
            "success": False,
            "error": "Tidak berhasil menemukan titik ukur pada pakaian.",
            "failed_reason": "keypoints_not_detected",
            "correction": "Ratakan pakaian dengan rapi (lengan/kaki tidak terlipat), pastikan seluruh bagian terlihat.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
        }

    invalid_measurements = impossible_garment_measurements(data)
    if invalid_measurements:
        fields = ", ".join(
            f"{GARMENT_LABELS.get(item['field'], item['field'])} {item['value']}cm"
            for item in invalid_measurements[:5]
        )
        return {
            "success": False,
            "error": f"Hasil ukur tidak masuk akal ({fields}).",
            "failed_reason": "unrealistic_measurements",
            "correction": "Ratakan pakaian dengan rapi dan ulangi foto dari atas dengan pencahayaan cukup.",
            "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
            "invalid_measurements": invalid_measurements,
        }

    completeness = len([f for f in expected_fields if f in data]) / len(expected_fields)
    confidence = round(min(0.95, max(0.4, scale_result.get("quality", 0.7) * completeness)), 2)

    return {
        "success": True,
        "garment_type": garment_type,
        "data": data,
        "confidence": confidence,
        "quality_score": confidence,
        "ref_detected": True,
        "measurement_method": "garment_flat_lay",
        "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
    }
