#!/usr/bin/env bash
# WorldCup Universe: stop every writer before data is swapped (scripts/restore.sh), and let it back in afterwards.
#   sudo bash scripts/maintenance.sh on       # Nginx answers 503 for every PHP route, timers stopped, requests drained
#   sudo bash scripts/maintenance.sh off      # site and timers back
#   sudo bash scripts/maintenance.sh status
# The switch is storage/runtime/maintenance.flag, which deploy/nginx/app.conf.template checks before PHP-FPM.
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
FLAG="$APP_DIR/storage/runtime/maintenance.flag"
SOCK=/run/php/worldcup-universe-fpm.sock
TIMERS=(wcu-news-sync.timer wcu-backup.timer)
DRAIN_SECONDS="${DRAIN_SECONDS:-60}"

busy() { # PHP requests still running = connections Nginx holds open on the pool socket
  command -v ss >/dev/null || { echo 0; return; }
  ss -xH state connected 2>/dev/null | grep -c "$SOCK" || true
}

case "${1:-status}" in
  on)
    mkdir -p "$(dirname "$FLAG")"
    date '+%F %T' > "$FLAG"; chmod 644 "$FLAG"
    echo "Maintenance on: dynamic routes now answer 503."
    systemctl stop "${TIMERS[@]}" 2>/dev/null || true
    for unit in wcu-news-sync.service wcu-backup.service; do
      waited=0
      while systemctl is-active --quiet "$unit" 2>/dev/null && (( waited < 900 )); do sleep 2; waited=$((waited + 2)); done
    done
    waited=0
    while (( $(busy) > 0 && waited < DRAIN_SECONDS )); do sleep 1; waited=$((waited + 1)); done
    echo "Timers stopped; $(busy) PHP request(s) still running after ${waited}s."
    ;;
  off)
    rm -f "$FLAG"
    systemctl start "${TIMERS[@]}" 2>/dev/null || true
    echo "Maintenance off: site and timers back."
    ;;
  status)
    if [[ -f "$FLAG" ]]; then echo "on since $(cat "$FLAG")"; else echo "off"; fi
    ;;
  *) echo "Usage: $0 on|off|status" >&2; exit 2;;
esac
