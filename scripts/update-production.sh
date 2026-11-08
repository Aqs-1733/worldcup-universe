#!/usr/bin/env bash
# WorldCup Universe: update production to the newest commit of the tracked GitHub branch (default main).
#
#   sudo bash scripts/update-production.sh            # update if origin/main has new commits, otherwise exit 0
#   sudo bash scripts/update-production.sh --check    # only report whether an update is available
#   sudo bash scripts/update-production.sh --clear-failure   # allow a commit that failed before to be retried
#
# Run by wcu-update.timer (not enabled by deploy.sh; enable it explicitly). Steps, each stopping the run on failure:
#   1. one run at a time (shares /run/lock/wcu-deploy.lock with scripts/deploy.sh); refuses during maintenance;
#   2. git fetch as the code owner; nothing new -> exit 0; a commit that failed before is skipped until cleared;
#   3. refuses uncommitted changes to tracked files, a checkout on another branch, and non fast-forward history;
#   4. pre-flight of the new commit in a temporary worktree: php -l on every PHP file, bash -n on every script;
#   5. backup of database and uploaded files (scripts/backup.sh -> /var/backups/worldcup-universe/pre-update);
#   6. git merge --ff-only, then scripts/deploy.sh --skip-backup (Composer, Yii2 migrations, config, services);
#   7. HTTP health checks and php yii media/check.
# .env, the administrator password, database rows and uploads are never reset: deploy.sh only fills missing .env keys.
# On failure after the code changed: if no new migration was applied, the code is put back on the previous commit
# and redeployed (the schema did not change, so this is safe); if migrations were applied nothing is rolled back
# automatically and the log prints the exact recovery commands.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE="${WCU_UPDATE_REMOTE:-origin}"; BRANCH="${WCU_UPDATE_BRANCH:-main}"
STATE_DIR=/var/lib/worldcup-universe; FAILED_MARK="$STATE_DIR/update-failed"
LOG_DIR=/var/log/worldcup-universe; LOG="$LOG_DIR/update.log"
CHECK_ONLY=0; CLEAR=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --check) CHECK_ONLY=1; shift;;
    --clear-failure) CLEAR=1; shift;;
    -h|--help) sed -n '2,20p' "$0"; exit 0;;
    *) echo "Unknown option: $1" >&2; exit 2;;
  esac
done
[[ $EUID -eq 0 ]] || { echo "ERROR: run with sudo." >&2; exit 1; }
mkdir -p "$STATE_DIR" "$LOG_DIR"; chmod 750 "$LOG_DIR"; touch "$LOG"; chmod 640 "$LOG"
exec > >(tee -a "$LOG") 2>&1
log() { printf '%s %s\n' "$(date '+%F %T')" "$*"; }
refuse() { log "REFUSED: $*"; exit 3; }

if [[ $CLEAR -eq 1 ]]; then rm -f "$FAILED_MARK"; log "failure marker cleared"; exit 0; fi

# --- 1. single run ------------------------------------------------------------------------------------------------
exec 9>/run/lock/wcu-deploy.lock
flock -n 9 || { log "another deployment or update is running; nothing done."; exit 0; }
export WCU_DEPLOY_LOCK_HELD=1   # scripts/deploy.sh below runs under this lock
[[ ! -f "$APP_DIR/storage/runtime/maintenance.flag" ]] || refuse "maintenance mode is on (restore in progress?)."

OWNER="$(stat -c %U "$APP_DIR")"
as_owner() { sudo -u "$OWNER" -H "$@"; }
G() { as_owner git -C "$APP_DIR" "$@"; }

# --- 2. anything new? -----------------------------------------------------------------------------------------------
G fetch --quiet "$REMOTE" "$BRANCH" || { log "ERROR: git fetch $REMOTE $BRANCH failed (network?)."; exit 1; }
PREV="$(G rev-parse HEAD)"; NEW="$(G rev-parse "$REMOTE/$BRANCH")"
if [[ "$PREV" == "$NEW" ]]; then [[ $CHECK_ONLY -eq 1 ]] && log "up to date at ${PREV:0:7}"; exit 0; fi
if [[ -f "$FAILED_MARK" && "$(cat "$FAILED_MARK")" == "$NEW" ]]; then
  log "skipping ${NEW:0:7}: it failed before (see $LOG). Fix forward with a new commit or run --clear-failure."; exit 0
fi
log "update available: ${PREV:0:7} -> ${NEW:0:7} ($REMOTE/$BRANCH)"
G log --oneline "$PREV..$NEW" | sed 's/^/    /'
[[ $CHECK_ONLY -eq 1 ]] && exit 0

# --- 3. safe to move? -------------------------------------------------------------------------------------------------
[[ "$(G symbolic-ref -q --short HEAD || true)" == "$BRANCH" ]] || refuse "checkout is not on branch $BRANCH (git checkout $BRANCH first)."
dirty="$(G status --porcelain --untracked-files=no)"
[[ -z "$dirty" ]] || refuse "uncommitted changes to tracked files on the server:"$'\n'"$dirty"
G merge-base --is-ancestor "$PREV" "$NEW" || refuse "$REMOTE/$BRANCH is not a fast-forward of ${PREV:0:7} (history rewritten?)."

