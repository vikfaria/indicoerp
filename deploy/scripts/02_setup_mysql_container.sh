#!/usr/bin/env bash
set -euo pipefail

DB_CONTAINER_NAME="${DB_CONTAINER_NAME:-hrm_mysql}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"
DB_NAME="${DB_NAME:-hrm_saas}"
DB_USER="${DB_USER:-hrm_user}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_HOST_PORT="${DB_HOST_PORT:-3307}"
DB_VOLUME_DIR="${DB_VOLUME_DIR:-/opt/hrm/mysql-data}"

if [ -z "$DB_ROOT_PASSWORD" ] || [ -z "$DB_PASSWORD" ]; then
  echo "Defina DB_ROOT_PASSWORD e DB_PASSWORD."
  exit 1
fi

mkdir -p "$DB_VOLUME_DIR"

if docker ps -a --format '{{.Names}}' | grep -qx "$DB_CONTAINER_NAME"; then
  echo "Container $DB_CONTAINER_NAME já existe. A iniciar..."
  docker start "$DB_CONTAINER_NAME" >/dev/null || true
else
  docker run -d \
    --name "$DB_CONTAINER_NAME" \
    --restart unless-stopped \
    -p "127.0.0.1:${DB_HOST_PORT}:3306" \
    -v "${DB_VOLUME_DIR}:/var/lib/mysql" \
    -e MYSQL_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
    -e MYSQL_DATABASE="$DB_NAME" \
    -e MYSQL_USER="$DB_USER" \
    -e MYSQL_PASSWORD="$DB_PASSWORD" \
    mysql:8.0
fi

echo "Aguarde MySQL iniciar..."
sleep 10

docker exec -i "$DB_CONTAINER_NAME" mysql -uroot -p"$DB_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

echo "MySQL pronto em 127.0.0.1:${DB_HOST_PORT} (container: ${DB_CONTAINER_NAME})."
