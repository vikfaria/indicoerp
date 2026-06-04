#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/indicoerp/repo}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/indicoerp}"
OPS_DIR="${OPS_DIR:-${BACKUP_DIR}/ops}"
APP_URL_VALUE="${APP_URL_VALUE:-${APP_URL:-https://staging.indicoerp.com}}"

PRE_DEPLOY_BACKUP_SCRIPT="${PRE_DEPLOY_BACKUP_SCRIPT:-${APP_DIR}/deploy/scripts/17_pre_deploy_backup_indicoerp.sh}"
RESTORE_VERIFY_SCRIPT="${RESTORE_VERIFY_SCRIPT:-${APP_DIR}/deploy/scripts/18_verify_restore_indicoerp.sh}"
HEALTHCHECK_SCRIPT="${HEALTHCHECK_SCRIPT:-${APP_DIR}/deploy/scripts/19_post_deploy_healthcheck_indicoerp.sh}"
SMOKE_SCRIPT="${SMOKE_SCRIPT:-${APP_DIR}/deploy/scripts/20_post_deploy_smoke_indicoerp.sh}"
K6_MATRIX_SCRIPT="${K6_MATRIX_SCRIPT:-${APP_DIR}/deploy/scripts/21_post_deploy_k6_matrix_indicoerp.sh}"

PRE_DEPLOY_BACKUP_ENV_FILE="${PRE_DEPLOY_BACKUP_ENV_FILE:-${BACKUP_DIR}/pre_deploy_backup_latest.env}"
RESTORE_VERIFY_ENV_FILE="${RESTORE_VERIFY_ENV_FILE:-${BACKUP_DIR}/restore_verify_latest.env}"
POST_DEPLOY_HEALTHCHECK_ENV_FILE="${POST_DEPLOY_HEALTHCHECK_ENV_FILE:-${OPS_DIR}/post_deploy_healthcheck_latest.env}"
POST_DEPLOY_SMOKE_ENV_FILE="${POST_DEPLOY_SMOKE_ENV_FILE:-${OPS_DIR}/post_deploy_smoke_latest.env}"
POST_DEPLOY_K6_MATRIX_ENV_FILE="${POST_DEPLOY_K6_MATRIX_ENV_FILE:-${OPS_DIR}/post_deploy_k6_matrix_latest.env}"
STAGING_VALIDATION_ENV_FILE="${STAGING_VALIDATION_ENV_FILE:-${OPS_DIR}/staging_validation_latest.env}"
STAGING_VALIDATION_LOG_FILE="${STAGING_VALIDATION_LOG_FILE:-${OPS_DIR}/staging_validation_latest.log}"

RUN_BACKUP_VERIFY="${RUN_BACKUP_VERIFY:-1}"
RUN_BUILD="${RUN_BUILD:-1}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"
RUN_SCE_SETUP="${RUN_SCE_SETUP:-1}"
RUN_TESTS="${RUN_TESTS:-1}"
RUN_HEALTHCHECK="${RUN_HEALTHCHECK:-1}"
RUN_SMOKE="${RUN_SMOKE:-1}"
RUN_K6_MATRIX="${RUN_K6_MATRIX:-1}"

LOG_SINCE="${LOG_SINCE:-30 min ago}"
REQUIRE_QUEUE="${REQUIRE_QUEUE:-1}"
SENSITIVE_PATHS_CSV="${SENSITIVE_PATHS_CSV:-/.env,/.git/config,/storage/logs/laravel.log,/composer.json}"
K6_DURATION="${K6_DURATION:-2m}"
K6_VUS_MATRIX="${K6_VUS_MATRIX:-25,50}"
K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-1}"
K6_AUTH_PATHS="${K6_AUTH_PATHS:-/dashboard,/dashboard/account,/dashboard/hrm,/account/reports/mozambique-financial-compliance-dashboard,/account/reports/mozambique-go-live-readiness,/sales-invoices,/purchase-invoices,/hrm/reports/modelo19-support,/hrm/reports/inss-guide,/hrm/reports/accounting-journal-lines}"
SMOKE_LOGIN_EMAIL="${SMOKE_LOGIN_EMAIL:-${K6_LOGIN_EMAIL:-}}"
SMOKE_LOGIN_PASSWORD="${SMOKE_LOGIN_PASSWORD:-${K6_LOGIN_PASSWORD:-}}"

