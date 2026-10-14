#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/hzmahjong}"
DOMAIN="${DOMAIN:-hzmahjong.tech}"
REPO_URL="${REPO_URL:-https://github.com/Kadalzz/Mahjong.git}"
ENV_FILE="${ENV_FILE:-/root/hzmahjong.env}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-}"

if [ "$(id -u)" -ne 0 ]; then
    echo "Jalankan sebagai root."
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "File env tidak ditemukan di $ENV_FILE. Upload .env.production ke path itu dulu."
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
export COMPOSER_ALLOW_SUPERUSER=1

apt-get update
apt-get install -y nginx mysql-server git unzip curl ufw certbot python3-certbot-nginx \
    php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl \
    composer

if ! command -v node >/dev/null 2>&1; then
    apt-get install -y nodejs
fi

if ! command -v npm >/dev/null 2>&1; then
    apt-get install -y npm
fi

echo "Node: $(node -v), npm: $(npm -v)"

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
echo "PHP terpasang: ${PHP_VER}"

if ! git config --global --get-all safe.directory | grep -qx "$APP_DIR"; then
    git config --global --add safe.directory "$APP_DIR"
fi

if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" pull --ff-only
else
    mkdir -p "$(dirname "$APP_DIR")"
    git clone "$REPO_URL" "$APP_DIR"
fi

cp "$ENV_FILE" "$APP_DIR/.env"
chmod 600 "$APP_DIR/.env"

env_value() {
    grep -E "^$1=" "$APP_DIR/.env" | head -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"
DB_PASS="$(env_value DB_PASSWORD)"
BRIDGE_SECRET="$(env_value ESP32_BRIDGE_SECRET)"
WS_TOKEN="$(env_value ESP32_WS_TOKEN)"

if [ -z "$WS_TOKEN" ]; then
    echo "PERINGATAN: ESP32_WS_TOKEN kosong. Port 81 akan menerima koneksi Master tanpa token."
fi

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$BRIDGE_SECRET" ]; then
    echo "DB_DATABASE, DB_USERNAME, DB_PASSWORD, atau ESP32_BRIDGE_SECRET kosong di $ENV_FILE."
    exit 1
fi

systemctl enable --now mysql
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

(cd "$APP_DIR/esp32-bridge" && composer install --no-dev --optimize-autoloader --no-interaction)

cat > "$APP_DIR/esp32-bridge/config.php" <<PHP
<?php

return [
    'ws_port' => 81,
    'control_port' => 8090,
    'secret' => '${BRIDGE_SECRET}',
    'ws_token' => '${WS_TOKEN}',
];
PHP

chown -R www-data:www-data "$APP_DIR"
chmod 600 "$APP_DIR/.env" "$APP_DIR/esp32-bridge/config.php"

as_www() {
    runuser -u www-data -- "$@"
}

cd "$APP_DIR"
as_www php artisan migrate --force

if [ ! -f "$APP_DIR/storage/.seeded" ]; then
    as_www php artisan db:seed --force
    as_www touch "$APP_DIR/storage/.seeded"
fi

as_www php artisan storage:link || true
as_www php artisan config:cache
as_www php artisan route:cache
as_www php artisan view:cache

sed -e "s#__DOMAIN__#${DOMAIN}#g" -e "s#__APP_DIR__#${APP_DIR}#g" -e "s#__PHP_SOCK__#${PHP_SOCK}#g" \
    "$APP_DIR/deploy/nginx.conf.template" > /etc/nginx/sites-available/hzmahjong
ln -sf /etc/nginx/sites-available/hzmahjong /etc/nginx/sites-enabled/hzmahjong
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now "php${PHP_VER}-fpm"
systemctl reload nginx

for unit in hzmahjong-queue hzmahjong-bridge; do
    sed -e "s#__APP_DIR__#${APP_DIR}#g" "$APP_DIR/deploy/${unit}.service" > "/etc/systemd/system/${unit}.service"
done
systemctl daemon-reload
systemctl enable hzmahjong-queue hzmahjong-bridge
systemctl restart hzmahjong-queue hzmahjong-bridge

echo "* * * * * www-data cd ${APP_DIR} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/hzmahjong
chmod 644 /etc/cron.d/hzmahjong

ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 81/tcp
ufw --force enable

if [ -n "$CERTBOT_EMAIL" ]; then
    certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos -m "$CERTBOT_EMAIL" --redirect --expand
fi

echo
echo "Selesai."
systemctl --no-pager --lines=0 status hzmahjong-queue hzmahjong-bridge | grep -E "●|Active" || true
echo "Cek: curl -I http://${DOMAIN}"
