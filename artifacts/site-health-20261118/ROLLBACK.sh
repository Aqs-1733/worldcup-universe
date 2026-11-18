#!/usr/bin/env bash
# Restore the source paths changed by the 2026-11-18 site-health update.
set -euo pipefail
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
TARGET="${1:-$(pwd -P)}"
if [[ ! -d "$TARGET" ]]; then
  echo "Target directory does not exist: $TARGET" >&2
  exit 2
fi
restore_file() {
  local rel="$1"
  rel="${rel%$'\r'}"
  [[ -z "$rel" ]] && return 0
  local source="$SCRIPT_DIR/ROLLBACK_BASELINE/$rel"
  local destination="$TARGET/$rel"
  [[ -f "$source" ]] || { echo "Missing rollback source: $source" >&2; exit 3; }
  mkdir -p -- "$(dirname -- "$destination")"
  cp -- "$source" "$destination"
}
remove_file() {
  local rel="$1"
  rel="${rel%$'\r'}"
  [[ -z "$rel" ]] && return 0
  rm -f -- "$TARGET/$rel"
}
while IFS= read -r rel || [[ -n "$rel" ]]; do restore_file "$rel"; done < "$SCRIPT_DIR/RESTORE_FILES.txt"
while IFS= read -r rel || [[ -n "$rel" ]]; do remove_file "$rel"; done < "$SCRIPT_DIR/NEW_FILES.txt"
echo "RESTORED $(wc -l < "$SCRIPT_DIR/RESTORE_FILES.txt") baseline paths and removed $(wc -l < "$SCRIPT_DIR/NEW_FILES.txt") added paths in $TARGET"
