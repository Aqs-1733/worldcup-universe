#!/usr/bin/env bash
# 文件作用：服务器备份脚本：一致性导出数据库，并打包网站写入的所有文件。
# 作者：蒋林瀞_2413507
# WorldCup Universe: consistent backup of the MySQL database and every file the website writes.
#   db-<timestamp>.sql.gz     mysqldump --single-transaction of DB_DATABASE (no DROP DATABASE; restores into any name)
#   files-<timestamp>.tar.gz  public/uploads (administrator media), data/team + data/personal (coursework, incl. their
#                             uploads/), storage/uploads (private AI uploads), .env
#   videos-<timestamp>.tar.gz public/assets/media/videos (local open-licence video shelf, ~180 MB). It rarely changes, so
#                             BACKUP_VIDEOS=auto (default) writes it only when the folder differs from the last archive in
#                             BACKUP_DIR; always / never force it. Deployments and updates use never (they do not touch it).
# Usage: sudo bash scripts/backup.sh            (BACKUP_DIR, BACKUP_KEEP_DAYS, BACKUP_VIDEOS and APP_DIR may be overridden)
# Credentials are read from .env and handed to mysqldump through a 0600 temp file, never on the command line.
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/worldcup-universe}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
BACKUP_VIDEOS="${BACKUP_VIDEOS:-auto}"
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
DB_OUT="$BACKUP_DIR/db-$STAMP.sql.gz"; FILES_OUT="$BACKUP_DIR/files-$STAMP.tar.gz"; VIDEOS_OUT="$BACKUP_DIR/videos-$STAMP.tar.gz"
CNF="$(mktemp)"; trap 'rm -f "$CNF" "$DB_OUT.part" "$FILES_OUT.part" "$VIDEOS_OUT.part"' EXIT
escaped="${DB_PASSWORD//\\/\\\\}"; escaped="${escaped//\"/\\\"}"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\ndefault-character-set=utf8mb4\n' "$DB_HOST" "$DB_PORT" "$DB_USERNAME" "$escaped" > "$CNF"

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --hex-blob \
  --no-tablespaces --set-gtid-purged=OFF "$DB_DATABASE" 2>/dev/null | gzip -6 > "$DB_OUT.part" \
  || mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --hex-blob \
       --no-tablespaces "$DB_DATABASE" | gzip -6 > "$DB_OUT.part"   # MariaDB has no --set-gtid-purged
gzip -t "$DB_OUT.part" && mv "$DB_OUT.part" "$DB_OUT"

paths=()
for p in public/uploads data/team data/personal storage/uploads .env; do [[ -e "$APP_DIR/$p" ]] && paths+=("$p"); done
tar -czf "$FILES_OUT.part" -C "$APP_DIR" "${paths[@]}"
mv "$FILES_OUT.part" "$FILES_OUT"

VIDEOS_MSG=""
VID="$APP_DIR/public/assets/media/videos"
if [[ -d "$VID" && "$BACKUP_VIDEOS" != never ]]; then
  state="$(find "$VID" -type f -printf '%P %s %T@\n' | sort | sha256sum | cut -c1-64)"
  last="$(cat "$BACKUP_DIR/.videos-state" 2>/dev/null || true)"
  if [[ "$BACKUP_VIDEOS" == always || "$state" != "$last" ]] || ! ls "$BACKUP_DIR"/videos-*.tar.gz >/dev/null 2>&1; then
    tar -czf "$VIDEOS_OUT.part" -C "$APP_DIR" public/assets/media/videos
    mv "$VIDEOS_OUT.part" "$VIDEOS_OUT"; printf '%s\n' "$state" > "$BACKUP_DIR/.videos-state"
    VIDEOS_MSG=", $VIDEOS_OUT ($(du -h "$VIDEOS_OUT" | cut -f1))"
  else
    VIDEOS_MSG=", videos unchanged since $(ls -t "$BACKUP_DIR"/videos-*.tar.gz | head -n 1)"
  fi
fi

find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'files-*.tar.gz' \) -mtime +"$BACKUP_KEEP_DAYS" -delete
# The video archive is only rewritten on change, so the newest one is kept whatever its age.
newest_videos="$(ls -t "$BACKUP_DIR"/videos-*.tar.gz 2>/dev/null | head -n 1 || true)"
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'videos-*.tar.gz' -mtime +"$BACKUP_KEEP_DAYS" ! -path "$newest_videos" -delete
echo "Backup written: $DB_OUT ($(du -h "$DB_OUT" | cut -f1)), $FILES_OUT ($(du -h "$FILES_OUT" | cut -f1))$VIDEOS_MSG"
