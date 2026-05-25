#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BRANCH="${BRANCH:-main}"
APP_ENV_VALUE="${APP_ENV_VALUE:-production}"
APP_URL_VALUE="${APP_URL_VALUE:-https://indicoerp.com}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_NAME="${DB_NAME:-indicoerp}"
DB_USER="${DB_USER:-indicoerp_user}"
DB_PASS="${DB_PASS:-}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm}"
QUEUE_SERVICE="${QUEUE_SERVICE:-indicoerp-queue}"
SCHEDULER_SERVICE="${SCHEDULER_SERVICE:-indicoerp-scheduler}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"

if [ -z "$DB_PASS" ]; then
  echo "DB_PASS nao definido."
  exit 1
fi

cd "$APP_DIR"
git config --global --add safe.directory "$APP_DIR"

APP_DOWN=0
cleanup() {
  if [ "$APP_DOWN" -eq 1 ]; then
    php artisan up || true
  fi
}
trap cleanup EXIT

escape_sed_replacement() {
  printf '%s' "$1" | sed -e 's/[&]/\\&/g'
}

upsert_env() {
  local key="$1"
  local value="$2"
  local escaped
  escaped="$(escape_sed_replacement "$value")"

  if grep -q "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${escaped}#g" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

list_service_units() {
  if ! command -v systemctl >/dev/null 2>&1; then
    return 0
  fi

  systemctl list-unit-files --type=service --no-legend 2>/dev/null | awk '{print $1}'
}

unit_exists() {
  local unit="$1"
  [ -n "$unit" ] || return 1

  list_service_units | grep -Fxq "$unit"
}

first_matching_unit() {
  local regex="$1"
  list_service_units | grep -E "$regex" | head -n 1 || true
}

resolve_service_unit() {
  local preferred="$1"
  local regex="$2"
  shift 2
  local candidates=("$@")
  local candidate

  if unit_exists "$preferred"; then
    echo "$preferred"
    return 0
  fi

  for candidate in "${candidates[@]}"; do
    if unit_exists "$candidate"; then
      echo "$candidate"
      return 0
    fi
  done

  first_matching_unit "$regex"
}

restart_if_available() {
  local unit="$1"
  if [ -z "$unit" ]; then
    return 0
  fi

  if unit_exists "$unit"; then
    systemctl restart "$unit"
    echo "Restarted: ${unit}"
    return 0
  fi

  echo "WARN: unit not found, skipping restart: ${unit}"
  return 0
}

PHP_FPM_SERVICE="$(resolve_service_unit "$PHP_FPM_SERVICE" '^php[0-9]+\.[0-9]+-fpm\.service$')"
QUEUE_SERVICE="$(resolve_service_unit "$QUEUE_SERVICE" '(^|-)queue\.service$' 'indicoerp-queue.service' 'hrm-queue.service')"
SCHEDULER_SERVICE="$(resolve_service_unit "$SCHEDULER_SERVICE" '(^|-)scheduler\.service$' 'indicoerp-scheduler.service' 'hrm-scheduler.service')"

if [ -n "$PHP_FPM_SERVICE" ]; then
  echo "PHP_FPM_SERVICE resolved: $PHP_FPM_SERVICE"
else
  echo "WARN: no php-fpm unit found by auto-detection."
fi

if [ -n "$QUEUE_SERVICE" ]; then
  echo "QUEUE_SERVICE resolved: $QUEUE_SERVICE"
else
  echo "WARN: no queue service unit found by auto-detection."
fi

if [ -n "$SCHEDULER_SERVICE" ]; then
  echo "SCHEDULER_SERVICE resolved: $SCHEDULER_SERVICE"
else
  echo "WARN: no scheduler service unit found by auto-detection."
fi

echo "==> Pre-check: git status"
git status --short || true

echo "==> Backup"
mkdir -p "$BACKUP_DIR"
DB_BACKUP_FILE="${BACKUP_DIR}/db_$(date +%F_%H%M%S).sql.gz"
APP_BACKUP_FILE="${BACKUP_DIR}/app_storage_env_$(date +%F_%H%M%S).tar.gz"

MYSQL_PWD="$DB_PASS" mysqldump --no-tablespaces -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$DB_NAME" \
  | gzip > "$DB_BACKUP_FILE"
tar -czf "$APP_BACKUP_FILE" storage .env

echo "==> Maintenance mode"
php artisan down --retry=60
APP_DOWN=1

echo "==> Git update"
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Sync .env"
upsert_env APP_ENV "$APP_ENV_VALUE"
upsert_env APP_URL "$APP_URL_VALUE"
upsert_env DB_HOST "$DB_HOST"
upsert_env DB_PORT "$DB_PORT"
upsert_env DB_DATABASE "$DB_NAME"
upsert_env DB_USERNAME "$DB_USER"
upsert_env DB_PASSWORD "$DB_PASS"

echo "==> Install dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [ -f package.json ]; then
  if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then
    npm ci
  else
    npm install --no-audit --no-fund
  fi
  npm run build
fi

echo "==> Laravel optimize clear"
php artisan optimize:clear

echo "==> Run migrations"
php artisan migrate --force --no-interaction
php artisan sce:setup --no-interaction || true

echo "==> Cache rebuild"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restart services"
restart_if_available "$PHP_FPM_SERVICE"
restart_if_available "nginx.service"
restart_if_available "$QUEUE_SERVICE"
restart_if_available "$SCHEDULER_SERVICE"
php artisan queue:restart

echo "==> Application up"
php artisan up
APP_DOWN=0

echo "==> Health checks"
curl -I "$APP_URL_VALUE"
php artisan migrate:status | tail -n 15
tail -n 80 storage/logs/laravel.log

echo "Deploy concluido."
echo "DB backup: $DB_BACKUP_FILE"
echo "APP backup: $APP_BACKUP_FILE"
