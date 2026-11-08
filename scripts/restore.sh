#!/usr/bin/env bash
# WorldCup Universe: restore a backup made by scripts/backup.sh into the database named in .env.
# Usage:
#   sudo bash scripts/restore.sh --db /var/backups/worldcup-universe/db-YYYYmmdd-HHMMSS.sql.gz \
#        [--files /var/backups/worldcup-universe/files-YYYYmmdd-HHMMSS.tar.gz] [--with-env] --yes
# Safety: nothing happens without --yes. Writers are stopped first (scripts/maintenance.sh on: Nginx answers 503 for
# every PHP route, timers stopped, PHP-FPM drained and stopped), then a backup of the current state is taken
# (skip: --no-pre-backup), and only then are database and files swapped. If any step fails the site stays in
# maintenance so the database and upload folders are never served half-restored.
# The current .env (database credentials, cookie key) is kept; --with-env also restores the archived .env.
# The dump replaces the tables it contains (DROP TABLE IF EXISTS ...); tables absent from the dump are left in place.
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
ENV_FILE="$APP_DIR/.env"
DB_FILE=""; FILES_FILE=""; CONFIRM=0; PRE_BACKUP=1; WITH_ENV=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --db) DB_FILE="$2"; shift 2;;
    --files) FILES_FILE="$2"; shift 2;;
    --yes) CONFIRM=1; shift;;
    --no-pre-backup) PRE_BACKUP=0; shift;;
    --with-env) WITH_ENV=1; shift;;
    --keep-env) WITH_ENV=0; shift;;   # the default; accepted for older instructions
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

members=(public/uploads data/team data/personal storage/uploads public/assets/media/videos)
[[ $WITH_ENV -eq 1 ]] && members+=(.env)
echo "Target application: $APP_DIR"
[[ -n "$DB_FILE" ]] && echo "  database  $DB_DATABASE@$DB_HOST:$DB_PORT  <=  $DB_FILE"
[[ -n "$FILES_FILE" ]] && echo "  files     ${members[*]}  <=  $FILES_FILE"
[[ $WITH_ENV -eq 1 ]] || echo "  .env      kept as is (pass --with-env to restore the archived one)"
if [[ $CONFIRM -ne 1 ]]; then echo "Dry run only. Re-run with --yes to overwrite the data above." >&2; exit 3; fi
# Never swap data while scripts/deploy.sh or scripts/update-production.sh is running (and keep them out until done).
exec 9>/run/lock/wcu-deploy.lock
flock -n 9 || { echo "ERROR: a deployment or update is running (/run/lock/wcu-deploy.lock); retry when it has finished." >&2; exit 1; }
export WCU_DEPLOY_LOCK_HELD=1

[[ -z "$DB_FILE" || -r "$DB_FILE" ]] || { echo "ERROR: cannot read $DB_FILE" >&2; exit 1; }
[[ -z "$FILES_FILE" || -r "$FILES_FILE" ]] || { echo "ERROR: cannot read $FILES_FILE" >&2; exit 1; }
[[ -z "$DB_FILE" ]] || gzip -t "$DB_FILE"
[[ -z "$FILES_FILE" ]] || tar -tzf "$FILES_FILE" > /dev/null

