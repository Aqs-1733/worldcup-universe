from __future__ import annotations

import shutil
import zipfile
from pathlib import Path


ROOT = Path(__file__).resolve().parent
SUBMISSIONS = ROOT.parent / "submissions"
PACKAGE_NAME = "作业2(学号_姓名)_Axure前端设计"
PDF = ROOT / f"{PACKAGE_NAME}.pdf"
ZIP = SUBMISSIONS / f"{PACKAGE_NAME}.zip"


def main() -> None:
    SUBMISSIONS.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(ROOT / "qa-render-3" / "report.pdf", PDF)

    selected_files = [
        f"{PACKAGE_NAME}.docx",
        f"{PACKAGE_NAME}.pdf",
        "个人作业2_Axure页面结构与交互说明.md",
        "个人作业2_前台后台模板选型.md",
        "axure-widget-spec.csv",
        "axure-interaction-spec.csv",
        "提交前必读.txt",
    ]

    with zipfile.ZipFile(ZIP, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in selected_files:
            path = ROOT / name
            archive.write(path, f"{PACKAGE_NAME}/{name}")
        for folder in ("prototype", "screenshots"):
            for path in sorted((ROOT / folder).rglob("*")):
                if path.is_file():
                    archive.write(path, f"{PACKAGE_NAME}/{path.relative_to(ROOT).as_posix()}")

    with zipfile.ZipFile(ZIP, "r") as archive:
        bad = archive.testzip()
        print(f"ZIP={ZIP}")
        print(f"ZIP_SIZE={ZIP.stat().st_size}")
        print(f"ENTRY_COUNT={len(archive.infolist())}")
        print(f"ZIP_TEST={'OK' if bad is None else bad}")
        for item in archive.infolist():
            print(item.filename)


if __name__ == "__main__":
    main()
