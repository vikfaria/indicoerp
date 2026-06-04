# Runbook de Validação de Staging — ERPGo/SysGest Moçambique

Este runbook define a sequência recomendada para validar uma release em `staging/preprod` antes do go-live em produção.

O objectivo é validar, no mínimo:

- migrations e setup fiscal;
- backup/restore;
- suites críticas de faturação, tesouraria, contabilidade/SCE, RH e readiness;
- healthcheck;
- smoke funcional com login real;
- smoke de carga controlada (`k6` 25/50 VUs);
- UAT/E2E manual com massa representativa.

Se ainda não preparaste o ambiente, consulta primeiro [STAGING_SETUP_RUNBOOK_PT.md](./STAGING_SETUP_RUNBOOK_PT.md).

## 1) Variáveis mínimas

Definir antes de começar:

```bash
cd /var/www/indicoerp/repo

export APP_URL='http://staging.indicoerp.local'
export DB_PASS='COLOCAR_A_PASSWORD'
export SMOKE_LOGIN_EMAIL='seu-utilizador@dominio.com'
export SMOKE_LOGIN_PASSWORD='sua-password'
```

Se o staging usar um container MySQL:

```bash
export MYSQL_CONTAINER_NAME='indicoerp_mysql'
```

## 2) Execução recomendada

### 2.1 Validação completa automatizada

O caminho preferencial é executar o wrapper de staging:

```bash
bash deploy/scripts/22_staging_validation_indicoerp.sh
```

Este comando executa, nesta ordem:

1. backup pré-validação;
2. verificação de restore em base temporária;
3. `npm run build` quando o projecto tem frontend;
4. `php artisan migrate --force --no-interaction`;
5. `php artisan sce:setup --no-interaction --force`;
6. suites críticas de testes;
7. healthcheck operacional;
8. smoke funcional;
9. `k6` controlado em `25,50` VUs.

As evidências ficam em `BACKUP_DIR/ops/` e no ficheiro `staging_validation_latest.env`.

### 2.2 Sequência manual equivalente

Se preferir correr passo a passo:

```bash
bash deploy/scripts/17_pre_deploy_backup_indicoerp.sh
```

```bash
bash deploy/scripts/18_verify_restore_indicoerp.sh
```

```bash
php artisan migrate --force --no-interaction
```

```bash
php artisan sce:setup --no-interaction --force
```

```bash
php artisan test --stop-on-failure \
  tests/Feature/FiscalDocumentComplianceMatrixTest.php \
  tests/Feature/FiscalDocumentImmutabilityHardeningTest.php \
  tests/Feature/SalesInvoiceFiscalComplianceRulesTest.php \
  tests/Feature/PurchaseInvoiceFiscalComplianceRulesTest.php \
  tests/Feature/ImportProcessVatRateTest.php \
  tests/Feature/MozambiqueAccountingFiscalMapTest.php
```

```bash
php artisan test --stop-on-failure \
  tests/Feature/AccountForeignCurrencyPaymentsTest.php \
  tests/Feature/BankStatementImportReconciliationTest.php \
  tests/Feature/BankAccountElectronicMoneyExemptionTest.php \
  tests/Feature/AccountPaymentIsolationTest.php \
  tests/Feature/MozambiqueCashClosingTest.php \
  tests/Feature/VendorAdvanceAndEmployeeLoanAccountingTest.php \
  tests/Feature/RetainerAdvanceSettlementTest.php
```

```bash
php artisan test --stop-on-failure \
  tests/Feature/AccountingTrialBalanceEndToEndTest.php \
  tests/Feature/AccountingClosingWorkflowTest.php \
  tests/Feature/FinancialStatementsEndToEndTest.php \
  tests/Feature/IrpcModel20EndToEndTest.php \
  tests/Feature/PgcImportValidationTest.php \
  tests/Feature/FixedAssetLifecycleTest.php \
  tests/Feature/CostCenterAnalysisReportTest.php \
  tests/Feature/InventoryCostingFifoTest.php \
  tests/Feature/AccountingCriticalAuditTrailTest.php
```

```bash
php artisan test --stop-on-failure \
  tests/Feature/MozambiquePayrollLegalTablesValidationTest.php \
  tests/Feature/HrmPayrollPaymentJournalFlowTest.php \
  tests/Feature/HrmLeaveBalanceRealBalanceTest.php \
  tests/Feature/MozambiqueNightWorkPayrollTest.php \
  tests/Feature/RecruitmentMozambiqueComplianceTest.php \
  tests/Feature/HrmDisciplinaryHarassmentOffboardingCrudTest.php \
  tests/Feature/HrmPayrollComplianceImportApiTest.php \
  tests/Feature/HrmPayrollSubmissionExportsTest.php \
  tests/Feature/MozambiqueLabourRulesTest.php
```

```bash
php artisan test --stop-on-failure \
  tests/Feature/MozambiqueGoLiveReadinessTest.php \
  tests/Feature/SceModuleAccessControlTest.php \
  tests/Feature/SyncFiscalCalendarCommandTest.php \
  tests/Feature/CompanyFinanceRoleSyncTest.php
```

```bash
APP_URL='http://staging.indicoerp.local' \
LOG_SINCE='30 min ago' \
bash deploy/scripts/19_post_deploy_healthcheck_indicoerp.sh
```

```bash
SMOKE_LOGIN_EMAIL='seu-utilizador@dominio.com' \
SMOKE_LOGIN_PASSWORD='sua-password' \
bash deploy/scripts/20_post_deploy_smoke_indicoerp.sh
```

```bash
SMOKE_LOGIN_EMAIL='seu-utilizador@dominio.com' \
SMOKE_LOGIN_PASSWORD='sua-password' \
bash deploy/scripts/21_post_deploy_k6_matrix_indicoerp.sh
```

## 3) E2E funcional em staging

O que está coberto por testes automatizados:

- `E2E-001` vendas → documento fiscal → journal → IVA → SAF-T;
- `E2E-002` compras → retenções → journal;
- `E2E-003` payroll → IRPS/INSS → journal;
- `E2E-004` POS/faturação rápida → conformidade fiscal;
- `E2E-005` exportação/FX → repatriamento/dossier cambial.

O que continua a exigir validação manual:

- `E2E-006` UAT do cliente com massa representativa.

Checklist manual mínimo:

1. Criar/editar cliente e fornecedor representativos.
2. Emitir factura de venda e validar número, série, IVA e hash.
3. Emitir factura de compra e validar retenção/IVA/reconciliação.
4. Processar folha de salário e confirmar IRPS, INSS e journal.
5. Executar um fluxo de caixa/banco e reconciliar extracto.
6. Exportar SAF-T e confirmar geração do XML.
7. Validar o painel de readiness e registar aprovação legal/comercial.

## 4) Critério de passagem para produção

Só avançar quando:

- backup/restore em staging foi verificado;
- migrations e `sce:setup` correram sem erro;
- suites críticas passaram;
- healthcheck, smoke e `k6` ficaram verdes;
- UAT manual foi aceite;
- evidências ficaram guardadas nos ficheiros `.env` e logs de `ops/`.
