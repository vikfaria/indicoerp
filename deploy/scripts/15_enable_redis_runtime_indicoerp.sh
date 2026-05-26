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

LAST_BACKUP_FILE=""
ROLLBACK_ON_ERROR=0

set_env_value() {
  local key="$1"
  local value="$2"
  local escaped_value

  escaped_value="$(printf '%s' "$value" | sed -e 's/[\/&|]/\\&/g')"

  if grep -Eq "^${key}=" "$ENV_FILE"; then
    sed -i -E "s|^${key}=.*|${key}=${escaped_value}|g" "$ENV_FILE"
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

restore_runtime_backup() {
  if [ "$ROLLBACK_ON_ERROR" -ne 1 ] || [ -z "$LAST_BACKUP_FILE" ] || [ ! -f "$LAST_BACKUP_FILE" ]; then
    return 0
  fi

  echo "AVISO: falha detectada. A repor .env anterior a partir de $LAST_BACKUP_FILE"
  cp "$LAST_BACKUP_FILE" "$ENV_FILE"
  "$PHP_BIN" artisan config:clear >/dev/null 2>&1 || true
  "$PHP_BIN" artisan optimize:clear >/dev/null 2>&1 || true
  "$PHP_BIN" artisan config:cache >/dev/null 2>&1 || true
  restart_if_exists "$PHP_FPM_SERVICE" || true
  restart_if_exists "$QUEUE_SERVICE" || true
  restart_if_exists "$SCHEDULER_SERVICE" || true
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

redis_cli_available() {
  command -v redis-cli >/dev/null 2>&1
}

docker_available() {
  command -v docker >/dev/null 2>&1
}

normalize_redis_password_value() {
  local value="${1:-}"

  case "$value" in
    ""|"null"|"NULL"|"auto"|"AUTO")
      echo ""
      ;;
    *)
      echo "$value"
      ;;
  esac
}

redis_ping() {
  local password="${1:-}"
  local output=""

  if [ "$REDIS_CLIENT_VALUE" = "phpredis" ] && "$PHP_BIN" -m | grep -qi '^redis$'; then
    output="$(REDIS_HOST="$REDIS_HOST_VALUE" REDIS_PORT="$REDIS_PORT_VALUE" REDIS_PASSWORD="$password" "$PHP_BIN" -r '
      $host = getenv("REDIS_HOST") ?: "127.0.0.1";
      $port = (int)(getenv("REDIS_PORT") ?: "6379");
      $password = getenv("REDIS_PASSWORD");
      try {
        $redis = new Redis();
        $redis->connect($host, $port, 2.0);
        if ($password !== false && $password !== "") {
          $redis->auth($password);
        }
        $pong = $redis->ping();
        if ($pong === true) {
          echo "PONG";
        } else {
          echo strtoupper((string)$pong);
        }
      } catch (Throwable $e) {
        echo $e->getMessage();
      }
    ' 2>&1 || true)"
  fi

  if [ -z "$output" ] && redis_cli_available; then
    if [ -n "$password" ]; then
      output="$(redis-cli --no-auth-warning -h "$REDIS_HOST_VALUE" -p "$REDIS_PORT_VALUE" -a "$password" ping 2>&1 || true)"
    else
      output="$(redis-cli -h "$REDIS_HOST_VALUE" -p "$REDIS_PORT_VALUE" ping 2>&1 || true)"
    fi
  fi

  if [ -z "$output" ]; then
    output="ERRO: nao foi possivel validar Redis (phpredis/redis-cli indisponiveis)."
  fi

  echo "$output"
}

discover_redis_password_from_docker_env() {
  local cid image name password

  docker_available || return 1

  while read -r cid image name; do
    [ -n "$cid" ] || continue

    if ! echo "$image $name" | grep -Eqi '(redis|valkey|keydb)'; then
      continue
    fi

    password="$(
      docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' "$cid" 2>/dev/null \
      | sed -nE 's/^(REDIS_PASSWORD|REDISCLI_AUTH)=(.+)$/\2/p' \
      | head -n 1 || true
    )"
    if [ -n "$password" ]; then
      echo "$password"
      return 0
    fi

    password="$(
      docker inspect --format '{{.Path}} {{range .Args}}{{printf "%s " .}}{{end}}' "$cid" 2>/dev/null \
      | sed -nE 's/.*--requirepass[[:space:]]+([^[:space:]]+).*/\1/p' \
      | head -n 1 || true
    )"
    if [ -n "$password" ]; then
      echo "$password"
      return 0
    fi
  done < <(docker ps --format '{{.ID}} {{.Image}} {{.Names}}' 2>/dev/null || true)

  return 1
}

discover_redis_password_from_aclfile() {
  local acl_file="$1"
  local line

  [ -f "$acl_file" ] || return 1

  line="$(grep -E '^user[[:space:]]+default[[:space:]]+' "$acl_file" | head -n 1 || true)"
  [ -n "$line" ] || return 1

  echo "$line" | sed -nE 's/.*[[:space:]]>([^[:space:]]+).*/\1/p' | head -n 1
}

