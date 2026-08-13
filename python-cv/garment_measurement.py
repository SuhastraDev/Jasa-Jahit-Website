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
import base64

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
    "chest": (60, 220),
    "shoulder_width": (30, 70),
    "shirt_length": (35, 100),
    "arm_length": (30, 90),
    "wrist": (12, 35),
    "pants_waist": (50, 220),
    "pants_hips": (60, 230),
    "thigh": (30, 110),
    "inseam": (35, 120),
    "outseam": (60, 150),
    "rise": (15, 70),
    "ankle": (14, 60),
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


_REMBG_SESSION = None


def _get_rembg_session():
    """Lazily create the AI segmentation session once per process (loading
    the ~176MB U2Net model is the expensive part, not running it)."""
    global _REMBG_SESSION
    if _REMBG_SESSION is None:
        from rembg import new_session
        _REMBG_SESSION = new_session("u2net")
    return _REMBG_SESSION


def segment_garment(image, ref_contour=None):
    """Separate the garment from its background using an AI salient-object
    segmentation model (rembg/U2Net) instead of classical color-clustering
    (GrabCut). GrabCut was tried first and abandoned: it had real run-to-run
    variance on the exact same photo (its GMM init isn't seeded), and would
    intermittently misclassify tiled-floor grout lines as part of the
    garment. An AI model trained specifically to find "the main object in
    a photo" doesn't share either failure mode."""
    success, buffer = cv2.imencode(".png", image)
    if success:
        from rembg import remove
        result_bytes = remove(buffer.tobytes(), session=_get_rembg_session())
        result_image = cv2.imdecode(np.frombuffer(result_bytes, np.uint8), cv2.IMREAD_UNCHANGED)
    else:
        result_image = None

    if result_image is not None and result_image.ndim == 3 and result_image.shape[2] == 4:
        alpha = result_image[:, :, 3]
        _, garment_mask = cv2.threshold(alpha, 127, 255, cv2.THRESH_BINARY)
    else:
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        _, garment_mask = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)

    if ref_contour is not None:
        # The AI model already tends not to include a separate small object
        # like the marker as part of the main garment, but exclude its
        # footprint explicitly too as a safety net for edge cases.
        cv2.drawContours(garment_mask, [ref_contour], -1, 0, thickness=cv2.FILLED)

    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
    garment_mask = cv2.morphologyEx(garment_mask, cv2.MORPH_CLOSE, kernel, iterations=1)
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

    # Neck width is deliberately not estimated: a horizontal mask scan a few
    # percent below the collar point already lands past the neckline taper
    # and into the shoulder span, which measured as a "neck" width on real
    # photos (e.g. a jacket collar) rather than the actual neck opening.
    # There's no reliable way to isolate just the collar hole from the
    # garment silhouette without a dedicated collar detector, so we skip it
    # rather than report a wrong number.

    return data


def detect_pants_keypoints(contour, mask):
    x, y, w, h = cv2.boundingRect(contour)
    contour_points = contour.reshape(-1, 2)

    top_band = points_in_y_band(contour_points, y, y + h * 0.08)
    left_waist = min(top_band, key=lambda p: p[0]) if top_band else None
    right_waist = max(top_band, key=lambda p: p[0]) if top_band else None

    # The true crotch notch is, by a wide margin, the single deepest
    # concavity in the whole silhouette (pocket flaps and fabric folds are
    # noticeably shallower) - anchoring on depth alone is more reliable
    # than assuming it sits near the horizontal center, since legs laid
    # flat are rarely perfectly symmetric around the waistband's midpoint.
    defects = convexity_defect_points(contour)
    crotch_candidates = [d for d in defects if d["point"][1] > y + h * 0.3]
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


