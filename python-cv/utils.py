"""
utils.py — Helper functions for CV measurement
"""
import math
import numpy as np


def euclidean_distance(point1, point2):
    """Calculate Euclidean distance between two points (x, y)."""
    return math.sqrt((point1[0] - point2[0]) ** 2 + (point1[1] - point2[1]) ** 2)


def pixel_to_cm(pixel_distance, scale):
    """Convert pixel distance to centimeters using the calculated scale."""
    if scale <= 0:
        return 0.0
    return round(pixel_distance / scale, 2)


def get_reference_dimensions(ref_object, ref_width_cm=None, ref_height_cm=None):
    """
    Get real-world dimensions of reference object in cm.
    Returns (width_cm, height_cm)
    """
    if ref_object in ("a4", "aruco_a4", "checkerboard_a4"):
        return (21.0, 29.7)
    elif ref_object == "atm":
        return (8.56, 5.4)
    elif ref_object == "custom":
        if ref_width_cm and ref_height_cm:
            return (float(ref_width_cm), float(ref_height_cm))
        raise ValueError("Custom reference requires width and height in cm")
    else:
        raise ValueError(f"Unknown reference object: {ref_object}")


def midpoint(p1, p2):
    """Calculate midpoint between two points."""
    return ((p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2)
