#!/usr/bin/env bash
# WorldCup Universe: restore a backup made by scripts/backup.sh into the database named in .env.
# Usage:
#   sudo bash scripts/restore.sh --db /var/backups/worldcup-universe/db-YYYYmmdd-HHMMSS.sql.gz \
#        [--files /var/backups/worldcup-universe/files-YYYYmmdd-HHMMSS.tar.gz] [--keep-env] --yes
# Safety: nothing happens without --yes; a fresh backup of the current state is taken first (skip: --no-pre-backup).
# The dump replaces the tables it contains (DROP TABLE IF EXISTS ...); tables absent from the dump are left in place.
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
ENV_FILE="$APP_DIR/.env"
DB_FILE=""; FILES_FILE=""; CONFIRM=0; PRE_BACKUP=1; KEEP_ENV=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --db) DB_FILE="$2"; shift 2;;
    --files) FILES_FILE="$2"; shift 2;;
    --yes) CONFIRM=1; shift;;
    --no-pre-backup) PRE_BACKUP=0; shift;;
    --keep-env) KEEP_ENV=1; shift;;
    *) echo "Unknown option: $1" >&2; exit 2;;
  esac
done
[[ -n "$DB_FILE" || -n "$FILES_FILE" ]] || { echo "Nothing to restore: pass --db and/or --files." >&2; exit 2; }

env_get() {
  local line
  line="$(grep -E "^[[:space:]]*$1=" "$ENV_FILE" | tail -n 1 || true)"
  line="${line#*=}"; line="${line%$'\r'}"
  line="${line#\"}"; line="${line%\"}"; line="${line#\'}"; line="${line%\'}"
  printf '%s' "$line"
}
DB_HOST="$(env_get DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="$(env_get DB_DATABASE)"; DB_USERNAME="$(env_get DB_USERNAME)"; DB_PASSWORD="$(env_get DB_PASSWORD)"
[[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ ]] || { echo "ERROR: DB_DATABASE missing or invalid in .env" >&2; exit 1; }

echo "Target application: $APP_DIR"
[[ -n "$DB_FILE" ]] && echo "  database  $DB_DATABASE@$DB_HOST:$DB_PORT  <=  $DB_FILE"
[[ -n "$FILES_FILE" ]] && echo "  files     uploads, data/team, data/personal, videos  <=  $FILES_FILE"
if [[ $CONFIRM -ne 1 ]]; then echo "Dry run only. Re-run with --yes to overwrite the data above." >&2; exit 3; fi

[[ -z "$DB_FILE" || -r "$DB_FILE" ]] || { echo "ERROR: cannot read $DB_FILE" >&2; exit 1; }
[[ -z "$FILES_FILE" || -r "$FILES_FILE" ]] || { echo "ERROR: cannot read $FILES_FILE" >&2; exit 1; }
[[ -z "$DB_FILE" ]] || gzip -t "$DB_FILE"
[[ -z "$FILES_FILE" ]] || tar -tzf "$FILES_FILE" > /dev/null

if [[ $PRE_BACKUP -eq 1 ]]; then
  echo "Taking a safety backup of the current state first..."
  BACKUP_DIR="${BACKUP_DIR:-/var/backups/worldcup-universe}/pre-restore" BACKUP_KEEP_DAYS=3650 bash "$APP_DIR/scripts/backup.sh"
fi

# Stop the scheduled writer while data is swapped.
systemctl stop wcu-news-sync.timer 2>/dev/null || true
trap 'systemctl start wcu-news-sync.timer 2>/dev/null || true' EXIT

if [[ -n "$DB_FILE" ]]; then
  CNF="$(mktemp)"; trap 'rm -f "$CNF"; systemctl start wcu-news-sync.timer 2>/dev/null || true' EXIT
  escaped="${DB_PASSWORD//\\/\\\\}"; escaped="${escaped//\"/\\\"}"
  printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\ndefault-character-set=utf8mb4\n' "$DB_HOST" "$DB_PORT" "$DB_USERNAME" "$escaped" > "$CNF"
  chmod 600 "$CNF"
  gunzip -c "$DB_FILE" | mysql --defaults-extra-file="$CNF" "$DB_DATABASE"
  echo "Database restored into $DB_DATABASE."
fi

if [[ -n "$FILES_FILE" ]]; then
  members=(public/uploads data/team data/personal public/assets/media/videos)
  [[ $KEEP_ENV -eq 1 ]] || members+=(.env)
  present=()
  for m in "${members[@]}"; do tar -tzf "$FILES_FILE" "$m" > /dev/null 2>&1 && present+=("$m"); done
  # GNU tar refuses absolute and ../ member names, so extraction cannot leave APP_DIR.
  tar -xzf "$FILES_FILE" -C "$APP_DIR" --no-same-owner "${present[@]}"
  for d in public/uploads data/team data/personal; do
    [[ -d "$APP_DIR/$d" ]] && chgrp -R www-data "$APP_DIR/$d" && find "$APP_DIR/$d" -type d -exec chmod 2775 {} + && find "$APP_DIR/$d" -type f -exec chmod 664 {} +
  done
  [[ -f "$APP_DIR/.env" ]] && chgrp www-data "$APP_DIR/.env" && chmod 640 "$APP_DIR/.env"
  echo "Files restored: ${present[*]}"
fi

# Schema may differ from the code after restoring an older dump; apply pending migrations and drop cached schema.
if [[ -n "$DB_FILE" ]]; then
  sudo -u www-data php "$APP_DIR/yii" migrate/up --interactive=0
  sudo -u www-data php "$APP_DIR/yii" cache/flush-all --interactive=0 || true
fi
systemctl reload "php*-fpm" 2>/dev/null || true
echo "Restore complete."
