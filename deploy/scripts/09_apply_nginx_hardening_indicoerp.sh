#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-indicoerp.com}"
SNIPPET_TARGET="${SNIPPET_TARGET:-/etc/nginx/snippets/indicoerp-hardening.conf}"
SITE_PATH="${SITE_PATH:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SNIPPET_SOURCE="${SCRIPT_DIR}/../nginx/indicoerp-hardening-snippet.conf"
INCLUDE_DIRECTIVE="include ${SNIPPET_TARGET};"

if [ ! -f "$SNIPPET_SOURCE" ]; then
  echo "ERRO: snippet fonte nao encontrado: $SNIPPET_SOURCE"
  exit 1
fi

if [ -z "$SITE_PATH" ]; then
  for candidate in \
    "/etc/nginx/sites-available/${DOMAIN}.conf" \
    "/etc/nginx/sites-enabled/${DOMAIN}.conf" \
    "/etc/nginx/sites-available/default" \
    "/etc/nginx/sites-enabled/default"; do
    if [ -f "$candidate" ] && grep -Eq "server_name .*${DOMAIN}" "$candidate"; then
      SITE_PATH="$candidate"
      break
    fi
  done
fi

if [ -z "$SITE_PATH" ] || [ ! -f "$SITE_PATH" ]; then
  echo "ERRO: nao foi possivel resolver o ficheiro do vhost para ${DOMAIN}."
  exit 1
fi

mkdir -p "$(dirname "$SNIPPET_TARGET")"
cp "$SNIPPET_SOURCE" "$SNIPPET_TARGET"

if ! grep -Fq "$INCLUDE_DIRECTIVE" "$SITE_PATH"; then
  python3 - "$SITE_PATH" "$DOMAIN" "$INCLUDE_DIRECTIVE" <<'PY'
from pathlib import Path
import re
import sys

site_path = Path(sys.argv[1])
domain = sys.argv[2]
include_directive = sys.argv[3]
text = site_path.read_text()

pattern = re.compile(rf'(^\s*server_name\s+.*\b{re.escape(domain)}\b.*;\s*$)', re.MULTILINE)
new_text, count = pattern.subn(r'\1\n    ' + include_directive, text)

if count == 0:
    raise SystemExit(f"Nenhum server_name com {domain} encontrado em {site_path}")

site_path.write_text(new_text)
PY
fi

nginx -t
systemctl reload nginx

echo "Hardening aplicado em ${SITE_PATH}"
echo "Snippet instalado em ${SNIPPET_TARGET}"
