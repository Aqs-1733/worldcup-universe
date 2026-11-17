#!/usr/bin/env bash
# WorldCup Universe: one-screen production health report.   sudo bash scripts/status.sh
# Exit 0 when everything looks fine, 1 when something needs attention (listed at the end under "ATTENTION").
set -uo pipefail
APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BK=/var/backups/worldcup-universe; LOG=/var/log/worldcup-universe/update.log
issues=()
warn() { issues+=("$1"); }
row() { printf '  %-22s %s\n' "$1" "$2"; }
age_h() { echo $(( ( $(date +%s) - $(stat -c %Y "$1") ) / 3600 )); }
env_get() { local l; l="$(grep -E "^[[:space:]]*$1=" "$APP_DIR/.env" 2>/dev/null | tail -n1)"; l="${l#*=}"; l="${l%$'\r'}"; l="${l#\"}"; printf '%s' "${l%\"}"; }
[[ $EUID -eq 0 ]] || { echo "Run with sudo (logs, backups and certificates are root-only)." >&2; exit 2; }

echo "WorldCup Universe status - $(date '+%F %T %Z') - $(hostname)"
echo "Code"
owner="$(stat -c %U "$APP_DIR")"
sudo -u "$owner" timeout 20 git -C "$APP_DIR" fetch -q origin 2>/dev/null || warn "git fetch from GitHub failed (network?)"
head="$(git -C "$APP_DIR" rev-parse --short HEAD)"; branch="$(git -C "$APP_DIR" symbolic-ref -q --short HEAD || echo detached)"
remote="$(git -C "$APP_DIR" rev-parse --short "origin/$branch" 2>/dev/null || echo '?')"
row "deployed" "$head on $branch ($(git -C "$APP_DIR" log -1 --format='%cd, %s' --date=format:'%F %R' | cut -c1-70))"
row "origin/$branch" "$remote$([[ "$head" == "$remote" ]] && echo '  (up to date)' || echo '  (newer commits wait for the next wcu-update run)')"
dirty="$(git -C "$APP_DIR" status --porcelain --untracked-files=no | wc -l)"; [[ $dirty -eq 0 ]] || warn "$dirty tracked file(s) modified on the server: auto-update will refuse to run"
last="$(grep -E 'UPDATED|FAILED|REFUSED|ROLLED BACK|up to date' "$LOG" 2>/dev/null | tail -n1)"
row "last update event" "${last:-none yet}"
if [[ -f /var/lib/worldcup-universe/update-failed ]]; then warn "commit $(cut -c1-7 /var/lib/worldcup-universe/update-failed) is marked failed: fix forward or run update-production.sh --clear-failure"; fi

echo "Services"
for s in nginx php8.1-fpm mariadb; do st="$(systemctl is-active "$s")"; row "$s" "$st"; [[ "$st" == active ]] || warn "$s is $st"; done
[[ -f "$APP_DIR/storage/runtime/maintenance.flag" ]] && warn "maintenance mode is ON since $(cat "$APP_DIR/storage/runtime/maintenance.flag") (restore running or failed?)"

echo "Timers (last result)"
for t in wcu-update wcu-news-sync wcu-backup wcu-offsite-backup certbot; do
  en="$(systemctl is-enabled "$t.timer" 2>/dev/null || echo missing)"
  res="$(systemctl show -p Result --value "$t.service" 2>/dev/null)"; when="$(systemctl show -p ExecMainExitTimestamp --value "$t.service" 2>/dev/null)"
  nxt="$(systemctl show -p NextElapseUSecRealtime --value "$t.timer" 2>/dev/null)"
  row "$t" "$en; last ${when:-never} -> ${res:-n/a}; next ${nxt:-n/a}"
  if [[ "$t" != wcu-news-sync && "$en" == enabled && -n "$when" && "$res" != success ]]; then warn "$t last run: $res (journalctl -u $t -n 50)"; fi
done

echo "Website"
url="$(env_get APP_URL)"; host="${url#*://}"; host="${host%%/*}"
code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$url/")"; row "$url/" "HTTP $code"; [[ "$code" == 200 ]] || warn "home page answers HTTP $code"
cert="/etc/letsencrypt/live/$host/cert.pem"
if [[ -f "$cert" ]]; then
  end="$(openssl x509 -enddate -noout -in "$cert" | cut -d= -f2)"; days=$(( ( $(date -d "$end" +%s) - $(date +%s) ) / 86400 ))
  row "certificate" "valid until $end ($days days)"; [[ $days -ge 14 ]] || warn "certificate expires in $days days (certbot renew --dry-run)"
else row "certificate" "none (HTTP only)"; fi

echo "Backups"
db="$(ls -t "$BK"/db-*.sql.gz 2>/dev/null | head -n1)"
if [[ -n "$db" ]]; then h=$(age_h "$db"); row "nightly (server)" "$(basename "$db") $(du -h "$db" | cut -f1), ${h}h ago"; [[ $h -le 36 ]] || warn "newest local backup is ${h}h old"; else warn "no local backup found in $BK"; fi
vid="$(ls -t "$BK"/videos-*.tar.gz 2>/dev/null | head -n1)"; [[ -n "$vid" ]] && row "video shelf archive" "$(basename "$vid") $(du -h "$vid" | cut -f1)"
row "backup disk use" "$(du -sh "$BK" 2>/dev/null | cut -f1)"
if [[ -f /etc/worldcup-universe/offsite.conf ]]; then
  off="$(cat /var/lib/worldcup-universe/offsite-last.json 2>/dev/null)"
  row "off-site (GitHub)" "${off:-never run}"
  if [[ -z "$off" || "$off" != *'"result":"ok"'* ]]; then warn "last off-site backup did not succeed"
  else t="$(sed -E 's/.*"time":"([^"]+)".*/\1/' <<<"$off")"; h=$(( ( $(date +%s) - $(date -d "$t" +%s) ) / 3600 )); [[ $h -le 36 ]] || warn "last off-site backup is ${h}h old"; fi
else row "off-site (GitHub)" "not configured"; fi

echo "Resources"
row "disk /" "$(df -h / | awk 'NR==2{print $3" used of "$2" ("$5")"}')"; use="$(df / | awk 'NR==2{gsub("%","",$5); print $5}')"; [[ $use -lt 85 ]] || warn "disk ${use}% full"
row "memory" "$(free -m | awk '/Mem:/{print $3" MB used, "$7" MB available"}'); swap $(free -m | awk '/Swap:/{print $3"/"$2" MB"}')"

echo
if (( ${#issues[@]} )); then echo "ATTENTION:"; for i in "${issues[@]}"; do echo "  - $i"; done; exit 1; fi
echo "All checks passed."
