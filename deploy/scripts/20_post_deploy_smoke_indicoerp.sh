#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"
OPS_DIR="${OPS_DIR:-${BACKUP_DIR}/ops}"
SMOKE_SCRIPT="${SMOKE_SCRIPT:-${APP_DIR}/deploy/scripts/08_run_k6_indicoerp_smoke.sh}"
PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.env}"
POST_DEPLOY_HEALTHCHECK_ENV_FILE="${POST_DEPLOY_HEALTHCHECK_ENV_FILE:-${OPS_DIR}/post_deploy_healthcheck_latest.env}"
POST_DEPLOY_SMOKE_ENV_FILE="${POST_DEPLOY_SMOKE_ENV_FILE:-${OPS_DIR}/post_deploy_smoke_latest.env}"
POST_DEPLOY_SMOKE_LOG_FILE="${POST_DEPLOY_SMOKE_LOG_FILE:-${OPS_DIR}/post_deploy_smoke_latest.log}"
POST_DEPLOY_SMOKE_LATEST_LOG_FILE="${POST_DEPLOY_SMOKE_LATEST_LOG_FILE:-${OPS_DIR}/post_deploy_smoke_latest.log}"
K6_BASE_URL="${K6_BASE_URL:-https://indicoerp.com}"
K6_DURATION="${K6_DURATION:-1m}"
K6_VUS="${K6_VUS:-1}"
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-0}"
K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard,/dashboard/account,/dashboard/hrm,/account/reports/mozambique-financial-compliance-dashboard,/account/reports/mozambique-go-live-readiness,/sales-invoices,/purchase-invoices,/hrm/reports/modelo19-support,/hrm/reports/inss-guide,/hrm/reports/accounting-journal-lines}"
SMOKE_LOGIN_EMAIL="${SMOKE_LOGIN_EMAIL:-${K6_LOGIN_EMAIL:-}}"
SMOKE_LOGIN_PASSWORD="${SMOKE_LOGIN_PASSWORD:-${K6_LOGIN_PASSWORD:-}}"
SMOKE_REQUIRE_LOGIN="${SMOKE_REQUIRE_LOGIN:-1}"

mkdir -p "$OPS_DIR"

if [ ! -f "$SMOKE_SCRIPT" ]; then
  echo "Smoke script not found: $SMOKE_SCRIPT"
  exit 1
fi

if [ -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$PRE_DEPLOY_BACKUP_ENV_FILE"
fi

if [ -f "$POST_DEPLOY_HEALTHCHECK_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$POST_DEPLOY_HEALTHCHECK_ENV_FILE"
fi

if [ "$SMOKE_REQUIRE_LOGIN" = "1" ] && { [ -z "$SMOKE_LOGIN_EMAIL" ] || [ -z "$SMOKE_LOGIN_PASSWORD" ]; }; then
  echo "SMOKE_LOGIN_EMAIL and SMOKE_LOGIN_PASSWORD are required for OPS-004 smoke."
  exit 1
fi

timestamp="$(date -u +%Y%m%d_%H%M%S)"
POST_DEPLOY_SMOKE_LOG_FILE="${OPS_DIR}/post_deploy_smoke_${timestamp}.log"

echo "==> Post-deploy smoke start"
echo "APP_DIR: $APP_DIR"
echo "K6_BASE_URL: $K6_BASE_URL"
echo "K6_DURATION: $K6_DURATION"
echo "K6_VUS: $K6_VUS"
echo "K6_AUTH_PATHS: $K6_AUTH_PATHS"
echo "LOG_FILE: $POST_DEPLOY_SMOKE_LOG_FILE"

tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

set +e
APP_DIR="$APP_DIR" \
K6_BASE_URL="$K6_BASE_URL" \
K6_DURATION="$K6_DURATION" \
K6_VUS="$K6_VUS" \
K6_THINK_TIME_SECONDS="$K6_THINK_TIME_SECONDS" \
K6_AUTH_PATHS="$K6_AUTH_PATHS" \
K6_LOGIN_EMAIL="$SMOKE_LOGIN_EMAIL" \
K6_LOGIN_PASSWORD="$SMOKE_LOGIN_PASSWORD" \
bash "$SMOKE_SCRIPT" 2>&1 | tee "$POST_DEPLOY_SMOKE_LOG_FILE" | tee "$tmp_output"
exit_code="${PIPESTATUS[0]}"
set -e

smoke_status="passed"
if [ "$exit_code" -ne 0 ]; then
  smoke_status="failed"
fi

manifest_ref=""
if [ -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$PRE_DEPLOY_BACKUP_ENV_FILE"
  manifest_ref="${PRE_DEPLOY_BACKUP_MANIFEST:-}"
fi

healthcheck_ref=""
if [ -f "$POST_DEPLOY_HEALTHCHECK_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$POST_DEPLOY_HEALTHCHECK_ENV_FILE"
  healthcheck_ref="${POST_DEPLOY_HEALTHCHECK_LOG_FILE:-}"
fi

{
  printf 'POST_DEPLOY_SMOKE_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'POST_DEPLOY_SMOKE_STATUS=%q\n' "$smoke_status"
  printf 'POST_DEPLOY_SMOKE_EXIT_CODE=%q\n' "$exit_code"
  printf 'POST_DEPLOY_SMOKE_LOG_FILE=%q\n' "$POST_DEPLOY_SMOKE_LOG_FILE"
  printf 'POST_DEPLOY_SMOKE_APP_URL=%q\n' "$K6_BASE_URL"
  printf 'POST_DEPLOY_SMOKE_DURATION=%q\n' "$K6_DURATION"
  printf 'POST_DEPLOY_SMOKE_VUS=%q\n' "$K6_VUS"
  printf 'POST_DEPLOY_SMOKE_AUTH_PATHS=%q\n' "$K6_AUTH_PATHS"
  printf 'POST_DEPLOY_SMOKE_PRE_DEPLOY_MANIFEST=%q\n' "$manifest_ref"
  printf 'POST_DEPLOY_SMOKE_HEALTHCHECK_LOG=%q\n' "$healthcheck_ref"
} > "$POST_DEPLOY_SMOKE_ENV_FILE"

chmod 600 "$POST_DEPLOY_SMOKE_ENV_FILE" "$POST_DEPLOY_SMOKE_LOG_FILE" 2>/dev/null || true
ln -sfn "$POST_DEPLOY_SMOKE_LOG_FILE" "$POST_DEPLOY_SMOKE_LATEST_LOG_FILE"

if [ "$exit_code" -ne 0 ]; then
  echo "FAIL: post-deploy smoke failed."
  exit "$exit_code"
fi

echo "OK: post-deploy smoke metadata: $POST_DEPLOY_SMOKE_ENV_FILE"
