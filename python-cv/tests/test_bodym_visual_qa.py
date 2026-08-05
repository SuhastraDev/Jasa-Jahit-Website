import sys
import tempfile
import unittest
from pathlib import Path

import numpy as np
from PIL import Image


PYTHON_CV = Path(__file__).resolve().parents[1]
if str(PYTHON_CV) not in sys.path:
    sys.path.insert(0, str(PYTHON_CV))

from bodym_preprocessing import extract_pair_features, preprocess_silhouette  # noqa: E402
from bodym_visual_qa import render_visual_qa  # noqa: E402


class BodyMVisualQATest(unittest.TestCase):
    def test_render_contains_nonblank_front_side_and_anatomy_overlays(self) -> None:
        front_mask = np.zeros((120, 100), dtype=np.uint8)
        side_mask = np.zeros((120, 100), dtype=np.uint8)
        front_mask[10:110, 30:70] = 255
        side_mask[10:110, 40:60] = 255
        front = preprocess_silhouette(front_mask, view="front", cm_per_pixel=1.6)
        side = preprocess_silhouette(side_mask, view="side", cm_per_pixel=1.6)
        features = extract_pair_features(front, side)

        with tempfile.TemporaryDirectory() as directory:
            output = Path(directory) / "qa.png"
            render_visual_qa(front, side, features, output, title="Synthetic QA")
            with Image.open(output) as image:
                colors = image.convert("RGB").getcolors(maxcolors=1_000_000)
                self.assertEqual(image.size, (920, 690))
                self.assertIsNotNone(colors)
                self.assertGreater(len(colors or []), 8)


if __name__ == "__main__":
    unittest.main()
