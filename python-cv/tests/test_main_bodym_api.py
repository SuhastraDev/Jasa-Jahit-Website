from __future__ import annotations

import asyncio
from pathlib import Path
import sys
import unittest
from unittest.mock import MagicMock, patch
from types import ModuleType


PYTHON_CV_ROOT = Path(__file__).resolve().parents[1]
if str(PYTHON_CV_ROOT) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV_ROOT))

_measurement_stub = ModuleType("measurement")
_measurement_stub.process_measurement = MagicMock()
sys.modules["measurement"] = _measurement_stub
import main
sys.modules.pop("measurement", None)
from bodym_preprocessing import feature_names


class BodyMApiTest(unittest.TestCase):
    def test_measure_accepts_handheld_ktp_and_forwards_reference_mode(self) -> None:
        class Upload:
            content_type = "image/jpeg"

            async def read(self):
                return b"photo"

        with patch.object(
            main,
            "process_measurement",
            return_value={"success": True, "data": {}},
        ) as process:
            response = asyncio.run(main.measure(
                front_photo=Upload(),
                side_photo=Upload(),
                back_photo=Upload(),
                ref_object="ktp",
                ref_width_cm=8.56,
                ref_height_cm=5.398,
                reference_mode="handheld",
                front_reference_box=None,
                side_reference_box=None,
                back_reference_box=None,
            ))

        self.assertTrue(response["success"])
        self.assertEqual("handheld", process.call_args.kwargs["reference_mode"])
        self.assertEqual("ktp", process.call_args.args[3])

    def test_health_exposes_loaded_model_version(self) -> None:
        service = MagicMock()
        service.status.return_value = {
            "loaded": True,
            "available": True,
            "model_version": "bodym-v1",
        }

        with patch.object(main, "get_bodym_service", return_value=service):
            response = asyncio.run(main.health_check())

        self.assertEqual(response["status"], "ok")
        self.assertEqual(response["bodym"]["model_version"], "bodym-v1")
        service.status.assert_called_once_with(load=True)

    def test_feature_endpoint_returns_versioned_prediction(self) -> None:
        service = MagicMock()
        service.predict_features.return_value = {
            "model_version": "bodym-v1",
            "measurement_method": "bodym_ml",
            "predictions_cm": {"height": 170.0},
        }
        payload = main.BodyMFeatureRequest(features=[0.0] * len(feature_names()), coverage=0.90)

        with patch.object(main, "get_bodym_service", return_value=service):
            response = asyncio.run(main.predict_bodym_features(payload))

        self.assertTrue(response["success"])
        self.assertEqual(response["model_version"], "bodym-v1")
        service.predict_features.assert_called_once()


if __name__ == "__main__":
    unittest.main()
