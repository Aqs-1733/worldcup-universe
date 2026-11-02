#!/usr/bin/env bash
# WorldCup Universe: idempotent one-command deployment on Ubuntu 22.04/24.04 (Oracle Cloud Always Free).
# Installs/configures Nginx + PHP-FPM + MySQL, creates .env and the database account on first run, runs the Yii2
# installer and migrations, renders server config from deploy/, issues a Let's Encrypt certificate and enables the
# news-sync and backup timers. Re-running it after `git pull` updates dependencies, schema and config in place.
#
#   sudo bash scripts/deploy.sh --install-packages --domain example.com --email you@example.com --open-firewall
#   sudo bash scripts/deploy.sh                      # later updates (backup is taken before migrations)
#
# Options: --install-packages  apt install nginx, mysql-server, php-fpm and extensions, composer, certbot
#          --domain NAME       public host name (needed for HTTPS); defaults to the server's address, HTTP only
#          --email ADDRESS     Let's Encrypt account e-mail; together with --domain this requests a certificate
#                              (certbot is run with --agree-tos: passing --email means you accept the LE terms)
#          --skip-tls          never request a certificate
#          --open-firewall     add iptables ACCEPT rules for 80/443 (Oracle Ubuntu images reject them by default)
#          --skip-backup       do not take the pre-migration backup
# Nothing here is reachable from the web: installation runs only from this root shell.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"
DOMAIN=""; EMAIL=""; INSTALL_PACKAGES=0; SKIP_TLS=0; OPEN_FIREWALL=0; SKIP_BACKUP=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2;;
    --email) EMAIL="$2"; shift 2;;
    --install-packages) INSTALL_PACKAGES=1; shift;;
    --skip-tls) SKIP_TLS=1; shift;;
    --open-firewall) OPEN_FIREWALL=1; shift;;
    --skip-backup) SKIP_BACKUP=1; shift;;
    -h|--help) sed -n '2,20p' "$0"; exit 0;;
    *) echo "Unknown option: $1" >&2; exit 2;;
  esac
done

step() { printf '\n==> %s\n' "$*"; }
die() { echo "ERROR: $*" >&2; exit 1; }
[[ $EUID -eq 0 ]] || die "run with sudo."
[[ -f "$APP_DIR/yii" && -d "$APP_DIR/public" ]] || die "$APP_DIR is not the worldcup-universe checkout."
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]*$ ]] || die "invalid --domain."
OWNER="$(stat -c %U "$APP_DIR")"; [[ "$OWNER" != "root" ]] || OWNER="${SUDO_USER:-root}"

env_get() {
  local line
  line="$(grep -E "^[[:space:]]*$1=" "$ENV_FILE" 2>/dev/null | tail -n 1 || true)"
  line="${line#*=}"; line="${line%$'\r'}"
  line="${line#\"}"; line="${line%\"}"; line="${line#\'}"; line="${line%\'}"
  printf '%s' "$line"
}
env_set() { # replace or append KEY=value without ever printing the value
  local key="$1" value="$2" tmp
  tmp="$(mktemp)"
  awk -v k="$key" -v v="$value" 'BEGIN{done=0} $0 ~ "^"k"=" {print k"="v; done=1; next} {print} END{if(!done) print k"="v}' "$ENV_FILE" > "$tmp"
  cat "$tmp" > "$ENV_FILE"; rm -f "$tmp"
}
as_www() { sudo -u www-data -H "$@"; }

# ---------------------------------------------------------------------------------------------------------------
if [[ $INSTALL_PACKAGES -eq 1 ]]; then
  step "Installing system packages"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -q
  apt-get install -y -q nginx mariadb-server mariadb-client php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip \
    php-intl unzip git composer certbot curl
fi
command -v php >/dev/null || die "PHP missing; re-run with --install-packages."
command -v nginx >/dev/null || die "Nginx missing; re-run with --install-packages."
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION,"8.1.0",">=")?0:1);' || die "PHP 8.1+ required (found $PHP_VERSION)."
PHP_MODULES="$(php -m)"
for ext in pdo_mysql mbstring curl SimpleXML fileinfo; do grep -qix "$ext" <<<"$PHP_MODULES" || die "PHP extension $ext missing."; done
FPM_SERVICE="php${PHP_VERSION}-fpm"; FPM_POOL_DIR="/etc/php/${PHP_VERSION}/fpm/pool.d"
[[ -d "$FPM_POOL_DIR" ]] || die "$FPM_POOL_DIR not found; install php${PHP_VERSION}-fpm."

