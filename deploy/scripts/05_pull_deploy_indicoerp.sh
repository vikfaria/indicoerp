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
OPS_DIR="${OPS_DIR:-${BACKUP_DIR}/ops}"
PRE_DEPLOY_BACKUP_SCRIPT="${PRE_DEPLOY_BACKUP_SCRIPT:-$APP_DIR/deploy/scripts/17_pre_deploy_backup_indicoerp.sh}"
PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-$BACKUP_DIR/pre_deploy_backup_latest.env}"
PRE_DEPLOY_BACKUP_LOG_FILE="${PRE_DEPLOY_BACKUP_LOG_FILE:-$BACKUP_DIR/pre_deploy_backup_latest.log}"
POST_DEPLOY_HEALTHCHECK_SCRIPT="${POST_DEPLOY_HEALTHCHECK_SCRIPT:-$APP_DIR/deploy/scripts/19_post_deploy_healthcheck_indicoerp.sh}"
POST_DEPLOY_HEALTHCHECK_ENV_FILE="${POST_DEPLOY_HEALTHCHECK_ENV_FILE:-$OPS_DIR/post_deploy_healthcheck_latest.env}"
POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE="${POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE:-$OPS_DIR/post_deploy_healthcheck_latest.log}"
POST_DEPLOY_SMOKE_SCRIPT="${POST_DEPLOY_SMOKE_SCRIPT:-$APP_DIR/deploy/scripts/20_post_deploy_smoke_indicoerp.sh}"
POST_DEPLOY_SMOKE_ENV_FILE="${POST_DEPLOY_SMOKE_ENV_FILE:-$OPS_DIR/post_deploy_smoke_latest.env}"
POST_DEPLOY_SMOKE_LOG_FILE="${POST_DEPLOY_SMOKE_LOG_FILE:-$OPS_DIR/post_deploy_smoke_latest.log}"
POST_DEPLOY_K6_MATRIX_SCRIPT="${POST_DEPLOY_K6_MATRIX_SCRIPT:-$APP_DIR/deploy/scripts/21_post_deploy_k6_matrix_indicoerp.sh}"
POST_DEPLOY_K6_MATRIX_ENV_FILE="${POST_DEPLOY_K6_MATRIX_ENV_FILE:-$OPS_DIR/post_deploy_k6_matrix_latest.env}"
POST_DEPLOY_K6_MATRIX_LOG_FILE="${POST_DEPLOY_K6_MATRIX_LOG_FILE:-$OPS_DIR/post_deploy_k6_matrix_latest.log}"
POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE="${POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE:-$OPS_DIR/post_deploy_k6_matrix_latest.log}"
POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE="${POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE:-$OPS_DIR/k6_matrix_summary_latest.md}"

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

echo "==> Pre-deploy backup"
BACKUP_DIR="$BACKUP_DIR" \
DB_HOST="$DB_HOST" \
DB_PORT="$DB_PORT" \
DB_NAME="$DB_NAME" \
DB_USER="$DB_USER" \
DB_PASS="$DB_PASS" \
PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
PRE_DEPLOY_BACKUP_LOG_FILE="$PRE_DEPLOY_BACKUP_LOG_FILE" \
bash "$PRE_DEPLOY_BACKUP_SCRIPT"

if [ ! -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ]; then
  echo "FAIL: pre-deploy backup metadata not found: $PRE_DEPLOY_BACKUP_ENV_FILE"
  exit 1
fi

# shellcheck disable=SC1090
. "$PRE_DEPLOY_BACKUP_ENV_FILE"

if [ -z "${PRE_DEPLOY_BACKUP_MANIFEST:-}" ] || [ ! -f "$PRE_DEPLOY_BACKUP_MANIFEST" ]; then
  echo "FAIL: pre-deploy backup manifest missing."
  exit 1
fi

if [ -z "${PRE_DEPLOY_BACKUP_DB_FILE:-}" ] || [ ! -f "$PRE_DEPLOY_BACKUP_DB_FILE" ]; then
  echo "FAIL: pre-deploy backup db file missing."
  exit 1
fi

gzip -t "$PRE_DEPLOY_BACKUP_DB_FILE"

echo "Backup manifest: $PRE_DEPLOY_BACKUP_MANIFEST"
echo "Backup log: $PRE_DEPLOY_BACKUP_LOG_FILE"

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

