#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"
OPS_DIR="${OPS_DIR:-${BACKUP_DIR}/ops}"
HEALTHCHECK_SCRIPT="${HEALTHCHECK_SCRIPT:-${APP_DIR}/deploy/scripts/06_post_deploy_healthcheck_indicoerp.sh}"
PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.env}"
POST_DEPLOY_HEALTHCHECK_ENV_FILE="${POST_DEPLOY_HEALTHCHECK_ENV_FILE:-${OPS_DIR}/post_deploy_healthcheck_latest.env}"
POST_DEPLOY_HEALTHCHECK_LOG_FILE="${POST_DEPLOY_HEALTHCHECK_LOG_FILE:-${OPS_DIR}/post_deploy_healthcheck_latest.log}"
POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE="${POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE:-${OPS_DIR}/post_deploy_healthcheck_latest.log}"
APP_URL="${APP_URL:-https://indicoerp.com}"
LOG_SINCE="${LOG_SINCE:-30 min ago}"
REQUIRE_QUEUE="${REQUIRE_QUEUE:-1}"
SENSITIVE_PATHS_CSV="${SENSITIVE_PATHS_CSV:-/.env,/.git/config,/storage/logs/laravel.log,/composer.json}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm.service}"
NGINX_SERVICE="${NGINX_SERVICE:-nginx.service}"
QUEUE_SERVICE="${QUEUE_SERVICE:-indicoerp-queue.service}"
SCHEDULER_SERVICE="${SCHEDULER_SERVICE:-indicoerp-scheduler.service}"

mkdir -p "$OPS_DIR"

if [ ! -f "$HEALTHCHECK_SCRIPT" ]; then
  echo "Healthcheck script not found: $HEALTHCHECK_SCRIPT"
  exit 1
fi

timestamp="$(date -u +%Y%m%d_%H%M%S)"
POST_DEPLOY_HEALTHCHECK_LOG_FILE="${OPS_DIR}/post_deploy_healthcheck_${timestamp}.log"

echo "==> Post-deploy healthcheck start"
echo "APP_DIR: $APP_DIR"
echo "APP_URL: $APP_URL"
echo "LOG_SINCE: $LOG_SINCE"
echo "LOG_FILE: $POST_DEPLOY_HEALTHCHECK_LOG_FILE"

tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

set +e
APP_DIR="$APP_DIR" \
APP_URL="$APP_URL" \
LOG_SINCE="$LOG_SINCE" \
PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
NGINX_SERVICE="$NGINX_SERVICE" \
QUEUE_SERVICE="$QUEUE_SERVICE" \
SCHEDULER_SERVICE="$SCHEDULER_SERVICE" \
REQUIRE_QUEUE="$REQUIRE_QUEUE" \
SENSITIVE_PATHS_CSV="$SENSITIVE_PATHS_CSV" \
bash "$HEALTHCHECK_SCRIPT" 2>&1 | tee "$POST_DEPLOY_HEALTHCHECK_LOG_FILE" | tee "$tmp_output"
exit_code="${PIPESTATUS[0]}"
set -e

healthcheck_status="passed"
if [ "$exit_code" -ne 0 ]; then
  healthcheck_status="failed"
fi

manifest_ref=""
if [ -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$PRE_DEPLOY_BACKUP_ENV_FILE"
  manifest_ref="${PRE_DEPLOY_BACKUP_MANIFEST:-}"
fi

{
  printf 'POST_DEPLOY_HEALTHCHECK_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'POST_DEPLOY_HEALTHCHECK_STATUS=%q\n' "$healthcheck_status"
  printf 'POST_DEPLOY_HEALTHCHECK_EXIT_CODE=%q\n' "$exit_code"
  printf 'POST_DEPLOY_HEALTHCHECK_LOG_FILE=%q\n' "$POST_DEPLOY_HEALTHCHECK_LOG_FILE"
  printf 'POST_DEPLOY_HEALTHCHECK_APP_URL=%q\n' "$APP_URL"
  printf 'POST_DEPLOY_HEALTHCHECK_LOG_SINCE=%q\n' "$LOG_SINCE"
  printf 'POST_DEPLOY_HEALTHCHECK_PRE_DEPLOY_MANIFEST=%q\n' "$manifest_ref"
} > "$POST_DEPLOY_HEALTHCHECK_ENV_FILE"

chmod 600 "$POST_DEPLOY_HEALTHCHECK_ENV_FILE" "$POST_DEPLOY_HEALTHCHECK_LOG_FILE" 2>/dev/null || true
ln -sfn "$POST_DEPLOY_HEALTHCHECK_LOG_FILE" "$POST_DEPLOY_HEALTHCHECK_LATEST_LOG_FILE"

if [ "$exit_code" -ne 0 ]; then
  echo "FAIL: post-deploy healthcheck failed."
  exit "$exit_code"
fi

echo "OK: post-deploy healthcheck metadata: $POST_DEPLOY_HEALTHCHECK_ENV_FILE"