# www-data must be able to reach the code (Ubuntu home directories are 0750).
sudo -u www-data test -r "$APP_DIR/public/index.php" || die "www-data cannot read $APP_DIR; deploy under /var/www (see docs/deployment-oracle-ubuntu.md)."

# ---------------------------------------------------------------------------------------------------------------
step "Environment file"
FIRST_INSTALL=0
if [[ ! -f "$ENV_FILE" ]]; then
  FIRST_INSTALL=1
  cp "$APP_DIR/deploy/env.production.example" "$ENV_FILE"
  env_set DB_PASSWORD "$(openssl rand -hex 20)"
  env_set ADMIN_PASSWORD "$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)"
  echo "Created .env with generated DB_PASSWORD and ADMIN_PASSWORD (read them with: sudo grep PASSWORD $ENV_FILE)."
fi
[[ "$(env_get YII_COOKIE_VALIDATION_KEY | wc -c)" -gt 32 ]] || env_set YII_COOKIE_VALIDATION_KEY "$(openssl rand -hex 32)"
[[ "$(env_get APP_ENV)" == "production" ]] || env_set APP_ENV production
HOST_NAME="$DOMAIN"
if [[ -z "$HOST_NAME" ]]; then
  current="$(env_get APP_URL)"; current="${current#*://}"; current="${current%%/*}"
  [[ -n "$current" && "$current" != "example.com" ]] && HOST_NAME="$current"
fi
[[ -n "$HOST_NAME" ]] || HOST_NAME="$(curl -fsS --max-time 5 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}')"
chown "$OWNER":www-data "$ENV_FILE"; chmod 640 "$ENV_FILE"

# ---------------------------------------------------------------------------------------------------------------
step "MySQL database and application account"
DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"; DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"; DB_PASSWORD="$(env_get DB_PASSWORD)"
[[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ && "$DB_USERNAME" =~ ^[A-Za-z0-9_]+$ ]] || die "DB_DATABASE / DB_USERNAME must be [A-Za-z0-9_]."
if [[ "$DB_HOST" == "127.0.0.1" || "$DB_HOST" == "localhost" ]]; then
  systemctl enable --now mariadb >/dev/null 2>&1 || systemctl enable --now mysql >/dev/null 2>&1 || true
  # data/install.sql declares UNIQUE keys on VARCHAR(800/900) utf8mb4 columns. MariaDB 10.4+ enforces them with a
  # hash; MySQL 8 rejects them (3072-byte key limit), so the schema installs only on MariaDB.
  server_version="$(mysql --protocol=socket -uroot -N -e 'SELECT VERSION()')"
  [[ "$server_version" == *MariaDB* ]] || die "database server is '$server_version'; install mariadb-server (MySQL 8 cannot create data/install.sql's long unique keys)."
  [[ -n "$DB_PASSWORD" ]] || { DB_PASSWORD="$(openssl rand -hex 20)"; env_set DB_PASSWORD "$DB_PASSWORD"; }
  sql_pw="${DB_PASSWORD//\'/\'\'}"
  # Ubuntu's root@localhost uses auth_socket, so this works from the root shell without a password.
  mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'localhost' IDENTIFIED BY '$sql_pw';
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$sql_pw';
ALTER USER '$DB_USERNAME'@'localhost' IDENTIFIED BY '$sql_pw';
ALTER USER '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$sql_pw';
GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
else
  echo "DB_HOST=$DB_HOST is remote; database and account must already exist."
fi
# Whether this database was already installed decides the pre-migration backup and the one-time metadata seed.
DB_CNF="$(mktemp)"; trap 'rm -f "$DB_CNF"' EXIT
escaped="${DB_PASSWORD//\\/\\\\}"; escaped="${escaped//\"/\\\"}"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\n' "$DB_HOST" "${DB_PORT:-3306}" "$DB_USERNAME" "$escaped" > "$DB_CNF"
HAS_SCHEMA="$(mysql --defaults-extra-file="$DB_CNF" -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_DATABASE' AND TABLE_NAME='migration'")" \
  || die "cannot connect to MySQL as $DB_USERNAME; check DB_* in .env."