echo "==> Post-deploy healthcheck"
mkdir -p "$OPS_DIR"
APP_DIR="$APP_DIR" \
BACKUP_DIR="$BACKUP_DIR" \
OPS_DIR="$OPS_DIR" \
APP_URL="$APP_URL_VALUE" \
LOG_SINCE="${LOG_SINCE:-30 min ago}" \
REQUIRE_QUEUE="${REQUIRE_QUEUE:-1}" \
SENSITIVE_PATHS_CSV="${SENSITIVE_PATHS_CSV:-/.env,/.git/config,/storage/logs/laravel.log,/composer.json}" \
PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
POST_DEPLOY_HEALTHCHECK_ENV_FILE="$POST_DEPLOY_HEALTHCHECK_ENV_FILE" \
POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE="$POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE" \
bash "$POST_DEPLOY_HEALTHCHECK_SCRIPT"

echo "==> Post-deploy smoke"
APP_DIR="$APP_DIR" \
BACKUP_DIR="$BACKUP_DIR" \
OPS_DIR="$OPS_DIR" \
K6_BASE_URL="$APP_URL_VALUE" \
K6_DURATION="${K6_DURATION:-1m}" \
K6_VUS="${K6_VUS:-1}" \
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-0}" \
K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard,/dashboard/account,/dashboard/hrm,/account/reports/mozambique-financial-compliance-dashboard,/account/reports/mozambique-go-live-readiness,/sales-invoices,/purchase-invoices,/hrm/reports/modelo19-support,/hrm/reports/inss-guide,/hrm/reports/accounting-journal-lines}" \
SMOKE_LOGIN_EMAIL="${SMOKE_LOGIN_EMAIL:-${K6_LOGIN_EMAIL:-}}" \
SMOKE_LOGIN_PASSWORD="${SMOKE_LOGIN_PASSWORD:-${K6_LOGIN_PASSWORD:-}}" \
PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
POST_DEPLOY_HEALTHCHECK_ENV_FILE="$POST_DEPLOY_HEALTHCHECK_ENV_FILE" \
POST_DEPLOY_SMOKE_ENV_FILE="$POST_DEPLOY_SMOKE_ENV_FILE" \
POST_DEPLOY_SMOKE_LOG_FILE="$POST_DEPLOY_SMOKE_LOG_FILE" \
bash "$POST_DEPLOY_SMOKE_SCRIPT"

echo "==> Post-deploy k6 matrix"
APP_DIR="$APP_DIR" \
BACKUP_DIR="$BACKUP_DIR" \
OPS_DIR="$OPS_DIR" \
K6_BASE_URL="$APP_URL_VALUE" \
K6_DURATION="${K6_DURATION:-2m}" \
K6_VUS_MATRIX="${K6_VUS_MATRIX:-25,50}" \
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-1}" \
K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard,/dashboard/account,/dashboard/hrm,/account/reports/mozambique-financial-compliance-dashboard,/account/reports/mozambique-go-live-readiness,/sales-invoices,/purchase-invoices,/hrm/reports/modelo19-support,/hrm/reports/inss-guide,/hrm/reports/accounting-journal-lines}" \
K6_LOGIN_EMAIL="${K6_LOGIN_EMAIL:-${SMOKE_LOGIN_EMAIL:-}}" \
K6_LOGIN_PASSWORD="${K6_LOGIN_PASSWORD:-${SMOKE_LOGIN_PASSWORD:-}}" \
PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
POST_DEPLOY_SMOKE_ENV_FILE="$POST_DEPLOY_SMOKE_ENV_FILE" \
POST_DEPLOY_K6_MATRIX_ENV_FILE="$POST_DEPLOY_K6_MATRIX_ENV_FILE" \
POST_DEPLOY_K6_MATRIX_LOG_FILE="$POST_DEPLOY_K6_MATRIX_LOG_FILE" \
POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE="$POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE" \
POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE="$POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE" \
bash "$POST_DEPLOY_K6_MATRIX_SCRIPT"

echo "Deploy concluido."
echo "DB backup: $PRE_DEPLOY_BACKUP_DB_FILE"
if [ -n "${PRE_DEPLOY_BACKUP_APP_FILE:-}" ]; then
  echo "APP backup: $PRE_DEPLOY_BACKUP_APP_FILE"
fi
echo "Backup manifest: $PRE_DEPLOY_BACKUP_MANIFEST"
echo "Healthcheck log: $POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE"
echo "Healthcheck metadata: $POST_DEPLOY_HEALTHCHECK_ENV_FILE"
echo "Smoke log: $POST_DEPLOY_SMOKE_LOG_FILE"
echo "Smoke metadata: $POST_DEPLOY_SMOKE_ENV_FILE"
echo "K6 matrix log: $POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE"
echo "K6 matrix summary: $POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE"
echo "K6 matrix metadata: $POST_DEPLOY_K6_MATRIX_ENV_FILE"
