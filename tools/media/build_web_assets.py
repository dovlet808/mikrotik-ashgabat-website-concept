"""Build optimised web assets.

  media/generated/originals/*.png  ->  public/images/editorial/<name>-{640,1280,1920}.webp
  editorial image + type           ->  public/images/og-default.jpg (1200x630)

The 4K editorial originals are large and are not kept in the repository, so the
editorial step is skipped when that folder is absent. The social image is built
from the web-sized editorial set and always works.

Run from the project root:
  python3 tools/media/build_web_assets.py
  python3 tools/media/build_web_assets.py --og-photo path/to/photo.jpg --og-contact "+993 00 00-00-00"
"""

import argparse
import os
import sys

from PIL import Image, ImageDraw, ImageFont, ImageOps

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
GENERATED = os.path.join(ROOT, "media", "generated", "originals")
EDITORIAL_OUT = os.path.join(ROOT, "public", "images", "editorial")
OG_OUT = os.path.join(ROOT, "public", "images", "og-default.jpg")
OG_DEFAULT_PHOTO = os.path.join(EDITORIAL_OUT, "services-cabling-1920.webp")

WIDTHS = (640, 1280, 1920)
QUALITY = 78

FONT_BOLD = "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf"
FONT_REGULAR = "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf"
FONT_MONO = "/usr/share/fonts/truetype/noto/NotoSansMono-Regular.ttf"

GRAPHITE = (23, 25, 27)
PAPER = (243, 240, 235)
AMBER = (242, 138, 46)
MUTED = (168, 172, 176)


def build_editorial():
    if not os.path.isdir(GENERATED):
        return 0

    os.makedirs(EDITORIAL_OUT, exist_ok=True)
    written = 0
    for name in sorted(os.listdir(GENERATED)):
        if not name.lower().endswith(".png"):
            continue
        stem = os.path.splitext(name)[0]
        with Image.open(os.path.join(GENERATED, name)) as im:
            im = im.convert("RGB")
            for width in WIDTHS:
                height = round(im.height * width / im.width)
                im.resize((width, height), Image.LANCZOS).save(
                    os.path.join(EDITORIAL_OUT, "%s-%d.webp" % (stem, width)), "WEBP", quality=QUALITY, method=6
                )
                written += 1
    return written


def build_og(photo_path, contact_line):
    """Type on the left, photograph on the right: text never sits on the image."""
    canvas = Image.new("RGB", (1200, 630), GRAPHITE)
    with Image.open(photo_path) as photo:
        photo = ImageOps.fit(photo.convert("RGB"), (560, 630), Image.LANCZOS, centering=(0.3, 0.5))
        canvas.paste(photo, (640, 0))

    draw = ImageDraw.Draw(canvas)
    mono = ImageFont.truetype(FONT_MONO, 22)
    title = ImageFont.truetype(FONT_BOLD, 64)
    body = ImageFont.truetype(FONT_REGULAR, 30)

    draw.text((64, 72), "СЕТЕВОЕ ОБОРУДОВАНИЕ · АШХАБАД", font=mono, fill=AMBER)
    draw.text((64, 150), "MikroTik", font=title, fill=PAPER)
    draw.text((64, 222), "Ashgabat", font=title, fill=PAPER)
    draw.text((64, 336), "Подбор, продажа, настройка", font=body, fill=MUTED)
    draw.text((64, 378), "и установка сетей", font=body, fill=MUTED)
    if contact_line:
        draw.line((64, 500, 576, 500), fill=(60, 64, 68), width=1)
        draw.text((64, 524), contact_line, font=mono, fill=PAPER)

    os.makedirs(os.path.dirname(OG_OUT), exist_ok=True)
    canvas.save(OG_OUT, "JPEG", quality=86, optimize=True, progressive=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--og-photo", default=OG_DEFAULT_PHOTO, help="image for the right half of the social card")
    parser.add_argument("--og-contact", default="", help="optional contact line under the tagline")
    args = parser.parse_args()

    count = build_editorial()
    build_og(args.og_photo, args.og_contact)
    print("wrote %d editorial webp files and %s" % (count, os.path.relpath(OG_OUT, ROOT)))


if __name__ == "__main__":
    sys.exit(main())