# ---------------------------------------------------------------------------------------------------------------
step "Writable directories (code stays read-only for www-data)"
for d in storage/runtime storage/logs storage/cache storage/generated storage/source_snapshots storage/imports storage/uploads \
         public/uploads data/team/uploads data/personal/uploads public/assets/media/videos; do
  mkdir -p "$APP_DIR/$d"
done
chown -R "$OWNER":www-data "$APP_DIR/storage" "$APP_DIR/public/uploads" "$APP_DIR/data/team" "$APP_DIR/data/personal" "$APP_DIR/public/assets/media/videos"
for d in storage public/uploads data/team data/personal public/assets/media/videos; do
  find "$APP_DIR/$d" -type d -exec chmod 2775 {} +
  find "$APP_DIR/$d" -type f -exec chmod 664 {} +
done
chmod 755 "$APP_DIR/yii"

# ---------------------------------------------------------------------------------------------------------------
if [[ "$HAS_SCHEMA" == "1" && $SKIP_BACKUP -eq 0 ]]; then
  step "Pre-deployment backup"
  BACKUP_DIR=/var/backups/worldcup-universe/pre-deploy BACKUP_KEEP_DAYS=30 bash "$APP_DIR/scripts/backup.sh"
fi

step "Composer dependencies"
mkdir -p "$APP_DIR/vendor"; chown "$OWNER" "$APP_DIR/vendor"
if [[ "$OWNER" == "root" ]]; then export COMPOSER_ALLOW_SUPERUSER=1; RUN_OWNER=(); else RUN_OWNER=(sudo -u "$OWNER" -H); fi
(cd "$APP_DIR" && "${RUN_OWNER[@]}" composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress)

step "Yii2 install / migrations (idempotent)"
as_www php "$APP_DIR/yii" app/install
as_www php "$APP_DIR/yii" migrate/up --interactive=0
# Display metadata (team members, venue labels) is seeded once; re-running it would overwrite admin edits.
if [[ "$HAS_SCHEMA" != "1" ]]; then as_www php "$APP_DIR/yii" release/metadata || true; fi
as_www php "$APP_DIR/yii" cache/flush-all --interactive=0 >/dev/null || true
as_www php "$APP_DIR/yii" media/check

# ---------------------------------------------------------------------------------------------------------------
step "PHP-FPM pool"
MAX_MB=0
for k in UPLOAD_MAX_IMAGE_MB UPLOAD_MAX_VIDEO_MB UPLOAD_MAX_DOCUMENT_MB; do v="$(env_get $k)"; v="${v:-0}"; [[ "$v" =~ ^[0-9]+$ ]] || v=0; (( v > MAX_MB )) && MAX_MB=$v; done
(( MAX_MB > 0 )) || MAX_MB=200
MEM_MB="$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)"
MAX_CHILDREN=$(( MEM_MB < 2048 ? 5 : (MEM_MB < 8192 ? 10 : 20) ))
render() { sed -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__SERVER_NAME__#$HOST_NAME#g" -e "s#__CERT_NAME__#$HOST_NAME#g" \
               -e "s#__PHP_VERSION__#$PHP_VERSION#g" -e "s#__UPLOAD_MB__#$MAX_MB#g" -e "s#__POST_MB__#$((MAX_MB + 8))#g" \
               -e "s#__BODY_MB__#$((MAX_MB + 8))#g" -e "s#__MAX_CHILDREN__#$MAX_CHILDREN#g" "$1"; }
render "$APP_DIR/deploy/php-fpm/pool.conf.template" > "$FPM_POOL_DIR/worldcup-universe.conf"
"php-fpm$PHP_VERSION" -t
systemctl enable "$FPM_SERVICE" >/dev/null
systemctl restart "$FPM_SERVICE"   # restart also clears OPcache for the new code

