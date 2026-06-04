#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${SOURCE_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
APP_DIR="${APP_DIR:-/var/www/indicoerp-staging}"
APP_URL="${APP_URL:-http://staging.indicoerp.local}"
DOMAIN="${DOMAIN:-staging.indicoerp.local}"

BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"
APP_ENV_FILE="${APP_ENV_FILE:-${APP_DIR}/shared/.env}"
ARCHIVE_PATH="${ARCHIVE_PATH:-/tmp/indicoerp-staging-release.tar.gz}"

DB_CONTAINER_NAME="${DB_CONTAINER_NAME:-indicoerp_staging_mysql}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"
DB_NAME="${DB_NAME:-indicoerp_staging}"
DB_USER="${DB_USER:-indicoerp_staging_user}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_HOST_PORT="${DB_HOST_PORT:-3308}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-${DB_HOST_PORT}}"

PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm.service}"
QUEUE_SERVICE_NAME="${QUEUE_SERVICE_NAME:-indicoerp-staging-queue.service}"
SCHEDULER_SERVICE_NAME="${SCHEDULER_SERVICE_NAME:-indicoerp-staging-scheduler.service}"
RUN_CERTBOT="${RUN_CERTBOT:-0}"
ENABLE_REDIS_RUNTIME="${ENABLE_REDIS_RUNTIME:-1}"
REDIS_DB_VALUE="${REDIS_DB_VALUE:-10}"
REDIS_CACHE_DB_VALUE="${REDIS_CACHE_DB_VALUE:-11}"

if [ -z "$DB_ROOT_PASSWORD" ] || [ -z "$DB_PASSWORD" ]; then
  echo "Defina DB_ROOT_PASSWORD e DB_PASSWORD."
  exit 1
fi

mkdir -p "$APP_DIR/releases" "$APP_DIR/shared" "$BACKUP_DIR"

if [ ! -f "$APP_ENV_FILE" ]; then
  cat > "$APP_ENV_FILE" <<EOF
APP_NAME=IndicoERP Staging
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL}

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=${REDIS_DB_VALUE}
REDIS_CACHE_DB=${REDIS_CACHE_DB_VALUE}
EOF
  chmod 600 "$APP_ENV_FILE"
fi

echo "==> Staging MySQL container"
DB_CONTAINER_NAME="$DB_CONTAINER_NAME" \
DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
DB_NAME="$DB_NAME" \
DB_USER="$DB_USER" \
DB_PASSWORD="$DB_PASSWORD" \
DB_HOST_PORT="$DB_HOST_PORT" \
DB_VOLUME_DIR="/opt/indicoerp-staging/mysql-data" \
bash "$SOURCE_DIR/deploy/scripts/02_setup_mysql_container.sh"

echo "==> Packaging staging release"
tmp_archive="$(mktemp /tmp/indicoerp-staging-release.XXXXXX.tar.gz)"
trap 'rm -f "$tmp_archive"' EXIT
tar -czf "$tmp_archive" \
  --exclude=".git" \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude=".env" \
  --exclude="storage/logs/*" \
  --exclude="storage/framework/cache/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  -C "$SOURCE_DIR" .

cp "$tmp_archive" "$ARCHIVE_PATH"

echo "==> Deploy release to staging"
APP_DIR="$APP_DIR" \
APP_ENV_FILE="$APP_ENV_FILE" \
QUEUE_SERVICE="$QUEUE_SERVICE_NAME" \
SCHEDULER_SERVICE="$SCHEDULER_SERVICE_NAME" \
PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
bash "$SOURCE_DIR/deploy/scripts/03_deploy_release.sh" "$ARCHIVE_PATH"

echo "==> Configure staging runtime"
DOMAIN="$DOMAIN" \
APP_DIR="$APP_DIR" \
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.2-fpm.sock}" \
QUEUE_SERVICE_NAME="$QUEUE_SERVICE_NAME" \
SCHEDULER_SERVICE_NAME="$SCHEDULER_SERVICE_NAME" \
RUN_CERTBOT="$RUN_CERTBOT" \
bash "$SOURCE_DIR/deploy/scripts/04_configure_runtime.sh"

if [ "$ENABLE_REDIS_RUNTIME" = "1" ]; then
  echo "==> Enable staging Redis runtime"
  APP_DIR="$APP_DIR" \
  ENV_FILE="$APP_ENV_FILE" \
  PHP_BIN="${PHP_BIN:-php}" \
  PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
  QUEUE_SERVICE="$QUEUE_SERVICE_NAME" \
  SCHEDULER_SERVICE="$SCHEDULER_SERVICE_NAME" \
  REDIS_DB_VALUE="$REDIS_DB_VALUE" \
  REDIS_CACHE_DB_VALUE="$REDIS_CACHE_DB_VALUE" \
  bash "$SOURCE_DIR/deploy/scripts/15_enable_redis_runtime_indicoerp.sh" enable
fi

echo "OK: staging setup concluído."
echo "APP_DIR: $APP_DIR"
echo "APP_ENV_FILE: $APP_ENV_FILE"
echo "DOMAIN: $DOMAIN"
echo "DB container: $DB_CONTAINER_NAME"
echo "Queue service: $QUEUE_SERVICE_NAME"
echo "Scheduler service: $SCHEDULER_SERVICE_NAME"
