#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-status}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_NAME="${DB_NAME:-indicoerp}"
DB_USER="${DB_USER:-indicoerp_user}"
DB_PASS="${DB_PASS:-${MYSQL_PWD:-}}"

LONG_QUERY_TIME="${LONG_QUERY_TIME:-0.3}"
LOG_OUTPUT="${LOG_OUTPUT:-FILE}"
LOG_QUERIES_NOT_USING_INDEXES="${LOG_QUERIES_NOT_USING_INDEXES:-OFF}"
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-indicoerp_mysql}"
MYSQL_ROOT_USER="${MYSQL_ROOT_USER:-root}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-}"

if [ -z "$DB_PASS" ] && [ -t 0 ]; then
  read -r -s -p "DB password (${DB_USER}@${DB_HOST}:${DB_PORT}): " DB_PASS
  echo
fi

if [ -n "$DB_PASS" ]; then
  export MYSQL_PWD="$DB_PASS"
fi

mysql_exec() {
  mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -D"$DB_NAME" "$@"
}

mysql_query() {
  local sql="$1"
  mysql_exec -e "$sql"
}

discover_mysql_root_password() {
  if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    return 0
  fi

  if ! command -v docker >/dev/null 2>&1; then
    return 1
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fxq "$MYSQL_CONTAINER_NAME"; then
    return 1
  fi

  MYSQL_ROOT_PASSWORD="$(docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' "$MYSQL_CONTAINER_NAME" 2>/dev/null \
    | awk -F= '/^MYSQL_ROOT_PASSWORD=/{print substr($0, index($0, "=")+1)}' | tail -n 1)"

  [ -n "$MYSQL_ROOT_PASSWORD" ]
}

docker_mysql_admin_query() {
  local sql="$1"

  if ! command -v docker >/dev/null 2>&1; then
    return 1
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fxq "$MYSQL_CONTAINER_NAME"; then
    return 1
  fi

  if ! discover_mysql_root_password; then
    return 1
  fi

  docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" "$MYSQL_CONTAINER_NAME" \
    mysql -u"$MYSQL_ROOT_USER" -D"$DB_NAME" -e "$sql"
}

mysql_admin_query() {
  local sql="$1"
  local err_file
  err_file="$(mktemp)"

  if mysql_exec -e "$sql" >/dev/null 2>"$err_file"; then
    rm -f "$err_file"
    return 0
  fi

  if grep -Eq 'Access denied; you need .* (SUPER|SYSTEM_VARIABLES_ADMIN) privilege' "$err_file"; then
    echo "AVISO: utilizador ${DB_USER} sem privilegios admin para SET GLOBAL. A tentar via docker root (${MYSQL_CONTAINER_NAME})..."
    if docker_mysql_admin_query "$sql" >/dev/null 2>"$err_file"; then
      rm -f "$err_file"
      return 0
    fi
    echo "ERRO: nao foi possivel aplicar alteracao GLOBAL nem via utilizador da app nem via root no container."
    cat "$err_file"
    rm -f "$err_file"
    return 1
  fi

  cat "$err_file"
  rm -f "$err_file"
  return 1
}

require_mysql_access() {
  if ! mysql_exec -e "SELECT 1;" >/dev/null 2>&1; then
    echo "ERRO: falha a ligar ao MySQL em ${DB_HOST}:${DB_PORT} com utilizador ${DB_USER}."
    echo "Dica: export DB_PASS='***' antes de executar."
    exit 1
  fi
}

print_status() {
  echo "== MySQL Slow Log Status =="
  mysql_query "SHOW VARIABLES WHERE Variable_name IN ('slow_query_log','long_query_time','log_output','slow_query_log_file','log_queries_not_using_indexes');"
  mysql_query "SHOW GLOBAL STATUS LIKE 'Slow_queries';"

  local slowlog_path
  slowlog_path="$(mysql_exec -N -B -e "SHOW VARIABLES LIKE 'slow_query_log_file';" | awk '{print $2}' | tail -n 1)"
  [ -n "$slowlog_path" ] || return 0

  if [ -f "$slowlog_path" ]; then
    echo "Slow log detectado no host: $slowlog_path"
    return 0
  fi

  if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -Fxq "$MYSQL_CONTAINER_NAME"; then
    if docker exec "$MYSQL_CONTAINER_NAME" test -f "$slowlog_path"; then
      echo "Slow log detectado no container ${MYSQL_CONTAINER_NAME}: $slowlog_path"
      return 0
    fi
  fi

  echo "AVISO: slow log file configurado mas nao encontrado no host/container: $slowlog_path"
}

enable_slowlog() {
  echo "A ativar slow query log..."
  mysql_admin_query "SET GLOBAL log_output='${LOG_OUTPUT}';"
  mysql_admin_query "SET GLOBAL long_query_time=${LONG_QUERY_TIME};"
  mysql_admin_query "SET GLOBAL slow_query_log='ON';"
  mysql_admin_query "SET GLOBAL log_queries_not_using_indexes='${LOG_QUERIES_NOT_USING_INDEXES}';"
}

disable_slowlog() {
  echo "A desativar slow query log..."
  mysql_admin_query "SET GLOBAL slow_query_log='OFF';"
}

require_mysql_access

case "$ACTION" in
  status)
    print_status
    ;;
  enable)
    enable_slowlog
    print_status
    ;;
  disable)
    disable_slowlog
    print_status
    ;;
  *)
    echo "Uso: $0 [status|enable|disable]"
    exit 1
    ;;
esac