discover_redis_password_from_configs() {
  local config_file acl_file password
  local candidates=(
    /etc/redis/redis.conf
    /etc/redis/redis-server.conf
    /etc/valkey/valkey.conf
  )

  for config_file in "${candidates[@]}"; do
    [ -f "$config_file" ] || continue

    password="$(sed -nE 's/^[[:space:]]*requirepass[[:space:]]+(.+)$/\1/p' "$config_file" | tail -n 1 | tr -d '"' | xargs || true)"
    if [ -n "$password" ]; then
      echo "$password"
      return 0
    fi

    acl_file="$(sed -nE 's/^[[:space:]]*aclfile[[:space:]]+(.+)$/\1/p' "$config_file" | tail -n 1 | tr -d '"' | xargs || true)"
    if [ -n "$acl_file" ]; then
      password="$(discover_redis_password_from_aclfile "$acl_file" || true)"
      if [ -n "$password" ]; then
        echo "$password"
        return 0
      fi
    fi
  done

  return 1
}

resolve_redis_password() {
  local normalized_password ping_output discovered_password

  normalized_password="$(normalize_redis_password_value "$REDIS_PASSWORD_VALUE")"
  ping_output="$(redis_ping "$normalized_password" || true)"

  if echo "$ping_output" | grep -Eiq '^\+?PONG$'; then
    REDIS_PASSWORD_VALUE="${normalized_password:-null}"
    return 0
  fi

  if ! echo "$ping_output" | grep -qi 'NOAUTH\|authentication required'; then
    echo "ERRO: falha a ligar ao Redis em ${REDIS_HOST_VALUE}:${REDIS_PORT_VALUE}"
    echo "$ping_output"
    return 1
  fi

  discovered_password="$(discover_redis_password_from_configs || true)"
  if [ -n "$discovered_password" ]; then
    ping_output="$(redis_ping "$discovered_password" || true)"
    if echo "$ping_output" | grep -Eiq '^\+?PONG$'; then
      REDIS_PASSWORD_VALUE="$discovered_password"
      echo "INFO: password Redis descoberta automaticamente a partir da configuracao local."
      return 0
    fi
  fi

  discovered_password="$(discover_redis_password_from_docker_env || true)"
  if [ -n "$discovered_password" ]; then
    ping_output="$(redis_ping "$discovered_password" || true)"
    if echo "$ping_output" | grep -Eiq '^\+?PONG$'; then
      REDIS_PASSWORD_VALUE="$discovered_password"
      echo "INFO: password Redis descoberta automaticamente via Docker."
      return 0
    fi
  fi

  echo "ERRO: Redis exige autenticacao e nao foi possivel validar uma password."
  echo "Defina REDIS_PASSWORD_VALUE='a-password-certa' e volte a executar."
  return 1
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
  resolve_redis_password

  LAST_BACKUP_FILE="$BACKUP_DIR/.env.$(date +%F_%H%M%S).bak"
  cp "$ENV_FILE" "$LAST_BACKUP_FILE"
  ROLLBACK_ON_ERROR=1
  trap restore_runtime_backup ERR

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

  ROLLBACK_ON_ERROR=0
  trap - ERR
}

disable_runtime() {
  LAST_BACKUP_FILE="$BACKUP_DIR/.env.$(date +%F_%H%M%S).bak"
  cp "$ENV_FILE" "$LAST_BACKUP_FILE"

  set_env_value "CACHE_DRIVER" "file"
  set_env_value "CACHE_STORE" "file"
  set_env_value "SESSION_DRIVER" "file"
  set_env_value "SESSION_CONNECTION" ""
  set_env_value "SESSION_STORE" ""

  # Clear config first to avoid stale cached settings forcing Redis on rollback.
  "$PHP_BIN" artisan config:clear || true
  if ! "$PHP_BIN" artisan optimize:clear; then
    echo "WARN: optimize:clear falhou durante rollback para file; a continuar."
  fi
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
  local configured_password

  "$PHP_BIN" artisan tinker --execute="
echo 'session.driver=' . config('session.driver') . PHP_EOL;
echo 'session.connection=' . (config('session.connection') ?? '') . PHP_EOL;
echo 'session.store=' . (config('session.store') ?? '') . PHP_EOL;
echo 'cache.default=' . config('cache.default') . PHP_EOL;
echo 'redis.client=' . config('database.redis.client') . PHP_EOL;
echo 'redis.default.host=' . config('database.redis.default.host') . PHP_EOL;
echo 'redis.cache.db=' . config('database.redis.cache.database') . PHP_EOL;
" 

  local ping_output
  configured_password="$(grep -E '^REDIS_PASSWORD=' "$ENV_FILE" | tail -n 1 | cut -d= -f2- || true)"
  ping_output="$(redis_ping "$(normalize_redis_password_value "$configured_password")" || true)"
  echo "redis.ping=${ping_output}"
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