mkdir -p "$OPS_DIR"
timestamp="$(date -u +%Y%m%d_%H%M%S)"
STAGING_VALIDATION_LOG_FILE="${OPS_DIR}/staging_validation_${timestamp}.log"
ln -sfn "$STAGING_VALIDATION_LOG_FILE" "${OPS_DIR}/staging_validation_latest.log"
if [ ! -d "$APP_DIR" ]; then
  echo "APP_DIR not found: $APP_DIR"
  exit 1
fi

cd "$APP_DIR"

exec > >(tee "$STAGING_VALIDATION_LOG_FILE") 2>&1

if [ ! -f "$PRE_DEPLOY_BACKUP_SCRIPT" ]; then
  echo "Backup wrapper not found: $PRE_DEPLOY_BACKUP_SCRIPT"
  exit 1
fi

if [ ! -f "$RESTORE_VERIFY_SCRIPT" ]; then
  echo "Restore verification wrapper not found: $RESTORE_VERIFY_SCRIPT"
  exit 1
fi

if [ ! -f "$HEALTHCHECK_SCRIPT" ]; then
  echo "Healthcheck wrapper not found: $HEALTHCHECK_SCRIPT"
  exit 1
fi

if [ ! -f "$SMOKE_SCRIPT" ]; then
  echo "Smoke wrapper not found: $SMOKE_SCRIPT"
  exit 1
fi

if [ ! -f "$K6_MATRIX_SCRIPT" ]; then
  echo "k6 matrix wrapper not found: $K6_MATRIX_SCRIPT"
  exit 1
fi

run_php_tests() {
  local label="$1"
  shift
  echo "==> $label"
  php artisan test --stop-on-failure "$@"
}

echo "==> Staging validation start"
echo "APP_DIR: $APP_DIR"
echo "APP_URL: $APP_URL_VALUE"
echo "BACKUP_DIR: $BACKUP_DIR"
echo "OPS_DIR: $OPS_DIR"
echo "LOG_FILE: $STAGING_VALIDATION_LOG_FILE"

if [ "$RUN_BACKUP_VERIFY" = "1" ]; then
  echo "==> Backup and restore verification"
  BACKUP_DIR="$BACKUP_DIR" \
  APP_DIR="$APP_DIR" \
  DB_HOST="${DB_HOST:-}" \
  DB_PORT="${DB_PORT:-}" \
  DB_NAME="${DB_NAME:-}" \
  DB_USER="${DB_USER:-}" \
  DB_PASS="${DB_PASS:-}" \
  MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-}" \
  MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-}" \
  MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}" \
  PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
  PRE_DEPLOY_BACKUP_LOG_FILE="${BACKUP_DIR}/pre_deploy_backup_latest.log" \
  bash "$PRE_DEPLOY_BACKUP_SCRIPT"

  BACKUP_DIR="$BACKUP_DIR" \
  APP_DIR="$APP_DIR" \
  DB_HOST="${DB_HOST:-}" \
  DB_PORT="${DB_PORT:-}" \
  DB_NAME="${DB_NAME:-}" \
  DB_USER="${DB_USER:-}" \
  DB_PASS="${DB_PASS:-}" \
  MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-}" \
  MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-}" \
  MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}" \
  PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
  RESTORE_VERIFY_ENV_FILE="$RESTORE_VERIFY_ENV_FILE" \
  RESTORE_VERIFY_LOG_FILE="${BACKUP_DIR}/restore_verify_latest.log" \
  bash "$RESTORE_VERIFY_SCRIPT"
fi

if [ "$RUN_BUILD" = "1" ] && [ -f "$APP_DIR/package.json" ]; then
  echo "==> Frontend build"
  if [ -f "$APP_DIR/package-lock.json" ] || [ -f "$APP_DIR/npm-shrinkwrap.json" ]; then
    (cd "$APP_DIR" && npm ci)
  else
    (cd "$APP_DIR" && npm install --no-audit --no-fund)
  fi
  (cd "$APP_DIR" && npm run build)
fi

if [ "$RUN_MIGRATIONS" = "1" ]; then
  echo "==> Migrations"
  (cd "$APP_DIR" && php artisan migrate --force --no-interaction)
fi

if [ "$RUN_SCE_SETUP" = "1" ]; then
  echo "==> SCE setup"
  (cd "$APP_DIR" && php artisan sce:setup --no-interaction --force)
fi

