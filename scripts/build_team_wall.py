"""Builds the small WebP tiles used by the home page team wall from the verified 2026 squad photos.

Every team gets a 360px tile; --hero CODE also writes a 1600px version for the tile the wall zooms into.
Run from the project root: python scripts/build_team_wall.py --hero ARG
"""
import argparse
import json
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
PHOTOS = ROOT / 'public/assets/media/team-photos'
OUT = PHOTOS / 'wall'


def save(src: Path, dest: Path, width: int, quality: int) -> int:
    with Image.open(src) as image:
        image = image.convert('RGB')
        height = round(image.height * width / image.width)
        image.resize((width, height), Image.LANCZOS).save(dest, 'WEBP', quality=quality, method=6)
    return dest.stat().st_size


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--hero', action='append', default=[], help='team code that also gets a 1600px tile')
    args = parser.parse_args()
    manifest = json.loads((PHOTOS / 'manifest.json').read_text(encoding='utf-8'))
    OUT.mkdir(exist_ok=True)
    total = 0
    for code, entry in manifest['teams'].items():
        if entry.get('status') != 'verified_2026_team_photo':
            continue
        src = ROOT / 'public' / entry['path'].lstrip('/')
        total += save(src, OUT / f'{code}.webp', 360, 62)
        if code in args.hero:
            total += save(src, OUT / f'{code}-hero.webp', 1600, 72)
    print(f'wrote {len(list(OUT.glob("*.webp")))} tiles, {total / 1024:.0f} KB')


if __name__ == '__main__':
    main()
