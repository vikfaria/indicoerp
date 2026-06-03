#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-backup}"
APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
ENV_FILE="${ENV_FILE:-${APP_DIR}/.env}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp/db}"
RETENTION_DAYS="${RETENTION_DAYS:-0}"
INCLUDE_APP_ARCHIVE="${INCLUDE_APP_ARCHIVE:-1}"
DRY_RUN="${DRY_RUN:-0}"

DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-}"
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-}"
MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}"

RESTORE_FILE="${RESTORE_FILE:-}"
VERIFY_DB_NAME="${VERIFY_DB_NAME:-}"
DROP_VERIFY_DB="${DROP_VERIFY_DB:-1}"
CONFIRM_RESTORE="${CONFIRM_RESTORE:-NO}"
RESTORE_APP_ARCHIVE="${RESTORE_APP_ARCHIVE:-}"

usage() {
  cat <<'USAGE'
Usage:
  bash deploy/scripts/16_backup_restore_indicoerp.sh backup
  RESTORE_FILE=/path/db.sql.gz VERIFY_DB_NAME=indicoerp_restore_check bash deploy/scripts/16_backup_restore_indicoerp.sh verify
  RESTORE_FILE=/path/db.sql.gz CONFIRM_RESTORE=YES bash deploy/scripts/16_backup_restore_indicoerp.sh restore

Environment:
  APP_DIR               Default: /var/www/indicoerp/repo
  ENV_FILE              Default: $APP_DIR/.env
  BACKUP_DIR            Default: /var/backups/indicoerp/db
  DB_HOST/DB_PORT       Loaded from .env when not provided
  DB_NAME/DB_USER       Loaded from .env when not provided
  DB_PASS               Loaded from .env DB_PASSWORD when not provided
  MYSQL_CONTAINER_NAME  Optional. If set, runs mysql/mysqldump inside this container.
  MYSQL_ADMIN_USER/PASS Optional admin credentials for create/drop database during verify/restore.
  INCLUDE_APP_ARCHIVE   1 to archive storage/.env together with DB backup. Default: 1
  RETENTION_DAYS        Delete old generated backup files if > 0. Default: 0
  DRY_RUN               Print actions without executing. Default: 0
USAGE
}

env_value() {
  local key="$1"
  [ -f "$ENV_FILE" ] || return 0

  awk -F= -v key="$key" '
    $1 == key {
      sub(/^[^=]*=/, "")
      gsub(/^[[:space:]]+|[[:space:]]+$/, "")
      gsub(/^"|"$/, "")
      gsub(/^'\''|'\''$/, "")
      print
      exit
    }
  ' "$ENV_FILE"
}

load_db_env() {
  DB_HOST="${DB_HOST:-$(env_value DB_HOST)}"
  DB_PORT="${DB_PORT:-$(env_value DB_PORT)}"
  DB_NAME="${DB_NAME:-$(env_value DB_DATABASE)}"
  DB_USER="${DB_USER:-$(env_value DB_USERNAME)}"
  DB_PASS="${DB_PASS:-$(env_value DB_PASSWORD)}"

  DB_HOST="${DB_HOST:-127.0.0.1}"
  DB_PORT="${DB_PORT:-3306}"
  MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-$DB_USER}"
  MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-$DB_PASS}"
}

