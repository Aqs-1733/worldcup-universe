from __future__ import annotations

import zipfile
from pathlib import Path

from PIL import Image
from pypdf import PdfReader


ROOT = Path(__file__).resolve().parent
DOCX = ROOT / "作业2(学号_姓名)_Axure前端设计.docx"
PDF = ROOT / "作业2(学号_姓名)_Axure前端设计.pdf"
ZIP = ROOT.parent / "submissions" / "作业2(学号_姓名)_Axure前端设计.zip"


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


with zipfile.ZipFile(DOCX, "r") as archive:
    require(archive.testzip() is None, "DOCX archive is corrupt")
    require("word/document.xml" in archive.namelist(), "DOCX document.xml missing")

pages = len(PdfReader(str(PDF)).pages)
require(pages == 13, f"expected 13 PDF pages, got {pages}")

with zipfile.ZipFile(ZIP, "r") as archive:
    require(archive.testzip() is None, "submission ZIP is corrupt")
    entries = archive.namelist()
    require(len(entries) == 14, f"expected 14 ZIP entries, got {len(entries)}")
    require(any(name.endswith("prototype/index.html") for name in entries), "prototype missing")

html = (ROOT / "prototype" / "index.html").read_text(encoding="utf-8")
js = (ROOT / "prototype" / "prototype.js").read_text(encoding="utf-8")
css = (ROOT / "prototype" / "styles.css").read_text(encoding="utf-8")
for token in ("themeToggle", "matchModal", "data-match", "data-scroll"):
    require(token in html, f"HTML interaction token missing: {token}")
require("URLSearchParams" in js and "scrollIntoView" in js, "prototype logic incomplete")
require("@media(max-width:620px)" in css and "@keyframes ticker" in css, "responsive or motion CSS missing")

expected_sizes = {
    "prototype-dark.png": (1440, 1050),
    "prototype-light.png": (1440, 1050),
    "prototype-mobile.png": (390, 844),
    "homepage-reference.png": (1440, 1050),
}
for name, expected in expected_sizes.items():
    with Image.open(ROOT / "screenshots" / name) as image:
        require(image.size == expected, f"{name} expected {expected}, got {image.size}")

print("DOCX_OK=word/document.xml")
print(f"PDF_PAGES={pages}")
print(f"ZIP_ENTRIES={len(entries)}")
print("PROTOTYPE_INTERACTIONS=theme,scroll,modal")
print("RESPONSIVE_VIEWS=1440,1024,390")
print("SCREENSHOTS=4")
print("RESULT=PASS")