# --- 4. pre-flight in a throw-away worktree ---------------------------------------------------------------------------
PRE="$(as_owner mktemp -d /tmp/wcu-preflight.XXXXXX)"
cleanup() { G worktree remove --force "$PRE" >/dev/null 2>&1 || true; rm -rf "$PRE"; }
trap cleanup EXIT
G worktree add --quiet --detach "$PRE" "$NEW"
bad=0
while IFS= read -r f; do php -l "$PRE/$f" >/dev/null 2>&1 || { php -l "$PRE/$f" 2>&1 | head -n 2 || true; bad=1; }; done < <(G -C "$PRE" ls-files '*.php')
# Only the operational scripts; archived notes under artifacts/ are not run by the deployment.
while IFS= read -r f; do bash -n "$PRE/$f" 2>&1 || bad=1; done < <(G -C "$PRE" ls-files 'scripts/*.sh')
[[ $bad -eq 0 ]] || { echo "$NEW" > "$FAILED_MARK"; refuse "pre-flight of ${NEW:0:7} failed (syntax errors above); production untouched."; }
cleanup; trap - EXIT
log "pre-flight passed"

# --- 5. backup ---------------------------------------------------------------------------------------------------------
BACKUP_DIR=/var/backups/worldcup-universe/pre-update BACKUP_KEEP_DAYS=30 bash "$APP_DIR/scripts/backup.sh" \
  || refuse "backup failed; production untouched."
BK_DB="$(ls -t /var/backups/worldcup-universe/pre-update/db-*.sql.gz | head -1)"
BK_FILES="$(ls -t /var/backups/worldcup-universe/pre-update/files-*.tar.gz | head -1)"
applied() { sudo -u www-data -H php "$APP_DIR/yii" migrate/history all --interactive=0 2>/dev/null | grep -cE '^\s+\(' || true; }
MIG_BEFORE="$(applied)"

# --- 6. switch code and deploy -------------------------------------------------------------------------------------------
fail() {
  local why="$1"; echo "$NEW" > "$FAILED_MARK"
  local mig_after; mig_after="$(applied)"
  log "FAILED: $why"
  if [[ "$mig_after" == "$MIG_BEFORE" ]]; then
    log "no new migration was applied; putting the code back on ${PREV:0:7} and redeploying."
    if G reset --quiet --keep "$PREV" && bash "$APP_DIR/scripts/deploy.sh" --skip-backup >/dev/null 2>&1 && health; then
      log "ROLLED BACK to ${PREV:0:7}; site healthy. ${NEW:0:7} is marked failed and will not be retried automatically."
    else
      log "ROLLBACK DID NOT COMPLETE. Manual steps:"; recovery_steps
    fi
  else
    log "$((mig_after - MIG_BEFORE)) new migration(s) were applied: the code is NOT rolled back automatically."
    recovery_steps
  fi
  exit 1
}
recovery_steps() {
  cat <<EOF
  Recovery (run on the server, in $APP_DIR):
    1. Inspect: tail -n 200 $LOG; tail -n 50 storage/logs/yii2.log storage/logs/php-fpm-error.log; journalctl -u wcu-update -n 100
    2a. Fix forward (preferred): push a fixed commit to $BRANCH, then: sudo bash scripts/update-production.sh --clear-failure && sudo bash scripts/update-production.sh
    2b. Back to the previous release, code only (when the migrations are backward compatible):
        sudo -u $OWNER git reset --keep $PREV && sudo bash scripts/deploy.sh --skip-backup
    2c. Back to the previous release with the pre-update data (undoes every write since $BK_DB was taken):
        sudo -u $OWNER git reset --keep $PREV
        sudo bash scripts/restore.sh --db $BK_DB --files $BK_FILES --yes
        sudo bash scripts/deploy.sh --skip-backup
EOF
}
health() {
  local url scheme host port code ok=0
  url="$(grep -E '^APP_URL=' "$APP_DIR/.env" | tail -n1 | cut -d= -f2- | tr -d '"'\''\r')"; scheme="${url%%://*}"; host="${url#*://}"; host="${host%%/*}"
  [[ "$scheme" == https ]] && port=443 || port=80
  check() { # path expected-status
    code="$(curl -k -s -o /dev/null -w '%{http_code}' --max-time 20 --resolve "$host:$port:127.0.0.1" "$scheme://$host$1")"
    printf '    %-18s %s (expect %s)\n' "$1" "$code" "$2"; [[ "$code" == "$2" ]] || ok=1
  }
  check / 200; check /teams 200; check /news 200; check /forum 200; check /watch 200; check /login 200
  check /api/v1/home 200; check /api/v1/session 200; check /admin 302; check /.env 404; check /composer.json 404
  sudo -u www-data -H php "$APP_DIR/yii" media/check >/dev/null 2>&1 || { echo "    media/check FAILED"; ok=1; }
  sudo -u www-data -H php "$APP_DIR/yii" migrate/new --interactive=0 2>/dev/null | grep -q 'No new migrations' || { echo "    pending migrations remain"; ok=1; }
  return $ok
}

G merge --quiet --ff-only "$NEW" || fail "git merge --ff-only failed."
log "code at ${NEW:0:7}; running scripts/deploy.sh --skip-backup"
set +e; bash "$APP_DIR/scripts/deploy.sh" --skip-backup 2>&1 | sed 's/^/    /'; rc=${PIPESTATUS[0]}; set -e
[[ $rc -eq 0 ]] || fail "scripts/deploy.sh exited with $rc."

# --- 7. health ---------------------------------------------------------------------------------------------------------------
log "health checks"
health || fail "health checks failed."
rm -f "$FAILED_MARK"
log "UPDATED ${PREV:0:7} -> ${NEW:0:7}; $(( $(applied) - MIG_BEFORE )) new migration(s); backup $BK_DB"
