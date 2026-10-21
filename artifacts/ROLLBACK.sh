#!/usr/bin/env bash
# Restore the original configuration fixture to an explicitly named COPY.
set -euo pipefail
here="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
target="${1:?Usage: ROLLBACK.sh /absolute/path/to/configuration-copy.php}"
original="$here/original-web.php"
expected="d8093e6cdc08b83dc18f4837645cc9c31a0f1b39a027414ac128dba157be7703"
if [[ "$(realpath -m "$target")" == "$(realpath -m "$here/../config/web.php")" ]]; then
  printf '%s\n' 'Use a separate fixture copy; live Yii2 configuration is kept unchanged.' >&2
  exit 2
fi
actual="$(sha256sum "$original" | cut -d' ' -f1)"
[[ "$actual" == "$expected" ]]
cp -- "$original" "$target"
cmp -- "$original" "$target"
printf '%s\n' 'ROLLBACK PASS: original SHA256 restored'
