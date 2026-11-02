#!/usr/bin/env bash
# WorldCup Universe: consistent backup of the MySQL database and every file the website writes.
#   db-<timestamp>.sql.gz     mysqldump --single-transaction of DB_DATABASE (no DROP DATABASE; restores into any name)
#   files-<timestamp>.tar.gz  public/uploads, data/team, data/personal, public/assets/media/videos, .env
# Usage: sudo bash scripts/backup.sh            (BACKUP_DIR, BACKUP_KEEP_DAYS and APP_DIR may be overridden)
# Credentials are read from .env and handed to mysqldump through a 0600 temp file, never on the command line.
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/worldcup-universe}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
ENV_FILE="$APP_DIR/.env"

env_get() { # last KEY=value in .env, surrounding quotes removed; the file is parsed, never sourced
  local line
  line="$(grep -E "^[[:space:]]*$1=" "$ENV_FILE" | tail -n 1 || true)"
  line="${line#*=}"; line="${line%$'\r'}"
  line="${line#\"}"; line="${line%\"}"; line="${line#\'}"; line="${line%\'}"
  printf '%s' "$line"
}

[[ -r "$ENV_FILE" ]] || { echo "ERROR: $ENV_FILE not readable (run as root or the deploy user)." >&2; exit 1; }
DB_HOST="$(env_get DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="$(env_get DB_DATABASE)"; DB_USERNAME="$(env_get DB_USERNAME)"; DB_PASSWORD="$(env_get DB_PASSWORD)"
[[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ ]] || { echo "ERROR: DB_DATABASE missing or invalid in .env" >&2; exit 1; }

umask 077
mkdir -p "$BACKUP_DIR"; chmod 700 "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
DB_OUT="$BACKUP_DIR/db-$STAMP.sql.gz"; FILES_OUT="$BACKUP_DIR/files-$STAMP.tar.gz"
CNF="$(mktemp)"; trap 'rm -f "$CNF" "$DB_OUT.part" "$FILES_OUT.part"' EXIT
escaped="${DB_PASSWORD//\\/\\\\}"; escaped="${escaped//\"/\\\"}"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\ndefault-character-set=utf8mb4\n' "$DB_HOST" "$DB_PORT" "$DB_USERNAME" "$escaped" > "$CNF"

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --hex-blob \
  --no-tablespaces --set-gtid-purged=OFF "$DB_DATABASE" 2>/dev/null | gzip -6 > "$DB_OUT.part" \
  || mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --hex-blob \
       --no-tablespaces "$DB_DATABASE" | gzip -6 > "$DB_OUT.part"   # MariaDB has no --set-gtid-purged
gzip -t "$DB_OUT.part" && mv "$DB_OUT.part" "$DB_OUT"

paths=()
for p in public/uploads data/team data/personal public/assets/media/videos .env; do [[ -e "$APP_DIR/$p" ]] && paths+=("$p"); done
tar -czf "$FILES_OUT.part" -C "$APP_DIR" "${paths[@]}"
mv "$FILES_OUT.part" "$FILES_OUT"

find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'files-*.tar.gz' \) -mtime +"$BACKUP_KEEP_DAYS" -delete
echo "Backup written: $DB_OUT ($(du -h "$DB_OUT" | cut -f1)), $FILES_OUT ($(du -h "$FILES_OUT" | cut -f1))"
