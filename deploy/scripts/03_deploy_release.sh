#!/usr/bin/env bash
set -euo pipefail

ARCHIVE_PATH="${1:-/tmp/hrm-release.tar.gz}"
APP_DIR="${APP_DIR:-/var/www/hrm-saas}"
RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
APP_ENV_FILE="${APP_ENV_FILE:-${SHARED_DIR}/.env}"

if [ ! -f "$ARCHIVE_PATH" ]; then
  echo "Arquivo não encontrado: $ARCHIVE_PATH"
  exit 1
fi

TIMESTAMP="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"

mkdir -p "$RELEASE_DIR" "$SHARED_DIR/storage"
tar -xzf "$ARCHIVE_PATH" -C "$RELEASE_DIR"

cd "$RELEASE_DIR"

ln -sfn "$APP_ENV_FILE" .env
rm -rf storage
ln -sfn "${SHARED_DIR}/storage" storage

mkdir -p bootstrap/cache "${SHARED_DIR}/storage/framework/cache" "${SHARED_DIR}/storage/framework/sessions" "${SHARED_DIR}/storage/framework/views" "${SHARED_DIR}/storage/logs"
chown -R www-data:www-data "$APP_DIR"
chmod -R ug+rwX "${SHARED_DIR}/storage" bootstrap/cache

"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [ -f package.json ]; then
  if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then
    npm ci
  else
    npm install --no-audit --no-fund
  fi
  npm run build
fi

"$PHP_BIN" artisan key:generate --force || true
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache || true
"$PHP_BIN" artisan view:cache

ln -sfn "$RELEASE_DIR" "$CURRENT_LINK"
chown -h www-data:www-data "$CURRENT_LINK"

systemctl restart "$PHP_FPM_SERVICE"
systemctl restart nginx
systemctl restart hrm-queue 2>/dev/null || true
systemctl restart hrm-scheduler 2>/dev/null || true

# manter apenas as últimas 5 releases
cd "$RELEASES_DIR"
ls -1dt */ | tail -n +6 | xargs -r rm -rf

echo "Deploy concluído: $RELEASE_DIR"
