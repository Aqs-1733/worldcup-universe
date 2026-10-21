#!/usr/bin/env bash
set -euo pipefail
here="$(cd -- "$(dirname -- "$0")" && pwd)"
target="${1:?Pass a separate rollback-copy.php path}"
case "$target" in */rollback-copy.php|rollback-copy.php) ;; *) echo 'Use a separately named rollback-copy.php'; exit 2;; esac
expected="6d087fedce3b4ef09d236e8bc0ef76ab17b2d82c125e66926d6765362c251dfb"
actual="$(sha256sum "$here/original-main.php" | cut -d' ' -f1)"
[ "$actual" = "$expected" ]
cp -- "$here/original-main.php" "$target"
cmp -- "$here/original-main.php" "$target"
echo 'ROLLBACK PASS: original layout SHA256 restored on copy'
