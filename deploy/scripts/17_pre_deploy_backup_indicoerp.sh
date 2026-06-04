#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp/db}"
ENV_FILE="${ENV_FILE:-${APP_DIR}/.env}"
BACKUP_SCRIPT="${BACKUP_SCRIPT:-${APP_DIR}/deploy/scripts/16_backup_restore_indicoerp.sh}"
PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.env}"
PRE_DEPLOY_BACKUP_LOG_FILE="${PRE_DEPLOY_BACKUP_LOG_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.log}"

mkdir -p "$BACKUP_DIR"

if [ ! -f "$BACKUP_SCRIPT" ]; then
  echo "Backup script not found: $BACKUP_SCRIPT"
  exit 1
fi

timestamp="$(date -u +%Y%m%d_%H%M%S)"
PRE_DEPLOY_BACKUP_LOG_FILE="${BACKUP_DIR}/pre_deploy_backup_${timestamp}.log"

echo "==> Pre-deploy backup start"
echo "APP_DIR: $APP_DIR"
echo "BACKUP_DIR: $BACKUP_DIR"
echo "LOG_FILE: $PRE_DEPLOY_BACKUP_LOG_FILE"

tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

BACKUP_DIR="$BACKUP_DIR" \
APP_DIR="$APP_DIR" \
ENV_FILE="$ENV_FILE" \
DB_HOST="${DB_HOST:-}" \
DB_PORT="${DB_PORT:-}" \
DB_NAME="${DB_NAME:-}" \
DB_USER="${DB_USER:-}" \
DB_PASS="${DB_PASS:-}" \
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-}" \
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-}" \
MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}" \
INCLUDE_APP_ARCHIVE="${INCLUDE_APP_ARCHIVE:-1}" \
RETENTION_DAYS="${RETENTION_DAYS:-0}" \
DRY_RUN="${DRY_RUN:-0}" \
bash "$BACKUP_SCRIPT" backup | tee "$PRE_DEPLOY_BACKUP_LOG_FILE" | tee "$tmp_output"

manifest="$(awk -F': ' '/^Manifest: / {print $2; exit}' "$tmp_output")"
db_file="$(awk -F': ' '/^DB backup: / {print $2; exit}' "$tmp_output")"
app_file="$(awk -F': ' '/^App archive: / {print $2; exit}' "$tmp_output")"

if [ -z "$manifest" ] || [ ! -f "$manifest" ]; then
  echo "FAIL: backup manifest not generated."
  exit 1
fi

if [ -z "$db_file" ] || [ ! -f "$db_file" ]; then
  echo "FAIL: database backup file not generated."
  exit 1
fi

gzip -t "$db_file"

if [ -n "$app_file" ] && [ ! -f "$app_file" ]; then
  echo "FAIL: app archive referenced by backup log was not created."
  exit 1
fi

{
  printf 'PRE_DEPLOY_BACKUP_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'PRE_DEPLOY_BACKUP_DB_FILE=%q\n' "$db_file"
  printf 'PRE_DEPLOY_BACKUP_MANIFEST=%q\n' "$manifest"
  printf 'PRE_DEPLOY_BACKUP_LOG_FILE=%q\n' "$PRE_DEPLOY_BACKUP_LOG_FILE"
  printf 'PRE_DEPLOY_BACKUP_APP_FILE=%q\n' "${app_file:-}"
} > "$PRE_DEPLOY_BACKUP_ENV_FILE"

chmod 600 "$PRE_DEPLOY_BACKUP_ENV_FILE" "$PRE_DEPLOY_BACKUP_LOG_FILE" 2>/dev/null || true

echo "OK: pre-deploy backup manifest: $manifest"
echo "OK: pre-deploy backup metadata: $PRE_DEPLOY_BACKUP_ENV_FILE"
