import json
import sys
import unittest
from pathlib import Path


CV_DIR = Path(__file__).resolve().parents[1]
PROJECT_DIR = CV_DIR.parent
if str(CV_DIR) not in sys.path:
    sys.path.insert(0, str(CV_DIR))

import bodym_contract


class BodyMContractTest(unittest.TestCase):
    def setUp(self):
        schema_path = PROJECT_DIR / "docs" / "bodym" / "measurement-contract-v1.json"
        self.schema = json.loads(schema_path.read_text(encoding="utf-8"))

    def test_contract_contains_exactly_the_fourteen_bodym_fields(self):
        fields = list(bodym_contract.MEASUREMENT_FIELDS)

        self.assertEqual(14, len(fields))
        self.assertEqual(fields, self.schema["properties"]["data"]["required"])
        self.assertEqual(fields, list(self.schema["properties"]["data"]["properties"]))
        self.assertEqual(
            fields,
            self.schema["properties"]["per_field_confidence"]["required"],
        )

    def test_contract_metadata_and_measurement_types_are_frozen(self):
        self.assertEqual("bodym.v1", bodym_contract.CONTRACT_VERSION)
        self.assertEqual("bodym_ml", bodym_contract.MEASUREMENT_METHOD)
        self.assertEqual("cm", bodym_contract.UNIT)
        self.assertEqual(
            set(bodym_contract.MEASUREMENT_FIELDS),
            set(bodym_contract.MEASUREMENT_TYPES),
        )
        self.assertTrue(
            set(bodym_contract.MEASUREMENT_TYPES.values())
            <= {"circumference", "length", "breadth", "height"}
        )

    def test_schema_rejects_unlisted_measurement_fields(self):
        self.assertFalse(self.schema["properties"]["data"]["additionalProperties"])
        self.assertNotIn("neck", bodym_contract.MEASUREMENT_FIELDS)
        self.assertNotIn("knee", bodym_contract.MEASUREMENT_FIELDS)
        self.assertNotIn("inseam", bodym_contract.MEASUREMENT_FIELDS)
        self.assertNotIn("outseam", bodym_contract.MEASUREMENT_FIELDS)
        self.assertNotIn("rise", bodym_contract.MEASUREMENT_FIELDS)


if __name__ == "__main__":
    unittest.main()