if [ "$RUN_TESTS" = "1" ]; then
  run_php_tests "Fiscal / faturacao suite" \
    tests/Feature/FiscalDocumentComplianceMatrixTest.php \
    tests/Feature/FiscalDocumentImmutabilityHardeningTest.php \
    tests/Feature/SalesInvoiceFiscalComplianceRulesTest.php \
    tests/Feature/PurchaseInvoiceFiscalComplianceRulesTest.php \
    tests/Feature/ImportProcessVatRateTest.php \
    tests/Feature/MozambiqueAccountingFiscalMapTest.php

  run_php_tests "Tesouraria / FX / compliance suite" \
    tests/Feature/AccountForeignCurrencyPaymentsTest.php \
    tests/Feature/BankStatementImportReconciliationTest.php \
    tests/Feature/BankAccountElectronicMoneyExemptionTest.php \
    tests/Feature/AccountPaymentIsolationTest.php \
    tests/Feature/MozambiqueCashClosingTest.php \
    tests/Feature/VendorAdvanceAndEmployeeLoanAccountingTest.php \
    tests/Feature/RetainerAdvanceSettlementTest.php

  run_php_tests "Contabilidade / SCE suite" \
    tests/Feature/AccountingTrialBalanceEndToEndTest.php \
    tests/Feature/AccountingClosingWorkflowTest.php \
    tests/Feature/FinancialStatementsEndToEndTest.php \
    tests/Feature/IrpcModel20EndToEndTest.php \
    tests/Feature/PgcImportValidationTest.php \
    tests/Feature/FixedAssetLifecycleTest.php \
    tests/Feature/CostCenterAnalysisReportTest.php \
    tests/Feature/InventoryCostingFifoTest.php \
    tests/Feature/AccountingCriticalAuditTrailTest.php

  run_php_tests "RH / payroll suite" \
    tests/Feature/MozambiquePayrollLegalTablesValidationTest.php \
    tests/Feature/HrmPayrollPaymentJournalFlowTest.php \
    tests/Feature/HrmLeaveBalanceRealBalanceTest.php \
    tests/Feature/MozambiqueNightWorkPayrollTest.php \
    tests/Feature/RecruitmentMozambiqueComplianceTest.php \
    tests/Feature/HrmDisciplinaryHarassmentOffboardingCrudTest.php \
    tests/Feature/HrmPayrollComplianceImportApiTest.php \
    tests/Feature/HrmPayrollSubmissionExportsTest.php \
    tests/Feature/MozambiqueLabourRulesTest.php

  run_php_tests "Readiness / segurança suite" \
    tests/Feature/MozambiqueGoLiveReadinessTest.php \
    tests/Feature/SceModuleAccessControlTest.php \
    tests/Feature/SyncFiscalCalendarCommandTest.php \
    tests/Feature/CompanyFinanceRoleSyncTest.php
fi

if [ "$RUN_HEALTHCHECK" = "1" ]; then
  echo "==> Post-deploy healthcheck"
  APP_DIR="$APP_DIR" \
  BACKUP_DIR="$BACKUP_DIR" \
  OPS_DIR="$OPS_DIR" \
  APP_URL="$APP_URL_VALUE" \
  LOG_SINCE="$LOG_SINCE" \
  REQUIRE_QUEUE="$REQUIRE_QUEUE" \
  SENSITIVE_PATHS_CSV="$SENSITIVE_PATHS_CSV" \
  PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
  POST_DEPLOY_HEALTHCHECK_ENV_FILE="$POST_DEPLOY_HEALTHCHECK_ENV_FILE" \
  bash "$HEALTHCHECK_SCRIPT"
fi

if [ "$RUN_SMOKE" = "1" ]; then
  if [ -z "$SMOKE_LOGIN_EMAIL" ] || [ -z "$SMOKE_LOGIN_PASSWORD" ]; then
    echo "SMOKE_LOGIN_EMAIL and SMOKE_LOGIN_PASSWORD are required for staging smoke."
    exit 1
  fi

  echo "==> Post-deploy smoke"
  APP_DIR="$APP_DIR" \
  BACKUP_DIR="$BACKUP_DIR" \
  OPS_DIR="$OPS_DIR" \
  K6_BASE_URL="$APP_URL_VALUE" \
  K6_DURATION="${K6_DURATION:-1m}" \
  K6_VUS="${K6_VUS:-1}" \
  K6_THINK_TIME_SECONDS="${K6_THINK_TIME_SECONDS:-0}" \
  K6_AUTH_PATHS="$K6_AUTH_PATHS" \
  K6_LOGIN_EMAIL="$SMOKE_LOGIN_EMAIL" \
  K6_LOGIN_PASSWORD="$SMOKE_LOGIN_PASSWORD" \
  PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
  POST_DEPLOY_HEALTHCHECK_ENV_FILE="$POST_DEPLOY_HEALTHCHECK_ENV_FILE" \
  POST_DEPLOY_SMOKE_ENV_FILE="$POST_DEPLOY_SMOKE_ENV_FILE" \
  bash "$SMOKE_SCRIPT"
