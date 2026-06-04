#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp/db}"
VERIFY_SCRIPT="${VERIFY_SCRIPT:-${APP_DIR}/deploy/scripts/16_backup_restore_indicoerp.sh}"
PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.env}"
RESTORE_VERIFY_ENV_FILE="${RESTORE_VERIFY_ENV_FILE:-${BACKUP_DIR}/restore_verify_latest.env}"
RESTORE_VERIFY_LOG_FILE="${RESTORE_VERIFY_LOG_FILE:-${BACKUP_DIR}/restore_verify_latest.log}"
RESTORE_FILE="${RESTORE_FILE:-}"
VERIFY_DB_NAME="${VERIFY_DB_NAME:-}"
DROP_VERIFY_DB="${DROP_VERIFY_DB:-1}"
DRY_RUN="${DRY_RUN:-0}"

mkdir -p "$BACKUP_DIR"

if [ ! -f "$VERIFY_SCRIPT" ]; then
  echo "Verify script not found: $VERIFY_SCRIPT"
  exit 1
fi

if [ -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ] && [ -z "$RESTORE_FILE" ]; then
  # shellcheck disable=SC1090
  . "$PRE_DEPLOY_BACKUP_ENV_FILE"
  RESTORE_FILE="${PRE_DEPLOY_BACKUP_DB_FILE:-$RESTORE_FILE}"
fi

if [ -z "$RESTORE_FILE" ]; then
  echo "RESTORE_FILE is required or PRE_DEPLOY_BACKUP_ENV_FILE must point to a valid backup metadata file."
  exit 1
fi

timestamp="$(date -u +%Y%m%d_%H%M%S)"
if [ -z "$VERIFY_DB_NAME" ]; then
  VERIFY_DB_NAME="indicoerp_restore_check_${timestamp}"
fi

RESTORE_VERIFY_LOG_FILE="${BACKUP_DIR}/restore_verify_${timestamp}.log"

echo "==> Restore verification start"
echo "APP_DIR: $APP_DIR"
echo "RESTORE_FILE: $RESTORE_FILE"
echo "VERIFY_DB_NAME: $VERIFY_DB_NAME"
echo "LOG_FILE: $RESTORE_VERIFY_LOG_FILE"

tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

VERIFY_SCRIPT="$VERIFY_SCRIPT" \
APP_DIR="$APP_DIR" \
BACKUP_DIR="$BACKUP_DIR" \
RESTORE_FILE="$RESTORE_FILE" \
VERIFY_DB_NAME="$VERIFY_DB_NAME" \
DROP_VERIFY_DB="$DROP_VERIFY_DB" \
DB_HOST="${DB_HOST:-}" \
DB_PORT="${DB_PORT:-}" \
DB_NAME="${DB_NAME:-}" \
DB_USER="${DB_USER:-}" \
DB_PASS="${DB_PASS:-}" \
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-}" \
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-}" \
MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}" \
DRY_RUN="${DRY_RUN:-0}" \
bash "$VERIFY_SCRIPT" verify | tee "$RESTORE_VERIFY_LOG_FILE" | tee "$tmp_output"

if [ "$DRY_RUN" != "1" ]; then
  if ! grep -qE '^OK: restore verification imported [0-9]+ table\(s\)\.$' "$tmp_output"; then
    echo "FAIL: restore verification did not report imported tables."
    exit 1
  fi
fi

table_count="$(sed -n 's/^OK: restore verification imported \([0-9][0-9]*\) table(s)\.$/\1/p' "$tmp_output" | tail -n 1)"
manifest_ref=""
if [ -f "$PRE_DEPLOY_BACKUP_ENV_FILE" ]; then
  # shellcheck disable=SC1090
  . "$PRE_DEPLOY_BACKUP_ENV_FILE"
  manifest_ref="${PRE_DEPLOY_BACKUP_MANIFEST:-}"
fi

{
  printf 'RESTORE_VERIFY_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'RESTORE_VERIFY_DB_NAME=%q\n' "$VERIFY_DB_NAME"
  printf 'RESTORE_VERIFY_RESTORE_FILE=%q\n' "$RESTORE_FILE"
  printf 'RESTORE_VERIFY_TABLE_COUNT=%q\n' "${table_count:-}"
  printf 'RESTORE_VERIFY_LOG_FILE=%q\n' "$RESTORE_VERIFY_LOG_FILE"
  printf 'RESTORE_VERIFY_PRE_DEPLOY_MANIFEST=%q\n' "$manifest_ref"
  printf 'RESTORE_VERIFY_DROP_TEMP_DB=%q\n' "$DROP_VERIFY_DB"
} > "$RESTORE_VERIFY_ENV_FILE"

chmod 600 "$RESTORE_VERIFY_ENV_FILE" "$RESTORE_VERIFY_LOG_FILE" 2>/dev/null || true

echo "OK: restore verification metadata: $RESTORE_VERIFY_ENV_FILE"