def row_extent(mask, y):
    """Leftmost/rightmost lit points of the mask at row y (same band logic
    as measurement.py's width_at_y, but returning the actual coordinates
    instead of just a width) — used to place visible points for
    measurements (hips, thigh) that are otherwise just a scanned width."""
    h = mask.shape[0]
    y = int(max(0, min(h - 1, y)))
    band_top = max(0, y - 3)
    band_bottom = min(h, y + 4)
    rows = mask[band_top:band_bottom, :]
    cols = np.where(rows.max(axis=0) > 0)[0]
    if len(cols) < 2:
        return None
    return (int(cols[0]), y), (int(cols[-1]), y)


def build_overlay_geometry(garment_type, keypoints, data, mask):
    """Point/line coordinates (pixel space of the measured image) behind
    every numeric field that was successfully computed, so the frontend can
    draw an interactive overlay (hover a line -> see its cm value) instead
    of a static baked-in image."""
    points = []
    lines = []

    def add_point(point_id, label, point):
        if point is None:
            return
        points.append({"id": point_id, "label": label, "x": int(point[0]), "y": int(point[1])})

    def add_line(field, label, point_a, point_b, note=None):
        if point_a is None or point_b is None or field not in data:
            return
        entry = {
            "field": field,
            "label": label,
            "value_cm": data[field],
            "points": [[int(point_a[0]), int(point_a[1])], [int(point_b[0]), int(point_b[1])]],
        }
        if note:
            entry["note"] = note
        lines.append(entry)

    if garment_type == "shirt":
        for key, label in SHIRT_KEYPOINT_LABELS:
            add_point(key, label, keypoints.get(key))

        add_line("shoulder_width", "Lebar Bahu", keypoints.get("left_shoulder"), keypoints.get("right_shoulder"))
        add_line(
            "chest", "Lingkar Dada", keypoints.get("left_armpit"), keypoints.get("right_armpit"),
            note="Lebar rata ditampilkan; keliling ≈ 2× nilai ini.",
        )
        add_line("shirt_length", "Panjang Badan", keypoints.get("collar"), keypoints.get("hem"))

        left_shoulder = keypoints.get("left_shoulder")
        left_armpit = keypoints.get("left_armpit")
        if left_shoulder and left_armpit and keypoints.get("left_cuff") and keypoints.get("right_cuff"):
            cuff = keypoints["left_cuff"] if keypoints["left_cuff"][0] <= left_armpit[0] else keypoints["right_cuff"]
            add_line("arm_length", "Panjang Lengan", left_shoulder, cuff)
    else:
        for key, label in PANTS_KEYPOINT_LABELS:
            add_point(key, label, keypoints.get(key))

        left_waist = keypoints.get("left_waist")
        right_waist = keypoints.get("right_waist")
        crotch = keypoints.get("crotch")
        x, y, w, h = keypoints["bounding_box"]

        add_line(
            "pants_waist", "Lingkar Pinggang", left_waist, right_waist,
            note="Lebar rata ditampilkan; keliling ≈ 2× nilai ini.",
        )

        hip_span = row_extent(mask, y + h * 0.18)
        if hip_span:
            add_point("left_hip", "Pinggul Kiri", hip_span[0])
            add_point("right_hip", "Pinggul Kanan", hip_span[1])
            add_line(
                "pants_hips", "Lingkar Pinggul", hip_span[0], hip_span[1],
                note="Lebar rata ditampilkan; keliling ≈ 2× nilai ini.",
            )

        if left_waist and right_waist and crotch:
            waist_mid = ((left_waist[0] + right_waist[0]) / 2, (left_waist[1] + right_waist[1]) / 2)
            add_point("waist_mid", "Tengah Pinggang", waist_mid)
            add_line("rise", "Panjang Pesak", waist_mid, crotch)

            thigh_span = row_extent(mask, crotch[1] + h * 0.05)
            if thigh_span:
                add_point("left_thigh", "Paha Kiri", thigh_span[0])
                add_point("right_thigh", "Paha Kanan", thigh_span[1])
                add_line(
                    "thigh", "Lingkar Paha", thigh_span[0], thigh_span[1],
                    note="Lebar rata ditampilkan; keliling ≈ 2× nilai ini.",
                )

            left_ankle = keypoints.get("left_ankle")
            if left_ankle:
                add_line("outseam", "Panjang Celana (Luar)", waist_mid, left_ankle)
                add_line("inseam", "Inseam", crotch, left_ankle)

        left_ankle = keypoints.get("left_ankle")
        right_ankle = keypoints.get("right_ankle")
        add_line(
            "ankle", "Lingkar Kaki Bawah", left_ankle, right_ankle,
            note="Diukur per kaki lalu dirata-rata; garis ini hanya penanda posisi.",
        )

    image_h, image_w = mask.shape[:2]
    return {"image_width": image_w, "image_height": image_h, "points": points, "lines": lines}