validate_db_name() {
  local value="$1"
  local label="$2"

  if [[ ! "$value" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "Invalid ${label}: only letters, numbers and underscore are allowed."
    exit 1
  fi
}

require_db_config() {
  load_db_env

  if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Missing DB config. Set DB_NAME/DB_USER/DB_PASS or provide a readable .env."
    exit 1
  fi

  validate_db_name "$DB_NAME" "DB_NAME"

  if [ -n "$VERIFY_DB_NAME" ]; then
    validate_db_name "$VERIFY_DB_NAME" "VERIFY_DB_NAME"
    if [ "$VERIFY_DB_NAME" = "$DB_NAME" ]; then
      echo "VERIFY_DB_NAME must be different from DB_NAME."
      exit 1
    fi
  fi
}

run_or_print() {
  if [ "$DRY_RUN" = "1" ]; then
    printf '[dry-run] %s\n' "$*"
    return 0
  fi

  "$@"
}

mysql_exec() {
  local user="$1"
  local pass="$2"
  shift 2

  if [ -n "$MYSQL_CONTAINER_NAME" ]; then
    docker exec -i -e MYSQL_PWD="$pass" "$MYSQL_CONTAINER_NAME" mysql -u"$user" "$@"
  else
    MYSQL_PWD="$pass" mysql -h"$DB_HOST" -P"$DB_PORT" -u"$user" "$@"
  fi
}

mysqldump_exec() {
  if [ -n "$MYSQL_CONTAINER_NAME" ]; then
    docker exec -i -e MYSQL_PWD="$DB_PASS" "$MYSQL_CONTAINER_NAME" mysqldump \
      --single-transaction --quick --routines --triggers --events --no-tablespaces \
      -u"$DB_USER" "$DB_NAME"
  else
    MYSQL_PWD="$DB_PASS" mysqldump \
      --single-transaction --quick --routines --triggers --events --no-tablespaces \
      -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$DB_NAME"
  fi
}

sha256_file() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$1" | awk '{print $1}'
  else
    shasum -a 256 "$1" | awk '{print $1}'
  fi
}

backup() {
  require_db_config

  local timestamp db_file app_file manifest git_ref
  timestamp="$(date -u +%Y%m%d_%H%M%S)"
  db_file="${BACKUP_DIR}/db_${DB_NAME}_${timestamp}.sql.gz"
  app_file="${BACKUP_DIR}/app_storage_env_${timestamp}.tar.gz"
  manifest="${BACKUP_DIR}/backup_${DB_NAME}_${timestamp}.manifest"
  git_ref="unknown"

  if [ -d "$APP_DIR/.git" ]; then
    git_ref="$(git -C "$APP_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)"
  fi

  echo "==> Backup start"
  echo "APP_DIR: $APP_DIR"
  echo "DB: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"
  echo "BACKUP_DIR: $BACKUP_DIR"

  if [ "$DRY_RUN" = "1" ]; then
    echo "[dry-run] mkdir -p $BACKUP_DIR"
    echo "[dry-run] mysqldump -> $db_file"
    [ "$INCLUDE_APP_ARCHIVE" = "1" ] && echo "[dry-run] tar storage .env -> $app_file"
    return 0
  fi

  mkdir -p "$BACKUP_DIR"
  mysqldump_exec | gzip -9 > "$db_file"
  gzip -t "$db_file"

  {
    echo "created_at_utc=$(date -u +%FT%TZ)"
    echo "app_dir=$APP_DIR"
    echo "git_ref=$git_ref"
    echo "db_name=$DB_NAME"
    echo "db_host=$DB_HOST"
    echo "db_port=$DB_PORT"
    echo "db_file=$db_file"
    echo "db_sha256=$(sha256_file "$db_file")"
  } > "$manifest"

  if [ "$INCLUDE_APP_ARCHIVE" = "1" ]; then
    tar -C "$APP_DIR" -czf "$app_file" storage .env
    echo "app_file=$app_file" >> "$manifest"
    echo "app_sha256=$(sha256_file "$app_file")" >> "$manifest"
  fi

  if [ "$RETENTION_DAYS" -gt 0 ]; then
    find "$BACKUP_DIR" -type f \( -name 'db_*.sql.gz' -o -name 'app_storage_env_*.tar.gz' -o -name 'backup_*.manifest' \) -mtime +"$RETENTION_DAYS" -delete
  fi

  echo "OK: backup created"
  echo "DB backup: $db_file"
  echo "Manifest: $manifest"
  [ "$INCLUDE_APP_ARCHIVE" = "1" ] && echo "App archive: $app_file"
}

verify_dump_file() {
  if [ -z "$RESTORE_FILE" ]; then
    echo "RESTORE_FILE is required."
    exit 1
  fi

  if [ ! -f "$RESTORE_FILE" ]; then
    echo "RESTORE_FILE not found: $RESTORE_FILE"
    exit 1
  fi

  gzip -t "$RESTORE_FILE"
  echo "OK: gzip integrity valid for $RESTORE_FILE"
}

verify() {
  require_db_config
  verify_dump_file

  if [ -z "$VERIFY_DB_NAME" ]; then
    echo "OK: dump file verified. Set VERIFY_DB_NAME to test restore into a temporary database."
    return 0
  fi

  echo "==> Restore verification into temporary database: $VERIFY_DB_NAME"

  if [ "$DRY_RUN" = "1" ]; then
    echo "[dry-run] create $VERIFY_DB_NAME, import dump, count tables, optional drop"
    return 0
  fi

  mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" -e "DROP DATABASE IF EXISTS \`$VERIFY_DB_NAME\`; CREATE DATABASE \`$VERIFY_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  gzip -dc "$RESTORE_FILE" | mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" "$VERIFY_DB_NAME"

  local table_count
  table_count="$(mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${VERIFY_DB_NAME}';")"
  echo "OK: restore verification imported ${table_count} table(s)."

  if [ "$DROP_VERIFY_DB" = "1" ]; then
    mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" -e "DROP DATABASE IF EXISTS \`$VERIFY_DB_NAME\`;"
    echo "OK: temporary verification database dropped."
  else
    echo "WARN: temporary verification database kept: $VERIFY_DB_NAME"
  fi
}

restore() {
  require_db_config
  verify_dump_file

  if [ "$CONFIRM_RESTORE" != "YES" ]; then
    echo "Refusing restore. Set CONFIRM_RESTORE=YES to overwrite database $DB_NAME."
    exit 1
  fi

  echo "==> Restore start"
  echo "Target DB: $DB_NAME"
  echo "Dump: $RESTORE_FILE"

  if [ "$DRY_RUN" = "1" ]; then
    echo "[dry-run] drop/create $DB_NAME and import $RESTORE_FILE"
    return 0
  fi

  mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  gzip -dc "$RESTORE_FILE" | mysql_exec "$MYSQL_ADMIN_USER" "$MYSQL_ADMIN_PASS" "$DB_NAME"

  if [ -n "$RESTORE_APP_ARCHIVE" ]; then
    if [ ! -f "$RESTORE_APP_ARCHIVE" ]; then
      echo "RESTORE_APP_ARCHIVE not found: $RESTORE_APP_ARCHIVE"
      exit 1
    fi

    tar -C "$APP_DIR" -xzf "$RESTORE_APP_ARCHIVE"
    echo "OK: app archive restored into $APP_DIR"
  fi

  echo "OK: database restored"
}

case "$ACTION" in
  backup)
    backup
    ;;
  verify)
    verify
    ;;
  restore)
    restore
    ;;
  help|-h|--help)
    usage
    ;;
  *)
    usage
    echo "Unknown action: $ACTION"
    exit 1
    ;;
esac
