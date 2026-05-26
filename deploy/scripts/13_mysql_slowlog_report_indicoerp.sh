#!/usr/bin/env bash
set -euo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_NAME="${DB_NAME:-indicoerp}"
DB_USER="${DB_USER:-indicoerp_user}"
DB_PASS="${DB_PASS:-${MYSQL_PWD:-}}"
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-indicoerp_mysql}"
TAIL_LINES="${TAIL_LINES:-120}"

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

print_digest_report() {
  echo "== Top query digests (performance_schema) =="
  mysql_exec -e "
SELECT
  ROUND(SUM_TIMER_WAIT / 1000000000000, 2) AS total_sec,
  COUNT_STAR AS exec_count,
  ROUND((SUM_TIMER_WAIT / 1000000000000) / NULLIF(COUNT_STAR, 0), 4) AS avg_sec,
  ROUND(MAX_TIMER_WAIT / 1000000000000, 4) AS max_sec,
  DIGEST_TEXT
FROM performance_schema.events_statements_summary_by_digest
WHERE SCHEMA_NAME = DATABASE()
  AND DIGEST_TEXT IS NOT NULL
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 20;
" 2>/dev/null || echo "AVISO: performance_schema digest indisponivel para este utilizador/servidor."
  echo
}

if ! mysql_exec -e "SELECT 1;" >/dev/null 2>&1; then
  echo "ERRO: falha a ligar ao MySQL em ${DB_HOST}:${DB_PORT} com utilizador ${DB_USER}."
  exit 1
fi

echo "== MySQL Slow Query Diagnostic =="
echo "Host: ${DB_HOST}:${DB_PORT}"
echo "Database: ${DB_NAME}"
echo

mysql_query "SHOW VARIABLES WHERE Variable_name IN ('slow_query_log','long_query_time','log_output','slow_query_log_file','log_queries_not_using_indexes');"
mysql_query "SHOW GLOBAL STATUS LIKE 'Slow_queries';"
echo

print_digest_report

slowlog_path="$(mysql_exec -N -B -e "SHOW VARIABLES LIKE 'slow_query_log_file';" | awk '{print $2}' | tail -n 1)"

if [ -z "$slowlog_path" ]; then
  echo "AVISO: nao foi possivel obter slow_query_log_file."
  exit 0
fi

echo "Slow log file: $slowlog_path"
echo

slow_enabled="$(mysql_exec -N -B -e "SHOW VARIABLES LIKE 'slow_query_log';" | awk '{print $2}' | tail -n 1)"
if [ "${slow_enabled,,}" != "on" ]; then
  echo "AVISO: slow_query_log esta OFF neste momento; o ficheiro pode nao estar a receber entradas."
  exit 0
fi

print_host_slowlog() {
  echo "== Tail slow log (host) =="
  tail -n "$TAIL_LINES" "$slowlog_path" || true
  echo

  if command -v mysqldumpslow >/dev/null 2>&1; then
    echo "== Top consultas lentas (mysqldumpslow host) =="
    mysqldumpslow -s t -t 20 "$slowlog_path" || true
    echo
  fi
}

print_container_slowlog() {
  echo "== Tail slow log (container ${MYSQL_CONTAINER_NAME}) =="
  docker exec "$MYSQL_CONTAINER_NAME" sh -lc "tail -n ${TAIL_LINES} '$slowlog_path'" || true
  echo

  if docker exec "$MYSQL_CONTAINER_NAME" sh -lc "command -v mysqldumpslow >/dev/null 2>&1"; then
    echo "== Top consultas lentas (mysqldumpslow container) =="
    docker exec "$MYSQL_CONTAINER_NAME" sh -lc "mysqldumpslow -s t -t 20 '$slowlog_path'" || true
    echo
  fi
}

if [ -f "$slowlog_path" ]; then
  print_host_slowlog
  exit 0
fi

if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -Fxq "$MYSQL_CONTAINER_NAME"; then
  if docker exec "$MYSQL_CONTAINER_NAME" sh -lc "test -f '$slowlog_path'"; then
    print_container_slowlog
    exit 0
  fi
fi

echo "AVISO: slow log file nao encontrado no host nem no container ${MYSQL_CONTAINER_NAME}."
echo "Verifica se o slow log esta ativado e se o caminho do ficheiro e valido."
