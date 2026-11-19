#!/usr/bin/env bash
# Restore the source paths changed by the 2026-11-19 forum cleanup.
set -euo pipefail
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
TARGET="${1:-$(pwd -P)}"
[[ -d "$TARGET" ]] || { echo "Target directory does not exist: $TARGET" >&2; exit 2; }
restore_file() {
  local rel="$1" source destination
  rel="${rel%$'\r'}"; [[ -z "$rel" ]] && return 0
  source="$SCRIPT_DIR/BASELINE/$rel"; destination="$TARGET/$rel"
  [[ -f "$source" ]] || { echo "Missing rollback source: $source" >&2; exit 3; }
  mkdir -p -- "$(dirname -- "$destination")"
  cp -- "$source" "$destination"
}
while IFS= read -r rel || [[ -n "$rel" ]]; do restore_file "$rel"; done < "$SCRIPT_DIR/RESTORE_FILES.txt"
removed=0
while IFS= read -r rel || [[ -n "$rel" ]]; do
  rel="${rel%$'\r'}"; [[ -z "$rel" ]] && continue
  rm -f -- "$TARGET/$rel"; removed=$((removed+1))
done < "$SCRIPT_DIR/NEW_FILES.txt"
echo "RESTORED $(wc -l < "$SCRIPT_DIR/RESTORE_FILES.txt") baseline paths and removed $removed added paths in $TARGET"
