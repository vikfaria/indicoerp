#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"
OPS_DIR="${OPS_DIR:-${BACKUP_DIR}/ops}"
MATRIX_SCRIPT="${MATRIX_SCRIPT:-${APP_DIR}/deploy/scripts/14_run_k6_matrix_indicoerp.sh}"
POST_DEPLOY_SMOKE_ENV_FILE="${POST_DEPLOY_SMOKE_ENV_FILE:-${OPS_DIR}/post_deploy_smoke_latest.env}"
POST_DEPLOY_K6_MATRIX_ENV_FILE="${POST_DEPLOY_K6_MATRIX_ENV_FILE:-${OPS_DIR}/post_deploy_k6_matrix_latest.env}"
POST_DEPLOY_K6_MATRIX_LOG_FILE="${POST_DEPLOY_K6_MATRIX_LOG_FILE:-${OPS_DIR}/post_deploy_k6_matrix_latest.log}"
POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE="${POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE:-${OPS_DIR}/post_deploy_k6_matrix_latest.log}"
POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE="${POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE:-${OPS_DIR}/k6_matrix_summary_latest.md}"
OUTPUT_DIR="${OUTPUT_DIR:-${OPS_DIR}/k6}"
K6_BASE_URL="${K6_BASE_URL:-https://indicoerp.com}"
K6_DURATION="${K6_DURATION:-2m}"
K6_VUS_MATRIX="${K6_VUS_MATRIX:-25,50}"
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-1}"
K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard,/dashboard/account,/dashboard/hrm,/account/reports/mozambique-financial-compliance-dashboard,/account/reports/mozambique-go-live-readiness,/sales-invoices,/purchase-invoices,/hrm/reports/modelo19-support,/hrm/reports/inss-guide,/hrm/reports/accounting-journal-lines}"
K6_LOGIN_EMAIL="${K6_LOGIN_EMAIL:-${SMOKE_LOGIN_EMAIL:-}}"
K6_LOGIN_PASSWORD="${K6_LOGIN_PASSWORD:-${SMOKE_LOGIN_PASSWORD:-}}"
REQUIRE_LOGIN="${REQUIRE_LOGIN:-1}"

mkdir -p "$OPS_DIR" "$OUTPUT_DIR"

if [ ! -f "$MATRIX_SCRIPT" ]; then
  echo "Matrix script not found: $MATRIX_SCRIPT"
  exit 1
fi

if [ "$REQUIRE_LOGIN" = "1" ] && { [ -z "$K6_LOGIN_EMAIL" ] || [ -z "$K6_LOGIN_PASSWORD" ]; }; then
  echo "K6_LOGIN_EMAIL and K6_LOGIN_PASSWORD are required for controlled load."
  exit 1
fi

timestamp="$(date -u +%Y%m%d_%H%M%S)"
POST_DEPLOY_K6_MATRIX_LOG_FILE="${OPS_DIR}/post_deploy_k6_matrix_${timestamp}.log"

echo "==> Post-deploy k6 matrix start"
echo "APP_DIR: $APP_DIR"
echo "K6_BASE_URL: $K6_BASE_URL"
echo "K6_DURATION: $K6_DURATION"
echo "K6_VUS_MATRIX: $K6_VUS_MATRIX"
echo "K6_AUTH_PATHS: $K6_AUTH_PATHS"
echo "OUTPUT_DIR: $OUTPUT_DIR"
echo "LOG_FILE: $POST_DEPLOY_K6_MATRIX_LOG_FILE"

tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

set +e
APP_DIR="$APP_DIR" \
OUTPUT_DIR="$OUTPUT_DIR" \
K6_BASE_URL="$K6_BASE_URL" \
K6_DURATION="$K6_DURATION" \
K6_VUS_MATRIX="$K6_VUS_MATRIX" \
K6_THINK_TIME_SECONDS="$K6_THINK_TIME_SECONDS" \
K6_AUTH_PATHS="$K6_AUTH_PATHS" \
K6_LOGIN_EMAIL="$K6_LOGIN_EMAIL" \
K6_LOGIN_PASSWORD="$K6_LOGIN_PASSWORD" \
bash "$MATRIX_SCRIPT" 2>&1 | tee "$POST_DEPLOY_K6_MATRIX_LOG_FILE" | tee "$tmp_output"
exit_code="${PIPESTATUS[0]}"
set -e

matrix_status="passed"
if [ "$exit_code" -ne 0 ]; then
  matrix_status="failed"
fi

summary_file="$(awk -F': ' '/^Resumo gravado em:/ {print $2; exit}' "$tmp_output")"
if [ -z "$summary_file" ] || [ ! -f "$summary_file" ]; then
  echo "FAIL: k6 matrix summary not generated."
  exit 1
fi

latest_summary_link="$POST_DEPLOY_K6_MATRIX_LATEST_SUMMARY_FILE"
ln -sfn "$summary_file" "$latest_summary_link"
ln -sfn "$POST_DEPLOY_K6_MATRIX_LOG_FILE" "$POST_DEPLOY_K6_MATRIX_LATEST_LOG_FILE"

smoke_ref=""
if [ -f "$POST_DEPLOY_SMOKE_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$POST_DEPLOY_SMOKE_ENV_FILE"
  smoke_ref="${POST_DEPLOY_SMOKE_LOG_FILE:-}"
fi

{
  printf 'POST_DEPLOY_K6_MATRIX_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'POST_DEPLOY_K6_MATRIX_STATUS=%q\n' "$matrix_status"
  printf 'POST_DEPLOY_K6_MATRIX_EXIT_CODE=%q\n' "$exit_code"
  printf 'POST_DEPLOY_K6_MATRIX_LOG_FILE=%q\n' "$POST_DEPLOY_K6_MATRIX_LOG_FILE"
  printf 'POST_DEPLOY_K6_MATRIX_SUMMARY_FILE=%q\n' "$summary_file"
  printf 'POST_DEPLOY_K6_MATRIX_OUTPUT_DIR=%q\n' "$OUTPUT_DIR"
  printf 'POST_DEPLOY_K6_MATRIX_APP_URL=%q\n' "$K6_BASE_URL"
  printf 'POST_DEPLOY_K6_MATRIX_DURATION=%q\n' "$K6_DURATION"
  printf 'POST_DEPLOY_K6_MATRIX_VUS_MATRIX=%q\n' "$K6_VUS_MATRIX"
  printf 'POST_DEPLOY_K6_MATRIX_AUTH_PATHS=%q\n' "$K6_AUTH_PATHS"
  printf 'POST_DEPLOY_K6_MATRIX_SMOKE_LOG=%q\n' "$smoke_ref"
} > "$POST_DEPLOY_K6_MATRIX_ENV_FILE"

chmod 600 "$POST_DEPLOY_K6_MATRIX_ENV_FILE" "$POST_DEPLOY_K6_MATRIX_LOG_FILE" 2>/dev/null || true

if [ "$exit_code" -ne 0 ]; then
  echo "FAIL: post-deploy k6 matrix failed."
  exit "$exit_code"
fi

echo "OK: post-deploy k6 matrix metadata: $POST_DEPLOY_K6_MATRIX_ENV_FILE"
