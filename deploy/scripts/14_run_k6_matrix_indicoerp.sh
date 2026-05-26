#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
K6_BASE_URL="${K6_BASE_URL:-https://indicoerp.com}"
K6_DURATION="${K6_DURATION:-2m}"
K6_VUS_MATRIX="${K6_VUS_MATRIX:-10,25,50}"
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-1}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SMOKE_SCRIPT="${SCRIPT_DIR}/08_run_k6_indicoerp_smoke.sh"

if [ -d "$APP_DIR" ]; then
  cd "$APP_DIR"
else
  APP_DIR="$(pwd)"
fi

OUTPUT_DIR="${OUTPUT_DIR:-${APP_DIR}/storage/logs/performance/k6}"

mkdir -p "$OUTPUT_DIR"

timestamp="$(date +%F_%H%M%S)"
summary_file="${OUTPUT_DIR}/k6_matrix_summary_${timestamp}.md"
touch "$summary_file"

overall_status=0

echo "# K6 Matrix Summary (${timestamp})" >>"$summary_file"
echo "" >>"$summary_file"
echo "| VUs | Status | avg | p95 | max | fail rate | req/s | Log |" >>"$summary_file"
echo "| --- | --- | --- | --- | --- | --- | --- | --- |" >>"$summary_file"

IFS=',' read -r -a vus_list <<< "$K6_VUS_MATRIX"

for vus in "${vus_list[@]}"; do
  vus="$(echo "$vus" | xargs)"
  [ -n "$vus" ] || continue

  log_file="${OUTPUT_DIR}/k6_${vus}vus_${timestamp}.log"

  echo "==> Running k6 smoke: ${vus} VUs for ${K6_DURATION}"
  set +e
  K6_BASE_URL="$K6_BASE_URL" \
  K6_VUS="$vus" \
  K6_DURATION="$K6_DURATION" \
  K6_THINK_TIME_SECONDS="$K6_THINK_TIME_SECONDS" \
  bash "$SMOKE_SCRIPT" | tee "$log_file"
  run_exit=$?
  set -e

  status="OK"
  if [ "$run_exit" -ne 0 ]; then
    status="FAIL"
    overall_status=1
  fi

  duration_line="$(grep -E 'http_req_duration\.*:' "$log_file" | tail -n 1 || true)"
  failed_line="$(grep -E 'http_req_failed\.*:' "$log_file" | tail -n 1 || true)"
  req_line="$(grep -E 'http_reqs\.*:' "$log_file" | tail -n 1 || true)"

  avg="$(echo "$duration_line" | sed -E 's/.*avg=([^ ]+).*/\1/' || true)"
  p95="$(echo "$duration_line" | sed -E 's/.*p\(95\)=([^ ]+).*/\1/' || true)"
  max="$(echo "$duration_line" | sed -E 's/.*max=([^ ]+).*/\1/' || true)"
  fail_rate="$(echo "$failed_line" | sed -E 's/.*: *([^ ]+).*/\1/' || true)"
  req_rate="$(echo "$req_line" | sed -E 's/.*: *[0-9]+ *([^ ]+).*/\1/' || true)"

  [ -n "$avg" ] || avg="n/a"
  [ -n "$p95" ] || p95="n/a"
  [ -n "$max" ] || max="n/a"
  [ -n "$fail_rate" ] || fail_rate="n/a"
  [ -n "$req_rate" ] || req_rate="n/a"

  echo "| ${vus} | ${status} | ${avg} | ${p95} | ${max} | ${fail_rate} | ${req_rate} | ${log_file} |" >>"$summary_file"
done

echo
echo "Resumo gravado em: ${summary_file}"
cat "$summary_file"

exit "$overall_status"
