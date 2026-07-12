from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
SHOT_DIR = ROOT / "docs" / "screenshots"
TARGET = (1440, 900)


def standardize(path: Path) -> tuple[int, int]:
    image = Image.open(path).convert("RGB")
    target_w, target_h = TARGET
    scale = min(target_w / image.width, target_h / image.height)
    new_size = (round(image.width * scale), round(image.height * scale))
    resized = image.resize(new_size, Image.Resampling.LANCZOS)

    canvas = Image.new("RGB", TARGET, "white")
    left = (target_w - new_size[0]) // 2
    top = (target_h - new_size[1]) // 2
    canvas.paste(resized, (left, top))
    canvas.save(path, "PNG", optimize=True)
    return canvas.size


if __name__ == "__main__":
    for file in sorted(SHOT_DIR.glob("*.png")):
        size = standardize(file)
        print(f"{file.name} {size[0]}x{size[1]}")
