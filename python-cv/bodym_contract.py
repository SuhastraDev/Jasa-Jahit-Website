"""Frozen BodyM v1 output contract shared by training and inference."""

CONTRACT_VERSION = "bodym.v1"
MEASUREMENT_METHOD = "bodym_ml"
UNIT = "cm"

MEASUREMENT_FIELDS = (
    "ankle_girth",
    "arm_length",
    "bicep_girth",
    "calf_girth",
    "chest_girth",
    "forearm_girth",
    "height",
    "hip_girth",
    "leg_length",
    "shoulder_breadth",
    "shoulder_to_crotch",
    "thigh_girth",
    "waist_girth",
    "wrist_girth",
)

MEASUREMENT_TYPES = {
    "ankle_girth": "circumference",
    "arm_length": "length",
    "bicep_girth": "circumference",
    "calf_girth": "circumference",
    "chest_girth": "circumference",
    "forearm_girth": "circumference",
    "height": "height",
    "hip_girth": "circumference",
    "leg_length": "length",
    "shoulder_breadth": "breadth",
    "shoulder_to_crotch": "length",
    "thigh_girth": "circumference",
    "waist_girth": "circumference",
    "wrist_girth": "circumference",
}
