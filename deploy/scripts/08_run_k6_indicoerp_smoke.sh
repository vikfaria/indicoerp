#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCENARIO_FILE="${SCRIPT_DIR}/08_k6_indicoerp_smoke.js"

if [ ! -f "$SCENARIO_FILE" ]; then
  echo "ERRO: scenario k6 nao encontrado em ${SCENARIO_FILE}"
  exit 1
fi

if command -v k6 >/dev/null 2>&1; then
  exec k6 run "$SCENARIO_FILE"
fi

if command -v docker >/dev/null 2>&1; then
  exec docker run --rm -i \
    -e K6_BASE_URL="${K6_BASE_URL:-https://indicoerp.com}" \
    -e K6_LOGIN_EMAIL="${K6_LOGIN_EMAIL:-}" \
    -e K6_LOGIN_PASSWORD="${K6_LOGIN_PASSWORD:-}" \
    -e K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard}" \
    -e K6_VUS="${K6_VUS:-10}" \
    -e K6_DURATION="${K6_DURATION:-2m}" \
    -e K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-1}" \
    -v "${SCRIPT_DIR}:/scripts" \
    grafana/k6 run "/scripts/08_k6_indicoerp_smoke.js"
fi

echo "ERRO: k6 nao instalado e docker indisponivel."
exit 1