# --- stop every writer: web (Nginx 503 + PHP-FPM stopped), timers, running sync/backup ---------------------------
FPM_SERVICE="$(systemctl list-units --type=service --all --no-legend 'php*-fpm.service' 2>/dev/null | awk '{print $1}' | grep -m1 fpm || true)"
CNF=""; DONE=0; STAGE=prepare; PRE_DB=""; PRE_FILES=""
# Already in maintenance (e.g. an earlier restore failed half-way): an early abort must not reopen the site.
WAS_ON=0; [[ -f "$APP_DIR/storage/runtime/maintenance.flag" ]] && WAS_ON=1
PRE_DIR="${BACKUP_DIR:-/var/backups/worldcup-universe}/pre-restore"
finish() {
  [[ -n "$CNF" ]] && rm -f "$CNF"
  # PHP-FPM may run again in every case: while the flag is set Nginx answers 503 before any request reaches it.
  [[ -n "$FPM_SERVICE" ]] && systemctl start "$FPM_SERVICE" 2>/dev/null
  if [[ $DONE -eq 1 ]]; then
    bash "$APP_DIR/scripts/maintenance.sh" off
  elif [[ $STAGE == prepare && $WAS_ON -eq 1 ]]; then
    echo "RESTORE ABORTED before any data was changed; maintenance was already on before this run and stays on." >&2
  elif [[ $STAGE == prepare ]]; then
    # Nothing was imported or extracted yet, so the live data is intact: abort cleanly and let the site back in.
    rm -f "$APP_DIR/storage/runtime/maintenance.flag"; systemctl start wcu-news-sync.timer wcu-backup.timer 2>/dev/null
    echo "RESTORE ABORTED before any data was changed (writers not confirmed stopped, or the safety backup failed)." >&2
    echo "The site and timers are back as they were. Fix the cause shown above and run the restore again." >&2
  else
    echo "" >&2
    echo "RESTORE FAILED WHILE DATA WAS BEING REPLACED: the database and/or upload folders may be only partly restored." >&2
    echo "The site STAYS IN MAINTENANCE (503) and the timers stay stopped. Do not switch maintenance off now." >&2
    if [[ -n "$PRE_DB" ]]; then
      echo "Put back the state from just before this restore with the safety backup:" >&2
      echo "  sudo bash $APP_DIR/scripts/restore.sh --db $PRE_DB --files $PRE_FILES --no-pre-backup --yes" >&2
    else
      echo "No safety backup was taken (--no-pre-backup); restore the newest good backup from ${PRE_DIR%/pre-restore}/ the same way." >&2
    fi
    echo "That restore switches maintenance off when it succeeds; only use 'scripts/maintenance.sh off' after checking the data." >&2
  fi
}
trap finish EXIT
# maintenance.sh exits 4 when a sync, backup or PHP request is still running after its wait: abort, touch nothing.
bash "$APP_DIR/scripts/maintenance.sh" on
if [[ -n "$FPM_SERVICE" ]]; then
  systemctl stop "$FPM_SERVICE"
  ! pgrep -f 'php-fpm: pool worldcup-universe' >/dev/null || { echo "ERROR: PHP-FPM workers still alive after stopping $FPM_SERVICE." >&2; exit 4; }
  echo "Stopped $FPM_SERVICE: no PHP request can write any more."
fi

if [[ $PRE_BACKUP -eq 1 ]]; then
  echo "Taking a safety backup of the current state first..."
  BACKUP_DIR="$PRE_DIR" BACKUP_KEEP_DAYS=3650 bash "$APP_DIR/scripts/backup.sh"
  PRE_DB="$(ls -t "$PRE_DIR"/db-*.sql.gz | head -n 1)"; PRE_FILES="$(ls -t "$PRE_DIR"/files-*.tar.gz | head -n 1)"
fi

STAGE=swap   # from here on a failure can leave data half-restored

if [[ -n "$DB_FILE" ]]; then
  CNF="$(mktemp)"; chmod 600 "$CNF"
  escaped="${DB_PASSWORD//\\/\\\\}"; escaped="${escaped//\"/\\\"}"
  printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\ndefault-character-set=utf8mb4\n' "$DB_HOST" "$DB_PORT" "$DB_USERNAME" "$escaped" > "$CNF"
  gunzip -c "$DB_FILE" | mysql --defaults-extra-file="$CNF" "$DB_DATABASE"
  echo "Database restored into $DB_DATABASE."
fi

if [[ -n "$FILES_FILE" ]]; then
  present=()
  for m in "${members[@]}"; do tar -tzf "$FILES_FILE" "$m" > /dev/null 2>&1 && present+=("$m"); done
  # GNU tar refuses absolute and ../ member names, so extraction cannot leave APP_DIR.
  tar -xzf "$FILES_FILE" -C "$APP_DIR" --no-same-owner "${present[@]}"
  for d in public/uploads data/team data/personal storage/uploads public/assets/media/videos; do
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
DONE=1
echo "Restore complete."