SHIRT_KEYPOINT_LABELS = [
    ("collar", "Kerah"),
    ("left_shoulder", "Bahu Kiri"),
    ("right_shoulder", "Bahu Kanan"),
    ("left_armpit", "Ketiak Kiri"),
    ("right_armpit", "Ketiak Kanan"),
    ("left_cuff", "Manset Kiri"),
    ("right_cuff", "Manset Kanan"),
    ("hem", "Bawah Baju"),
]

PANTS_KEYPOINT_LABELS = [
    ("left_waist", "Pinggang Kiri"),
    ("right_waist", "Pinggang Kanan"),
    ("crotch", "Selangkangan"),
    ("left_ankle", "Kaki Kiri"),
    ("right_ankle", "Kaki Kanan"),
]

SHIRT_MEASUREMENT_LINES = [("left_armpit", "right_armpit"), ("left_shoulder", "right_shoulder")]
PANTS_MEASUREMENT_LINES = [("left_waist", "right_waist")]


def draw_measurement_line(image, keypoints, key_a, key_b, color=(255, 90, 0)):
    a = keypoints.get(key_a)
    b = keypoints.get(key_b)
    if a is None or b is None:
        return
    cv2.line(image, (int(a[0]), int(a[1])), (int(b[0]), int(b[1])), color, 2, cv2.LINE_AA)


def render_debug_overlay(
    image,
    contour=None,
    marker_contour=None,
    keypoints=None,
    garment_type="shirt",
    status_text=None,
    status_ok=True,
):
    """Draw whatever the pipeline managed to detect (garment outline,
    reference marker box, named keypoints) on top of the original photo —
    called on BOTH success and failure so the user can see what the system
    actually read, not just get a text error with no visual explanation."""
    overlay = image.copy()
    keypoints = keypoints or {}

    if contour is not None:
        cv2.drawContours(overlay, [contour], -1, (0, 200, 0), 3)

    if marker_contour is not None:
        cv2.drawContours(overlay, [marker_contour], -1, (0, 165, 255), 3)

    labels = SHIRT_KEYPOINT_LABELS if garment_type == "shirt" else PANTS_KEYPOINT_LABELS
    lines = SHIRT_MEASUREMENT_LINES if garment_type == "shirt" else PANTS_MEASUREMENT_LINES

    for key_a, key_b in lines:
        draw_measurement_line(overlay, keypoints, key_a, key_b)

    for key, label in labels:
        point = keypoints.get(key)
        if point is None:
            continue
        pt = (int(point[0]), int(point[1]))
        cv2.circle(overlay, pt, 8, (255, 0, 0), -1)
        cv2.circle(overlay, pt, 8, (255, 255, 255), 2)
        cv2.putText(overlay, label, (pt[0] + 12, pt[1] - 8), cv2.FONT_HERSHEY_SIMPLEX, 0.55, (0, 0, 0), 3, cv2.LINE_AA)
        cv2.putText(overlay, label, (pt[0] + 12, pt[1] - 8), cv2.FONT_HERSHEY_SIMPLEX, 0.55, (255, 255, 255), 1, cv2.LINE_AA)

    if status_text:
        banner_color = (0, 140, 0) if status_ok else (0, 0, 200)
        cv2.rectangle(overlay, (0, 0), (overlay.shape[1], 46), banner_color, -1)
        cv2.putText(overlay, status_text, (14, 31), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2, cv2.LINE_AA)

    success, buffer = cv2.imencode(".jpg", overlay, [cv2.IMWRITE_JPEG_QUALITY, 85])
    if not success:
        return None
    return base64.b64encode(buffer).decode("ascii")


