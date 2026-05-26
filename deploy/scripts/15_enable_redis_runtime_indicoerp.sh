#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-enable}"

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
ENV_FILE="${ENV_FILE:-$APP_DIR/.env}"
PHP_BIN="${PHP_BIN:-php}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm.service}"
QUEUE_SERVICE="${QUEUE_SERVICE:-indicoerp-queue.service}"
SCHEDULER_SERVICE="${SCHEDULER_SERVICE:-indicoerp-scheduler.service}"

REDIS_CLIENT_VALUE="${REDIS_CLIENT_VALUE:-phpredis}"
REDIS_HOST_VALUE="${REDIS_HOST_VALUE:-127.0.0.1}"
REDIS_PORT_VALUE="${REDIS_PORT_VALUE:-6379}"
REDIS_PASSWORD_VALUE="${REDIS_PASSWORD_VALUE:-null}"
REDIS_DB_VALUE="${REDIS_DB_VALUE:-0}"
REDIS_CACHE_DB_VALUE="${REDIS_CACHE_DB_VALUE:-1}"
REDIS_CACHE_CONNECTION_VALUE="${REDIS_CACHE_CONNECTION_VALUE:-cache}"
REDIS_CACHE_LOCK_CONNECTION_VALUE="${REDIS_CACHE_LOCK_CONNECTION_VALUE:-default}"

if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "ERRO: executar como root."
  exit 1
fi

cd "$APP_DIR"

if [ ! -f artisan ]; then
  echo "ERRO: ficheiro artisan nao encontrado em $APP_DIR"
  exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
  echo "ERRO: ficheiro .env nao encontrado em $ENV_FILE"
  exit 1
fi

BACKUP_DIR="/var/backups/indicoerp/env"
mkdir -p "$BACKUP_DIR"

set_env_value() {
  local key="$1"
  local value="$2"

  if grep -Eq "^${key}=" "$ENV_FILE"; then
    sed -i -E "s|^${key}=.*|${key}=${value}|g" "$ENV_FILE"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
  fi
}

restart_if_exists() {
  local unit="$1"

  if [ -n "$unit" ] && systemctl list-unit-files --type=service --no-legend 2>/dev/null | awk '{print $1}' | grep -Fxq "$unit"; then
    systemctl restart "$unit"
  fi
}

validate_redis_client() {
  if [ "$REDIS_CLIENT_VALUE" = "phpredis" ]; then
    if ! "$PHP_BIN" -m | grep -qi '^redis$'; then
      echo "ERRO: extensao phpredis nao encontrada no PHP CLI."
      echo "Defina REDIS_CLIENT_VALUE=predis ou instale a extensao redis."
      exit 1
    fi
  fi
}

smoke_test_runtime() {
  "$PHP_BIN" artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

echo 'session.driver=' . config('session.driver') . PHP_EOL;
echo 'cache.default=' . config('cache.default') . PHP_EOL;
echo 'redis.client=' . config('database.redis.client') . PHP_EOL;
echo 'redis.ping=' . Redis::connection('default')->ping() . PHP_EOL;
Cache::store(config('cache.default'))->put('perf:redis:smoke', 'ok', 60);
echo 'cache.smoke=' . Cache::store(config('cache.default'))->get('perf:redis:smoke') . PHP_EOL;
" >/tmp/indicoerp_redis_runtime_check.txt

  cat /tmp/indicoerp_redis_runtime_check.txt
}

apply_runtime_config() {
  set_env_value "REDIS_CLIENT" "$REDIS_CLIENT_VALUE"
  set_env_value "REDIS_HOST" "$REDIS_HOST_VALUE"
  set_env_value "REDIS_PORT" "$REDIS_PORT_VALUE"
  set_env_value "REDIS_PASSWORD" "$REDIS_PASSWORD_VALUE"
  set_env_value "REDIS_DB" "$REDIS_DB_VALUE"
  set_env_value "REDIS_CACHE_DB" "$REDIS_CACHE_DB_VALUE"
  set_env_value "REDIS_CACHE_CONNECTION" "$REDIS_CACHE_CONNECTION_VALUE"
  set_env_value "REDIS_CACHE_LOCK_CONNECTION" "$REDIS_CACHE_LOCK_CONNECTION_VALUE"
}

enable_runtime() {
  validate_redis_client
  cp "$ENV_FILE" "$BACKUP_DIR/.env.$(date +%F_%H%M%S).bak"

  apply_runtime_config
  set_env_value "CACHE_DRIVER" "redis"
  set_env_value "CACHE_STORE" "redis"
  set_env_value "SESSION_DRIVER" "redis"
  set_env_value "SESSION_CONNECTION" "default"
  set_env_value "SESSION_STORE" "redis"

  "$PHP_BIN" artisan optimize:clear
  "$PHP_BIN" artisan config:cache
  restart_if_exists "$PHP_FPM_SERVICE"
  restart_if_exists "$QUEUE_SERVICE"
  restart_if_exists "$SCHEDULER_SERVICE"

  smoke_test_runtime
}

disable_runtime() {
  cp "$ENV_FILE" "$BACKUP_DIR/.env.$(date +%F_%H%M%S).bak"

  set_env_value "CACHE_DRIVER" "file"
  set_env_value "CACHE_STORE" "file"
  set_env_value "SESSION_DRIVER" "file"
  set_env_value "SESSION_CONNECTION" ""
  set_env_value "SESSION_STORE" ""

  "$PHP_BIN" artisan optimize:clear
  "$PHP_BIN" artisan config:cache
  restart_if_exists "$PHP_FPM_SERVICE"
  restart_if_exists "$QUEUE_SERVICE"
  restart_if_exists "$SCHEDULER_SERVICE"

  "$PHP_BIN" artisan tinker --execute="
echo 'session.driver=' . config('session.driver') . PHP_EOL;
echo 'cache.default=' . config('cache.default') . PHP_EOL;
" >/tmp/indicoerp_redis_runtime_check.txt

  cat /tmp/indicoerp_redis_runtime_check.txt
}

status_runtime() {
  "$PHP_BIN" artisan tinker --execute="
echo 'session.driver=' . config('session.driver') . PHP_EOL;
echo 'session.connection=' . (config('session.connection') ?? '') . PHP_EOL;
echo 'session.store=' . (config('session.store') ?? '') . PHP_EOL;
echo 'cache.default=' . config('cache.default') . PHP_EOL;
echo 'redis.client=' . config('database.redis.client') . PHP_EOL;
echo 'redis.default.host=' . config('database.redis.default.host') . PHP_EOL;
echo 'redis.cache.db=' . config('database.redis.cache.database') . PHP_EOL;
" 
}

case "$ACTION" in
  enable)
    enable_runtime
    ;;
  disable)
    disable_runtime
    ;;
  status)
    status_runtime
    ;;
  *)
    echo "Uso: $0 [enable|disable|status]"
    exit 1
    ;;
esac

