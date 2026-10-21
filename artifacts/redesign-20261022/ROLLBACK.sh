#!/usr/bin/env bash
# Restore the preserved forum fixture to an independent copy, not the live view.
set -euo pipefail
here="$(cd -- "$(dirname -- "$0")" && pwd)"
target="${1:?Pass an absolute path ending in rollback-copy.php}"
case "$target" in */rollback-copy.php) ;; *) echo 'A separately named rollback-copy.php is required'; exit 2;; esac
expected="8fed1f39d87b634c471730ce2a0e97256dd9bfbde5d0e658f5cc44ae86e15fbf"
actual="$(sha256sum "$here/original-forum.php" | cut -d' ' -f1)"
[ "$actual" = "$expected" ]
cp -- "$here/original-forum.php" "$target"
cmp -- "$here/original-forum.php" "$target"
echo 'ROLLBACK PASS: original forum SHA256 restored on copy'
