#!/usr/bin/env bash
# WorldCup Universe: encrypted off-site copy of the production data in a PRIVATE GitHub repository.
#
#   sudo bash scripts/offsite-backup.sh             # run once now (wcu-offsite-backup.timer runs it nightly)
#   sudo bash scripts/offsite-backup.sh --no-push   # build and encrypt the snapshot, commit nothing (test)
#
# What is copied: the database dump plus public/uploads (administrator images/videos), data/team + data/personal
# (coursework), storage/uploads and .env - exactly scripts/backup.sh's db + files archives. The local open-licence
# video shelf is left out unless OFFSITE_INCLUDE_VIDEOS=1 (180 MB, rarely changes, kept in the server's own backups).
# Why it is safe on GitHub: the bundle is encrypted with the team's PUBLIC key (scripts/backup-crypt.php); the server
# cannot decrypt it, and only the holder of the secret key (kept off the server) can. The repository must be private
# anyway, and the deploy key used to push can write to that one repository only.
#
# Configuration /etc/worldcup-universe/offsite.conf (root, 0600), e.g.
#   OFFSITE_REPO=git@github.com:OWNER/worldcup-universe-backups.git
#   OFFSITE_SSH_KEY=/etc/worldcup-universe/offsite_deploy_ed25519
#   OFFSITE_PUBLIC_KEY=/etc/worldcup-universe/backup-public.key
#   OFFSITE_KEEP=14                       # snapshots kept in the working tree (older ones remain in git history)
#   OFFSITE_GIT_NAME=... OFFSITE_GIT_EMAIL=...
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
CONF=/etc/worldcup-universe/offsite.conf
STATE_DIR=/var/lib/worldcup-universe; STATUS="$STATE_DIR/offsite-last.json"
WORK_ROOT=/var/backups/worldcup-universe/offsite
PUSH=1; [[ "${1:-}" == "--no-push" ]] && PUSH=0
[[ $EUID -eq 0 ]] || { echo "ERROR: run with sudo." >&2; exit 1; }
[[ -r "$CONF" ]] || { echo "ERROR: $CONF missing; off-site backup is not configured (see docs/deployment.md)." >&2; exit 1; }
# shellcheck disable=SC1090
source "$CONF"
: "${OFFSITE_REPO:?}" "${OFFSITE_SSH_KEY:?}" "${OFFSITE_PUBLIC_KEY:?}"
OFFSITE_KEEP="${OFFSITE_KEEP:-14}"; OFFSITE_INCLUDE_VIDEOS="${OFFSITE_INCLUDE_VIDEOS:-0}"
KNOWN_HOSTS=/etc/worldcup-universe/github_known_hosts
export GIT_SSH_COMMAND="ssh -i $OFFSITE_SSH_KEY -o IdentitiesOnly=yes -o UserKnownHostsFile=$KNOWN_HOSTS -o StrictHostKeyChecking=yes -o BatchMode=yes"

mkdir -p "$STATE_DIR" "$WORK_ROOT"; chmod 700 "$WORK_ROOT"
exec 9>/run/lock/wcu-offsite.lock; flock -n 9 || { echo "Another off-site backup is running."; exit 0; }
STAMP="$(date +%Y%m%d-%H%M%S)"; WORK="$WORK_ROOT/work-$STAMP"; REPO="$WORK_ROOT/repo"
record() { # result message [extra json]
  printf '{"time":"%s","snapshot":"%s","result":"%s","message":"%s"%s}\n' "$(date '+%F %T')" "$STAMP" "$1" "${2//\"/\'}" "${3:-}" > "$STATUS"
}
trap 'rc=$?; rm -rf "$WORK"; if [[ $rc -ne 0 ]]; then record failed "exit $rc; see journalctl -u wcu-offsite-backup"; fi' EXIT
[[ ! -f "$APP_DIR/storage/runtime/maintenance.flag" ]] || { record skipped "maintenance mode is on"; echo "Maintenance mode is on; skipped."; exit 0; }
umask 077; mkdir -p "$WORK/bundle"

