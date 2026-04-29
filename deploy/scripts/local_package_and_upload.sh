#!/usr/bin/env bash
set -euo pipefail

SERVER_HOST="${SERVER_HOST:-187.124.171.162}"
SERVER_USER="${SERVER_USER:-root}"
REMOTE_TMP="${REMOTE_TMP:-/tmp/hrm-release.tar.gz}"
ARCHIVE_NAME="${ARCHIVE_NAME:-/tmp/hrm-release.tar.gz}"

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$PROJECT_ROOT"

rm -f "$ARCHIVE_NAME"

tar -czf "$ARCHIVE_NAME" \
  --exclude=".git" \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude="storage/logs/*" \
  --exclude="storage/framework/cache/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  .

scp "$ARCHIVE_NAME" "${SERVER_USER}@${SERVER_HOST}:${REMOTE_TMP}"

echo "Upload concluído: ${SERVER_USER}@${SERVER_HOST}:${REMOTE_TMP}"
echo "No servidor execute:"
echo "sudo APP_ENV_FILE=/var/www/hrm-saas/shared/.env bash /var/www/hrm-saas/current/deploy/scripts/03_deploy_release.sh ${REMOTE_TMP}"