def encode_image_base64(image):
    """Undecorated copy of the measured photo, at the exact pixel size the
    overlay geometry's coordinates were computed in - the frontend draws an
    SVG overlay on top of this using those coordinates directly (no
    percentage math, no scaling drift)."""
    success, buffer = cv2.imencode(".jpg", image, [cv2.IMWRITE_JPEG_QUALITY, 90])
    if not success:
        return None
    return base64.b64encode(buffer).decode("ascii")


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

    # A flat-lay photo has to frame the whole garment, so the marker often
    # ends up much smaller relative to the frame than in a body photo (where
    # it's held close to the subject). The body-photo pipeline's 0.5%
    # minimum-area filter throws away markers this small, so use a lower
    # floor here. There's also no "body" to avoid overlapping, so the
    # body-side position gap doesn't apply to garment photos either.
    scale_result = calculate_scale(
        image,
        ref_object,
        ref_width_cm,
        ref_height_cm,
        reference_box,
        min_area_ratio=0.0015,
        require_body_side_gap=False,
        # A small marker's outline often comes out of Canny+morph-close as
        # several disconnected fragments rather than one clean closed loop,
        # so RETR_EXTERNAL (only top-level closed contours) can miss it
        # entirely. RETR_LIST considers every contour found and lets the
        # existing area/ratio/rectangularity scoring pick the real one.
        retrieval_mode=cv2.RETR_LIST,
    )
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
            "debug_image_base64": render_debug_overlay(
                image,
                marker_contour=scale_result.get("contour") if scale_result else None,
                garment_type=garment_type,
                status_text="Benda patokan (KTP/A4) tidak terdeteksi jelas",
                status_ok=False,
            ),
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
            "debug_image_base64": render_debug_overlay(
                image,
                marker_contour=scale_result.get("contour"),
                garment_type=garment_type,
                status_text="Siluet pakaian tidak ditemukan",
                status_ok=False,
            ),
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
            "debug_image_base64": render_debug_overlay(
                image,
                contour=contour,
                marker_contour=scale_result.get("contour"),
                keypoints=keypoints,
                garment_type=garment_type,
                status_text="Titik ukur tidak ditemukan",
                status_ok=False,
            ),
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
            "debug_image_base64": render_debug_overlay(
                image,
                contour=contour,
                marker_contour=scale_result.get("contour"),
                keypoints=keypoints,
                garment_type=garment_type,
                status_text="Hasil ukur di luar rentang wajar",
                status_ok=False,
            ),
        }

    completeness = len([f for f in expected_fields if f in data]) / len(expected_fields)
    confidence = round(min(0.95, max(0.4, scale_result.get("quality", 0.7) * completeness)), 2)

    debug_image_base64 = render_debug_overlay(
        image,
        contour=contour,
        marker_contour=scale_result.get("contour"),
        keypoints=keypoints,
        garment_type=garment_type,
        status_text="Berhasil dianalisis",
        status_ok=True,
    )

    return {
        "success": True,
        "garment_type": garment_type,
        "data": data,
        "confidence": confidence,
        "quality_score": confidence,
        "ref_detected": True,
        "measurement_method": "garment_flat_lay",
        "debug_image_base64": debug_image_base64,
        "clean_image_base64": encode_image_base64(image),
        "overlay": build_overlay_geometry(garment_type, keypoints, data, mask),
        "response_contract_version": GARMENT_RESPONSE_CONTRACT_VERSION,
    }
