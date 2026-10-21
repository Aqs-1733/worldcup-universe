#!/usr/bin/env bash
# Restore only the independent topic fixture; never overwrite the served view.
set -euo pipefail
here="$(cd -- "$(dirname -- "$0")" && pwd)"
target="${1:?Pass an absolute path ending in rollback-copy.php}"
case "$target" in */rollback-copy.php) ;; *) echo 'Separate rollback-copy.php required'; exit 2;; esac
expected="0fb952fcd3a715641d720cf7188febe125d972888521b684914804eb2e811775"
[ "$(sha256sum "$here/original-topic.php" | cut -d' ' -f1)" = "$expected" ]
cp -- "$here/original-topic.php" "$target"
cmp -- "$here/original-topic.php" "$target"
echo 'ROLLBACK PASS: original topic SHA256 restored on copy'
