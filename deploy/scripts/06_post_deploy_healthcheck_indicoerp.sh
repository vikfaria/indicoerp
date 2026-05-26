#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
APP_URL="${APP_URL:-https://indicoerp.com}"
LOG_SINCE="${LOG_SINCE:-30 min ago}"
LARAVEL_LOG_PATH="${LARAVEL_LOG_PATH:-storage/logs/laravel.log}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm.service}"
QUEUE_SERVICE="${QUEUE_SERVICE:-indicoerp-queue.service}"
SCHEDULER_SERVICE="${SCHEDULER_SERVICE:-indicoerp-scheduler.service}"
REQUIRE_QUEUE="${REQUIRE_QUEUE:-1}"
SENSITIVE_PATHS_CSV="${SENSITIVE_PATHS_CSV:-/.env,/.git/config,/storage/logs/laravel.log,/composer.json}"

cd "$APP_DIR"

if [ ! -f artisan ]; then
  echo "ERRO: ficheiro artisan não encontrado em $APP_DIR"
  exit 1
fi

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

assert_unit_active() {
  local unit="$1"
  local required="$2"

  if [ -z "$unit" ]; then
    if [ "$required" -eq 1 ]; then
      echo "FAIL: serviço obrigatório não resolvido."
      return 1
    fi
    echo "WARN: serviço opcional não resolvido."
    return 0
  fi

  if ! unit_exists "$unit"; then
    if [ "$required" -eq 1 ]; then
      echo "FAIL: unit não encontrada: $unit"
      return 1
    fi
    echo "WARN: unit opcional não encontrada: $unit"
    return 0
  fi

  local state
  state="$(systemctl is-active "$unit" || true)"
  if [ "$state" != "active" ]; then
    if [ "$required" -eq 1 ]; then
      echo "FAIL: unit não activa: $unit (estado: $state)"
      return 1
    fi
    echo "WARN: unit opcional não activa: $unit (estado: $state)"
    return 0
  fi

  echo "OK: $unit activa"
  return 0
}

PHP_FPM_SERVICE="$(resolve_service_unit "$PHP_FPM_SERVICE" '^php[0-9]+\.[0-9]+-fpm\.service$')"
QUEUE_SERVICE="$(resolve_service_unit "$QUEUE_SERVICE" '(^|-)queue\.service$' 'indicoerp-queue.service' 'hrm-queue.service')"
SCHEDULER_SERVICE="$(resolve_service_unit "$SCHEDULER_SERVICE" '(^|-)scheduler\.service$' 'indicoerp-scheduler.service' 'hrm-scheduler.service')"

echo "==> Healthcheck start: $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
echo "APP_DIR: $APP_DIR"
echo "APP_URL: $APP_URL"
echo "LOG_SINCE: $LOG_SINCE"
echo "PHP_FPM_SERVICE: ${PHP_FPM_SERVICE:-n/a}"
echo "QUEUE_SERVICE: ${QUEUE_SERVICE:-n/a}"
echo "SCHEDULER_SERVICE: ${SCHEDULER_SERVICE:-n/a}"
echo

FAILURES=0

HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 20 "$APP_URL" || true)"
if [[ "$HTTP_CODE" =~ ^(200|301|302)$ ]]; then
  echo "OK: HTTP ${HTTP_CODE} em ${APP_URL}"
else
  echo "FAIL: HTTP inesperado (${HTTP_CODE}) em ${APP_URL}"
  FAILURES=$((FAILURES + 1))
fi
echo

IFS=',' read -r -a SENSITIVE_PATHS <<< "$SENSITIVE_PATHS_CSV"
echo "==> Sensitive path checks"
for sensitive_path in "${SENSITIVE_PATHS[@]}"; do
  sensitive_path="$(echo "$sensitive_path" | xargs)"
  [ -n "$sensitive_path" ] || continue

  SENSITIVE_HTTP_CODE="$(curl -sS -L -o /dev/null -w '%{http_code}' --max-time 20 "${APP_URL%/}${sensitive_path}" || true)"
  if [[ "$SENSITIVE_HTTP_CODE" =~ ^(403|404)$ ]]; then
    echo "OK: ${sensitive_path} bloqueado com HTTP ${SENSITIVE_HTTP_CODE}"
  else
    echo "FAIL: ${sensitive_path} respondeu com HTTP ${SENSITIVE_HTTP_CODE}"
    FAILURES=$((FAILURES + 1))
  fi
done
echo

if php artisan migrate:status >/tmp/indicoerp_migrate_status.txt 2>/tmp/indicoerp_migrate_status.err; then
  echo "OK: migrate:status executado"
  tail -n 15 /tmp/indicoerp_migrate_status.txt
else
  echo "FAIL: migrate:status falhou"
  cat /tmp/indicoerp_migrate_status.err || true
  FAILURES=$((FAILURES + 1))
fi
echo

if ! assert_unit_active "nginx.service" 1; then
  FAILURES=$((FAILURES + 1))
fi
if ! assert_unit_active "$PHP_FPM_SERVICE" 1; then
  FAILURES=$((FAILURES + 1))
fi

if [ "$REQUIRE_QUEUE" -eq 1 ]; then
  if ! assert_unit_active "$QUEUE_SERVICE" 1; then
    FAILURES=$((FAILURES + 1))
  fi
  if ! assert_unit_active "$SCHEDULER_SERVICE" 1; then
    FAILURES=$((FAILURES + 1))
  fi
else
  assert_unit_active "$QUEUE_SERVICE" 0 || true
  assert_unit_active "$SCHEDULER_SERVICE" 0 || true
fi
echo

if [ -f "$LARAVEL_LOG_PATH" ]; then
  echo "==> Laravel errors desde: $LOG_SINCE"
  since_epoch="$(date -d "$LOG_SINCE" +%s 2>/dev/null || true)"
  if [ -n "$since_epoch" ]; then
    awk -v since_epoch="$since_epoch" '
      match($0, /^\[([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})\]/, m) {
        ts = mktime(m[1]" "m[2]" "m[3]" "m[4]" "m[5]" "m[6]);
        if (ts >= since_epoch && $0 ~ /(ERROR|CRITICAL|EMERGENCY):/) {
          print $0;
        }
      }
    ' "$LARAVEL_LOG_PATH" | tail -n 120
  else
    echo "WARN: não foi possível converter LOG_SINCE; mostrando últimos erros do ficheiro."
    grep -E '(ERROR|CRITICAL|EMERGENCY):' "$LARAVEL_LOG_PATH" | tail -n 120 || true
  fi
else
  echo "WARN: log Laravel não encontrado em $LARAVEL_LOG_PATH"
fi
echo

if command -v journalctl >/dev/null 2>&1; then
  echo "==> Systemd errors desde: $LOG_SINCE"
  journalctl --since "$LOG_SINCE" -u nginx.service \
    ${PHP_FPM_SERVICE:+-u "$PHP_FPM_SERVICE"} \
    ${QUEUE_SERVICE:+-u "$QUEUE_SERVICE"} \
    ${SCHEDULER_SERVICE:+-u "$SCHEDULER_SERVICE"} \
    -p err --no-pager || true
fi
echo

if [ "$FAILURES" -gt 0 ]; then
  echo "Healthcheck concluído com ${FAILURES} falha(s)."
  exit 1
fi

echo "Healthcheck concluído sem falhas."