# 1. Database + files. Holding the news-sync lock keeps the dump and the row counts below from the same moment.
echo "==> Backup (database, uploads, coursework, .env)"
exec 8>/run/lock/wcu-news-sync.lock; flock -w 900 8 || { echo "ERROR: news sync still running after 15 min." >&2; exit 1; }
videos=never; [[ "$OFFSITE_INCLUDE_VIDEOS" == 1 ]] && videos=always
BACKUP_DIR="$WORK/bundle" BACKUP_KEEP_DAYS=36500 BACKUP_VIDEOS=$videos APP_DIR="$APP_DIR" bash "$APP_DIR/scripts/backup.sh"
env_get() { local l; l="$(grep -E "^[[:space:]]*$1=" "$APP_DIR/.env" | tail -n1 || true)"; l="${l#*=}"; l="${l%$'\r'}"; l="${l#\"}"; l="${l%\"}"; printf '%s' "$l"; }
DB="$(env_get DB_DATABASE)"; CNF="$WORK/my.cnf"; pw="$(env_get DB_PASSWORD)"; pw="${pw//\\/\\\\}"; pw="${pw//\"/\\\"}"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\n' "$(env_get DB_HOST)" "$(env_get DB_PORT)" "$(env_get DB_USERNAME)" "$pw" > "$CNF"
union="$(mysql --defaults-extra-file="$CNF" -N -e "SET SESSION group_concat_max_len=1000000; SELECT GROUP_CONCAT(CONCAT('SELECT ''',TABLE_NAME,''',COUNT(*) FROM \`',TABLE_NAME,'\`') SEPARATOR ' UNION ALL ') FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_TYPE='BASE TABLE'")"
counts="$(mysql --defaults-extra-file="$CNF" -N "$DB" -e "$union" | awk 'BEGIN{ORS=""; print "{"} {printf "%s\"%s\":%s", (NR>1?",":""), $1, $2} END{print "}"}')"
exec 8>&-
uploads="$(find "$APP_DIR/public/uploads" "$APP_DIR/data/team/uploads" "$APP_DIR/data/personal/uploads" -type f ! -name .gitkeep 2>/dev/null | wc -l)"

# 2. One bundle, encrypted for the team's public key, split under GitHub's 50 MB warning size.
echo "==> Encrypt"
( cd "$WORK/bundle" && tar -cf "$WORK/snapshot.tar" --exclude='.videos-state' . )
plain_sha="$(sha256sum "$WORK/snapshot.tar" | cut -c1-64)"; plain_bytes="$(stat -c %s "$WORK/snapshot.tar")"
php "$APP_DIR/scripts/backup-crypt.php" encrypt "$OFFSITE_PUBLIC_KEY" "$WORK/snapshot.tar" "$WORK/snapshot.wcub"
rm -f "$WORK/snapshot.tar"
mkdir -p "$WORK/out/$STAMP"; ( cd "$WORK/out/$STAMP" && split -b 45M -d -a 3 "$WORK/snapshot.wcub" backup.wcub.part- )
parts="$(cd "$WORK/out/$STAMP" && ls backup.wcub.part-* | awk 'BEGIN{ORS=""} {printf "%s\"%s\"", (NR>1?",":""), $0}')"
commit="$(git -C "$APP_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)"
cat > "$WORK/out/$STAMP/summary.json" <<JSON
{"snapshot":"$STAMP","created":"$(date '+%F %T %z')","app_commit":"$commit","plain_sha256":"$plain_sha","plain_bytes":$plain_bytes,
 "parts":[$parts],"contents":"$(cd "$WORK/bundle" && ls | tr '\n' ' ' | sed 's/ $//')","uploaded_files":$uploads,"row_counts":$counts}
JSON
size="$(du -sh "$WORK/out/$STAMP" | cut -f1)"
if [[ $PUSH -eq 0 ]]; then
  mkdir -p "$WORK_ROOT/test-$STAMP"; cp -r "$WORK/out/$STAMP" "$WORK_ROOT/test-$STAMP/"
  record built "not pushed (--no-push); kept in $WORK_ROOT/test-$STAMP" ",\"size\":\"$size\""
  echo "Built and encrypted $size in $WORK_ROOT/test-$STAMP (not pushed)."; exit 0
fi

# 3. Commit to the private repository; a normal fast-forward push, never a force push.
echo "==> Push to $OFFSITE_REPO"
if [[ ! -d "$REPO/.git" ]]; then rm -rf "$REPO"; git clone -q "$OFFSITE_REPO" "$REPO"; fi
git -C "$REPO" config user.name "${OFFSITE_GIT_NAME:-WorldCup Universe server}"; git -C "$REPO" config user.email "${OFFSITE_GIT_EMAIL:-backup@localhost}"
if git -C "$REPO" rev-parse -q --verify HEAD >/dev/null; then
  branch="$(git -C "$REPO" symbolic-ref --short HEAD)"
  git -C "$REPO" fetch -q origin && git -C "$REPO" reset -q --hard "origin/$branch"   # this clone is only ever written by this script
else
  branch=main; git -C "$REPO" symbolic-ref HEAD refs/heads/main                       # first snapshot into an empty repository
fi
mkdir -p "$REPO/snapshots"; mv "$WORK/out/$STAMP" "$REPO/snapshots/"; printf '%s\n' "$STAMP" > "$REPO/LATEST"
[[ -f "$REPO/README.md" ]] || cat > "$REPO/README.md" <<'MD'
# WorldCup Universe - encrypted production backups

Written nightly by the production server (`scripts/offsite-backup.sh` in the application repository). Every
`snapshots/<time>/backup.wcub.part-*` set is one encrypted bundle (database dump, uploaded files, coursework, `.env`);
`summary.json` holds non-sensitive metadata (sizes, row counts, checksum). Without the team's secret key the parts are
unreadable. To restore on Windows run `fetch-backup.bat` from the application, see docs/deployment.md.
MD
keep="$(ls -1 "$REPO/snapshots" | sort | head -n -"$OFFSITE_KEEP" || true)"
for old in $keep; do git -C "$REPO" rm -rq "snapshots/$old"; done
git -C "$REPO" add -A
git -C "$REPO" commit -qm "backup $STAMP (app $commit, $size)"
git -C "$REPO" push -q origin "HEAD:$branch"
record ok "pushed snapshots/$STAMP" ",\"size\":\"$size\",\"repo\":\"$OFFSITE_REPO\""
echo "Off-site backup $STAMP pushed ($size, $(echo "$parts" | tr -cd ',' | wc -c | awk '{print $1+1}') part(s); keeping $OFFSITE_KEEP snapshots)."