fi

if [ "$RUN_K6_MATRIX" = "1" ]; then
  if [ -z "$SMOKE_LOGIN_EMAIL" ] || [ -z "$SMOKE_LOGIN_PASSWORD" ]; then
    echo "SMOKE_LOGIN_EMAIL and SMOKE_LOGIN_PASSWORD are required for staging k6 matrix."
    exit 1
  fi

  echo "==> Post-deploy k6 matrix"
  APP_DIR="$APP_DIR" \
  BACKUP_DIR="$BACKUP_DIR" \
  OPS_DIR="$OPS_DIR" \
  K6_BASE_URL="$APP_URL_VALUE" \
  K6_DURATION="$K6_DURATION" \
  K6_VUS_MATRIX="$K6_VUS_MATRIX" \
  K6_THINK_TIME_SECONDS="$K6_THINK_TIME_SECONDS" \
  K6_AUTH_PATHS="$K6_AUTH_PATHS" \
  K6_LOGIN_EMAIL="$SMOKE_LOGIN_EMAIL" \
  K6_LOGIN_PASSWORD="$SMOKE_LOGIN_PASSWORD" \
  PRE_DEPLOY_BACKUP_ENV_FILE="$PRE_DEPLOY_BACKUP_ENV_FILE" \
  POST_DEPLOY_SMOKE_ENV_FILE="$POST_DEPLOY_SMOKE_ENV_FILE" \
  POST_DEPLOY_K6_MATRIX_ENV_FILE="$POST_DEPLOY_K6_MATRIX_ENV_FILE" \
  bash "$K6_MATRIX_SCRIPT"
fi

{
  printf 'STAGING_VALIDATION_CREATED_AT_UTC=%q\n' "$(date -u +%FT%TZ)"
  printf 'STAGING_VALIDATION_APP_DIR=%q\n' "$APP_DIR"
  printf 'STAGING_VALIDATION_APP_URL=%q\n' "$APP_URL_VALUE"
  printf 'STAGING_VALIDATION_BACKUP_ENV_FILE=%q\n' "$PRE_DEPLOY_BACKUP_ENV_FILE"
  printf 'STAGING_VALIDATION_RESTORE_VERIFY_ENV_FILE=%q\n' "$RESTORE_VERIFY_ENV_FILE"
  printf 'STAGING_VALIDATION_HEALTHCHECK_ENV_FILE=%q\n' "$POST_DEPLOY_HEALTHCHECK_ENV_FILE"
  printf 'STAGING_VALIDATION_SMOKE_ENV_FILE=%q\n' "$POST_DEPLOY_SMOKE_ENV_FILE"
  printf 'STAGING_VALIDATION_K6_MATRIX_ENV_FILE=%q\n' "$POST_DEPLOY_K6_MATRIX_ENV_FILE"
  printf 'STAGING_VALIDATION_LOG_FILE=%q\n' "$STAGING_VALIDATION_LOG_FILE"
  printf 'STAGING_VALIDATION_RUN_BACKUP_VERIFY=%q\n' "$RUN_BACKUP_VERIFY"
  printf 'STAGING_VALIDATION_RUN_BUILD=%q\n' "$RUN_BUILD"
  printf 'STAGING_VALIDATION_RUN_MIGRATIONS=%q\n' "$RUN_MIGRATIONS"
  printf 'STAGING_VALIDATION_RUN_SCE_SETUP=%q\n' "$RUN_SCE_SETUP"
  printf 'STAGING_VALIDATION_RUN_TESTS=%q\n' "$RUN_TESTS"
  printf 'STAGING_VALIDATION_RUN_HEALTHCHECK=%q\n' "$RUN_HEALTHCHECK"
  printf 'STAGING_VALIDATION_RUN_SMOKE=%q\n' "$RUN_SMOKE"
  printf 'STAGING_VALIDATION_RUN_K6_MATRIX=%q\n' "$RUN_K6_MATRIX"
} > "$STAGING_VALIDATION_ENV_FILE"

chmod 600 "$STAGING_VALIDATION_ENV_FILE" "$STAGING_VALIDATION_LOG_FILE" 2>/dev/null || true
ln -sfn "$STAGING_VALIDATION_LOG_FILE" "${OPS_DIR}/staging_validation_latest.log"

echo "OK: staging validation metadata: $STAGING_VALIDATION_ENV_FILE"
