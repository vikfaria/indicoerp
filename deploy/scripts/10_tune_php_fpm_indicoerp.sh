#!/usr/bin/env bash
set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.2}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php${PHP_VERSION}-fpm.service}"
PHP_FPM_POOL_CONF="${PHP_FPM_POOL_CONF:-/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf}"
SLOWLOG_PATH="${SLOWLOG_PATH:-/var/log/php${PHP_VERSION}-fpm/www-slow.log}"

PM_MODE="${PM_MODE:-dynamic}"
PM_MAX_CHILDREN="${PM_MAX_CHILDREN:-12}"
PM_START_SERVERS="${PM_START_SERVERS:-4}"
PM_MIN_SPARE_SERVERS="${PM_MIN_SPARE_SERVERS:-2}"
PM_MAX_SPARE_SERVERS="${PM_MAX_SPARE_SERVERS:-6}"
PM_MAX_REQUESTS="${PM_MAX_REQUESTS:-500}"
REQUEST_SLOWLOG_TIMEOUT="${REQUEST_SLOWLOG_TIMEOUT:-5s}"

if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "ERRO: executar como root."
  exit 1
fi

if [ ! -f "$PHP_FPM_POOL_CONF" ]; then
  echo "ERRO: ficheiro do pool nao encontrado: $PHP_FPM_POOL_CONF"
  exit 1
fi

is_positive_int() {
  [[ "$1" =~ ^[0-9]+$ ]] && [ "$1" -gt 0 ]
}

for value in "$PM_MAX_CHILDREN" "$PM_START_SERVERS" "$PM_MIN_SPARE_SERVERS" "$PM_MAX_SPARE_SERVERS" "$PM_MAX_REQUESTS"; do
  if ! is_positive_int "$value"; then
    echo "ERRO: valor invalido (esperado inteiro positivo): $value"
    exit 1
  fi
done

if [ "$PM_MODE" != "dynamic" ] && [ "$PM_MODE" != "ondemand" ] && [ "$PM_MODE" != "static" ]; then
  echo "ERRO: PM_MODE invalido: $PM_MODE"
  exit 1
fi

if [ "$PM_MODE" = "dynamic" ]; then
  if [ "$PM_START_SERVERS" -lt "$PM_MIN_SPARE_SERVERS" ] || [ "$PM_START_SERVERS" -gt "$PM_MAX_SPARE_SERVERS" ]; then
    echo "ERRO: pm.start_servers deve ficar entre pm.min_spare_servers e pm.max_spare_servers."
    exit 1
  fi
fi

if [ "$PM_MAX_CHILDREN" -lt "$PM_MAX_SPARE_SERVERS" ]; then
  echo "ERRO: pm.max_children deve ser >= pm.max_spare_servers."
  exit 1
fi

BACKUP_DIR="/var/backups/indicoerp/php-fpm"
mkdir -p "$BACKUP_DIR"
cp "$PHP_FPM_POOL_CONF" "$BACKUP_DIR/www.conf.$(date +%F_%H%M%S).bak"

mkdir -p "$(dirname "$SLOWLOG_PATH")"
touch "$SLOWLOG_PATH"
chown www-data:www-data "$SLOWLOG_PATH"
chmod 640 "$SLOWLOG_PATH"

set_or_append() {
  local key="$1"
  local value="$2"
  local key_regex="${key//./\\.}"

  if grep -Eq "^[;[:space:]]*${key_regex}[[:space:]]*=" "$PHP_FPM_POOL_CONF"; then
    sed -i -E "s|^[;[:space:]]*${key_regex}[[:space:]]*=.*|${key} = ${value}|g" "$PHP_FPM_POOL_CONF"
  else
    printf '%s = %s\n' "$key" "$value" >> "$PHP_FPM_POOL_CONF"
  fi
}

set_or_append "pm" "$PM_MODE"
set_or_append "pm.max_children" "$PM_MAX_CHILDREN"
set_or_append "pm.start_servers" "$PM_START_SERVERS"
set_or_append "pm.min_spare_servers" "$PM_MIN_SPARE_SERVERS"
set_or_append "pm.max_spare_servers" "$PM_MAX_SPARE_SERVERS"
set_or_append "pm.max_requests" "$PM_MAX_REQUESTS"
set_or_append "request_slowlog_timeout" "$REQUEST_SLOWLOG_TIMEOUT"
set_or_append "slowlog" "$SLOWLOG_PATH"

if command -v "php-fpm${PHP_VERSION}" >/dev/null 2>&1; then
  "php-fpm${PHP_VERSION}" -tt >/tmp/php-fpm-check.log 2>&1 || {
    cat /tmp/php-fpm-check.log
    echo "ERRO: validacao php-fpm falhou."
    exit 1
  }
fi

systemctl restart "$PHP_FPM_SERVICE"
systemctl is-active "$PHP_FPM_SERVICE" >/dev/null

echo "Tuning PHP-FPM aplicado com sucesso."
echo "Servico: $PHP_FPM_SERVICE"
echo "Pool: $PHP_FPM_POOL_CONF"
echo "pm=${PM_MODE}, max_children=${PM_MAX_CHILDREN}, start=${PM_START_SERVERS}, min_spare=${PM_MIN_SPARE_SERVERS}, max_spare=${PM_MAX_SPARE_SERVERS}, max_requests=${PM_MAX_REQUESTS}"
echo "slowlog: $SLOWLOG_PATH (timeout ${REQUEST_SLOWLOG_TIMEOUT})"