# ---------------------------------------------------------------------------------------------------------------
step "Nginx site"
mkdir -p /var/www/letsencrypt
install -m 644 "$APP_DIR/deploy/nginx/headers.conf" /etc/nginx/snippets/worldcup-universe-headers.conf
render "$APP_DIR/deploy/nginx/app.conf.template" > /etc/nginx/snippets/worldcup-universe-app.conf
SITE=/etc/nginx/sites-available/worldcup-universe
CERT_DIR="/etc/letsencrypt/live/$HOST_NAME"
if [[ -f "$CERT_DIR/fullchain.pem" ]]; then render "$APP_DIR/deploy/nginx/site-https.conf.template" > "$SITE"
else render "$APP_DIR/deploy/nginx/site-http.conf.template" > "$SITE"; fi
ln -sfn "$SITE" /etc/nginx/sites-enabled/worldcup-universe
# The stock site also claims default_server on :80; only its symlink is removed, the file stays.
[[ -L /etc/nginx/sites-enabled/default ]] && rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable nginx >/dev/null; systemctl reload nginx || systemctl restart nginx

if [[ $OPEN_FIREWALL -eq 1 ]] && command -v iptables >/dev/null; then
  step "Host firewall (iptables) for 80/443"
  for port in 80 443; do
    iptables -C INPUT -p tcp --dport "$port" -m conntrack --ctstate NEW -j ACCEPT 2>/dev/null \
      || iptables -I INPUT -p tcp --dport "$port" -m conntrack --ctstate NEW -j ACCEPT
  done
  command -v netfilter-persistent >/dev/null && netfilter-persistent save >/dev/null || true
  echo "Also allow TCP 80 and 443 in the VCN Security List / NSG in the Oracle Cloud console."
fi

# ---------------------------------------------------------------------------------------------------------------
if [[ $SKIP_TLS -eq 0 && -n "$DOMAIN" && -n "$EMAIL" && ! -f "$CERT_DIR/fullchain.pem" ]]; then
  step "Let's Encrypt certificate for $DOMAIN"
  command -v certbot >/dev/null || die "certbot missing; re-run with --install-packages."
  certbot certonly --webroot -w /var/www/letsencrypt -d "$DOMAIN" --email "$EMAIL" --agree-tos -n --keep-until-expiring
  mkdir -p /etc/letsencrypt/renewal-hooks/deploy
  printf '#!/bin/sh\nsystemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
  chmod 755 /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
  render "$APP_DIR/deploy/nginx/site-https.conf.template" > "$SITE"
  nginx -t && systemctl reload nginx
fi
if [[ -f "$CERT_DIR/fullchain.pem" ]]; then env_set APP_URL "https://$HOST_NAME"
elif [[ "$(env_get APP_URL)" == "https://example.com" || -z "$(env_get APP_URL)" ]]; then env_set APP_URL "http://$HOST_NAME"; fi
chown "$OWNER":www-data "$ENV_FILE"; chmod 640 "$ENV_FILE"
systemctl reload "$FPM_SERVICE"

# ---------------------------------------------------------------------------------------------------------------
step "systemd timers (news sync every 10 min, nightly backup)"
for unit in wcu-news-sync.service wcu-backup.service; do render "$APP_DIR/deploy/systemd/$unit.template" > "/etc/systemd/system/$unit"; done
install -m 644 "$APP_DIR/deploy/systemd/wcu-news-sync.timer" "$APP_DIR/deploy/systemd/wcu-backup.timer" /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now wcu-news-sync.timer wcu-backup.timer >/dev/null

# ---------------------------------------------------------------------------------------------------------------
step "Smoke test"
SCHEME=http; [[ -f "$CERT_DIR/fullchain.pem" ]] && SCHEME=https
code="$(curl -k -s -o /dev/null -w '%{http_code}' --resolve "$HOST_NAME:443:127.0.0.1" --resolve "$HOST_NAME:80:127.0.0.1" "$SCHEME://$HOST_NAME/")"
env_code="$(curl -k -s -o /dev/null -w '%{http_code}' --resolve "$HOST_NAME:443:127.0.0.1" --resolve "$HOST_NAME:80:127.0.0.1" "$SCHEME://$HOST_NAME/.env")"
echo "GET /      -> $code (expect 200)"
echo "GET /.env  -> $env_code (expect 404)"
[[ "$code" == "200" && "$env_code" == "404" ]] || die "smoke test failed; see storage/logs and /var/log/nginx/error.log."
[[ $FIRST_INSTALL -eq 1 ]] && echo "Administrator '$(env_get ADMIN_USERNAME)' created; its first password is ADMIN_PASSWORD in $ENV_FILE."
echo "Deployed $SCHEME://$HOST_NAME/"
