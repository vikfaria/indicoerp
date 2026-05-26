#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm.service}"
PHP_FPM_POOL_CONF="${PHP_FPM_POOL_CONF:-/etc/php/8.2/fpm/pool.d/www.conf}"
MEMORY_RESERVE_MB="${MEMORY_RESERVE_MB:-2048}"

to_mb_from_kb() {
  awk -v value_kb="$1" 'BEGIN { printf "%.1f", value_kb / 1024 }'
}

safe_grep_pool_value() {
  local key="$1"

  if [ ! -f "$PHP_FPM_POOL_CONF" ]; then
    echo "n/a"
    return 0
  fi

  grep -E "^[;[:space:]]*${key}[[:space:]]*=" "$PHP_FPM_POOL_CONF" \
    | tail -n 1 \
    | sed -E 's/^[;[:space:]]*[^=]+=[[:space:]]*//' || true
}

echo "== Server Performance Audit =="
echo "Timestamp: $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
echo "Host: $(hostname -f 2>/dev/null || hostname)"
echo

if [ -d "$APP_DIR" ]; then
  echo "App dir: $APP_DIR"
fi

echo "== Runtime =="
uptime || true
echo

echo "== CPU / Memory =="
free -m || true
echo

echo "== Disk =="
df -h / || true
echo

echo "== Swap =="
swapon --show || true
echo

echo "== PHP-FPM service =="
systemctl status --no-pager "$PHP_FPM_SERVICE" || true
echo

echo "== PHP-FPM pool config =="
echo "Pool conf: $PHP_FPM_POOL_CONF"
echo "pm=$(safe_grep_pool_value pm)"
echo "pm.max_children=$(safe_grep_pool_value pm.max_children)"
echo "pm.start_servers=$(safe_grep_pool_value pm.start_servers)"
echo "pm.min_spare_servers=$(safe_grep_pool_value pm.min_spare_servers)"
echo "pm.max_spare_servers=$(safe_grep_pool_value pm.max_spare_servers)"
echo "pm.max_requests=$(safe_grep_pool_value pm.max_requests)"
echo

echo "== PHP-FPM worker RSS =="
worker_rss_kb="$(ps -eo rss=,args= | awk '/php-fpm: pool/ { sum += $1; count += 1; if ($1 > max) max = $1 } END { if (count > 0) printf "%d %d %d", count, sum / count, max }')"

if [ -n "$worker_rss_kb" ]; then
  worker_count="$(echo "$worker_rss_kb" | awk '{print $1}')"
  worker_avg_kb="$(echo "$worker_rss_kb" | awk '{print $2}')"
  worker_max_kb="$(echo "$worker_rss_kb" | awk '{print $3}')"
  total_mem_mb="$(awk '/MemTotal/ { printf "%d", $2 / 1024 }' /proc/meminfo)"
  usable_mem_mb=$(( total_mem_mb - MEMORY_RESERVE_MB ))

  if [ "$usable_mem_mb" -lt 1024 ]; then
    usable_mem_mb=1024
  fi

  recommended_children="$(awk -v usable_mb="$usable_mem_mb" -v max_kb="$worker_max_kb" 'BEGIN { if (max_kb <= 0) { print 0 } else { printf "%d", (usable_mb * 1024) / max_kb } }')"

  echo "Workers ativos: $worker_count"
  echo "RSS medio por worker: $(to_mb_from_kb "$worker_avg_kb") MB"
  echo "RSS maximo observado: $(to_mb_from_kb "$worker_max_kb") MB"
  echo "Memoria reservada ao SO/DB/cache: ${MEMORY_RESERVE_MB} MB"
  echo "Recomendacao inicial pm.max_children: ${recommended_children}"
else
  echo "Nao foi possivel medir workers do PHP-FPM."
fi
echo

echo "== MySQL / MariaDB processes =="
ps -eo pid,user,pmem,pcpu,args | awk '/[m]ysqld|[m]ariadbd/ { print }' || true
echo

if command -v docker >/dev/null 2>&1; then
  echo "== Docker DB containers =="
  docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}' | awk 'NR == 1 || /mysql|mariadb/i' || true
  echo
fi

echo "== Redis processes =="
ps -eo pid,user,pmem,pcpu,args | awk '/[r]edis-server/ { print }' || true
echo

echo "== Top memory consumers =="
ps -eo pid,user,pmem,rss,args --sort=-rss | head -n 15 || true
echo

echo "== Recommendations =="
if ! swapon --show | grep -q .; then
  echo "- Swap nao configurada. Criar 2G ou 4G de swap para reduzir risco de OOM."
fi

if [ -n "${recommended_children:-}" ] && [ "${recommended_children:-0}" -gt 0 ]; then
  echo "- Rever pm.max_children atual vs recomendacao calculada."
fi

mysql_process_count="$(ps -eo args | awk '/[m]ysqld|[m]ariadbd/ { count += 1 } END { print count + 0 }')"
if [ "$mysql_process_count" -gt 1 ]; then
  echo "- Existem multiplos processos MySQL/MariaDB. Confirmar se e intencional."
fi

echo "- Validar slow query log diretamente no servidor MySQL antes de alterar buffers."
