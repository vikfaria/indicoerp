# Relatorio tecnico de execucao go-live Mocambique - 2026-06-03

## 1. Resumo executivo

Estado apos esta execucao: **apto tecnicamente para preparar release**, mas **nao deve ir para producao sem validacao externa e execucao no ambiente real**.

Foram fechados os bloqueios tecnicos imediatos identificados no relatorio consolidado:

- migrations validadas em MySQL 8 temporario com `migrate:fresh`;
- IVA de importacao deixou de estar hardcoded e passou a usar tabela legal/configuracao;
- backup/restore ganhou script operacional versionado, runbook e gate no readiness;
- readiness passou a exigir evidencia de backup/restore;
- readiness passou a falhar quando SAF-T XSD e obrigatorio mas nao esta configurado;
- testes alvo de RH, payroll, contabilizacao, faturacao, tesouraria, fiscalidade, permissoes e SAF-T passaram;
- build frontend passou.

Decisao recomendada: **Apto com restricoes para staging/release candidate**. Para producao, ainda exige migracao real, backup/restore em producao/staging, validacao legal/fiscal externa, preenchimento do readiness e smoke test pos-deploy.

## 2. Plano por prioridade

| Prioridade | Item | Estado | Evidencia |
| --- | --- | --- | --- |
| P0 | Validar migrations em MySQL real/equivalente | Fechado em ambiente temporario MySQL 8 | `php artisan migrate:fresh --force --no-interaction` passou no container `sysgest_mysql_migration_check` |
| P0 | Parametrizar IVA hardcoded de importacao | Fechado | `ImportProcess` resolve `MzVatCode` tipo `import`/codigo `IMP`, fallback `SCE_DEFAULT_IMPORT_VAT_RATE` |
| P0 | Higiene de release para artefactos gerados | Fechado parcialmente | `.gitignore` ignora `.DS_Store`, `storage/app/private`, `storage/app/saft` |
| P1 | Backup/restore operacional | Fechado tecnicamente | `deploy/scripts/16_backup_restore_indicoerp.sh`, runbook atualizado, restore de verificacao em DB temporaria com 283 tabelas |
| P1 | Readiness com evidencia de DR/backup | Fechado | `operations.backup_restore.final`, criterios `backup_restore_verified` e UI React |
| P1 | SAF-T XSD readiness | Fechado tecnicamente | readiness falha se `SAFT_MZ_REQUIRE_XSD_VALIDATION=true` e XSD ausente |
| P1 | RH critico: cessacao, payroll, dados sensiveis | Validado por testes | 56 testes RH alvo passaram |
| P1 | Financeiro/fiscal/permissoes/E2E tecnico | Validado por testes | 75 testes fiscais/financeiros alvo passaram nas execucoes finais |
| P2 | Validacao legal/fiscal oficial | Pendente externo | Exige contabilista/fiscalista/AT, schema SAF-T oficial e tabelas legais oficiais |
| P2 | Readiness formal com empresa real | Pendente externo | Exige preenchimento de atestacoes, piloto, E2E real e aprovacao |
| P3 | Limpeza completa de worktree | Pendente operacional | Ha muitas alteracoes anteriores nao commitadas; requer revisao e commit/release organizado |

## 3. Alteracoes implementadas nesta execucao

### 3.1 IVA de importacao

Ficheiros:

- `app/Models/ImportProcess.php`
- `config/sce.php`
- `database/migrations/2026_06_03_100000_add_import_vat_rate_to_import_processes_table.php`
- `tests/Feature/ImportProcessVatRateTest.php`

Resultado:

- adicionada coluna `import_vat_rate`;
- calculo usa taxa explicita no processo quando preenchida;
- se vazia, usa `mz_vat_codes` ativo com codigo `IMP` ou tipo `import`;
- se nao existir tabela legal, usa fallback `config('sce.vat.default_import_rate')`;
- teste cobre taxa legal, override manual e fallback.

### 3.2 Backup/restore e readiness

Ficheiros:

- `deploy/scripts/16_backup_restore_indicoerp.sh`
- `deploy/PRODUCTION_RUNBOOK_PT.md`
- `packages/workdo/Account/src/Services/ReportService.php`
- `packages/workdo/Account/src/Http/Controllers/ReportsController.php`
- `packages/workdo/Account/src/Resources/js/Pages/Reports/MozambiqueGoLiveReadiness.tsx`
- `tests/Feature/MozambiqueGoLiveReadinessTest.php`

Resultado:

- novo script suporta `backup`, `verify`, `restore`;
- suporta host MySQL ou `MYSQL_CONTAINER_NAME`;
- restore destrutivo exige `CONFIRM_RESTORE=YES`;
- verify pode importar dump para `VERIFY_DB_NAME` temporario;
- readiness exige `backup_restore_status`, data e referencia de evidencia;
- recomendacao de lancamento depende de `backup_restore_verified`;
- UI permite registar evidencia de backup/restore.

### 3.3 SAF-T readiness

Ficheiros:

- `packages/workdo/Account/src/Services/ReportService.php`
- `tests/Feature/MozambiqueGoLiveReadinessTest.php`

Resultado:

- novo check critico `exports.saft_xsd_validation_config`;
- se `SAFT_MZ_REQUIRE_XSD_VALIDATION=true`, o readiness falha quando `SAFT_MZ_XSD_PATH` esta vazio ou aponta para ficheiro inexistente;
- isto nao substitui validacao oficial, mas impede falso "ready" com configuracao incompleta.

## 4. Comandos executados e evidencia

### 4.1 Migrations MySQL

```bash
APP_ENV=testing APP_DEBUG=false DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3308 DB_DATABASE=sysgest_migration_check DB_USERNAME=sysgest_user DB_PASSWORD=*** CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync php artisan migrate:fresh --force --no-interaction
```

Resultado: passou. Ultima migration executada:

```text
2026_06_03_100000_add_import_vat_rate_to_import_processes_table  DONE
```

`migrate:status` confirmou `[1] Ran` para as migrations recentes, incluindo `2026_06_03_100000_add_import_vat_rate_to_import_processes_table`.

### 4.2 Testes PHP

```bash
php artisan test tests/Feature/ImportProcessVatRateTest.php tests/Feature/SceSetupCommandTest.php
```

Resultado: 4 passed, 16 assertions.

```bash
php artisan test tests/Feature/MozambiqueGoLiveReadinessTest.php tests/Feature/ImportProcessVatRateTest.php
```

Resultado: 11 passed, 271 assertions.

```bash
php artisan test tests/Feature/MozambiqueGoLiveReadinessTest.php tests/Feature/MozambiqueFiscalExportsHistoryTest.php
```

Resultado: 11 passed, 293 assertions.

```bash
php artisan test tests/Feature/MozambiqueLabourRulesTest.php tests/Feature/HrmCompensationIsolationTest.php tests/Feature/HrmPayrollCancellationControlTest.php tests/Feature/HrmPayrollAccountingJournalTest.php tests/Feature/HrmPayrollAccountingExportsTest.php tests/Feature/HrmEmployeeAccessIsolationTest.php
```

Resultado: 56 passed, 275 assertions.

```bash
php artisan test tests/Feature/FiscalDocumentComplianceTest.php tests/Feature/AccountPaymentIsolationTest.php tests/Feature/AccountReportsPermissionHardeningTest.php tests/Feature/SceTaxDeclarationEndpointsTest.php tests/Feature/MozambiqueAccountingFiscalMapTest.php tests/Feature/MozambiqueFiscalClosingTest.php tests/Feature/CompanyFiscalSettingsTest.php
```

Resultado: 44 passed, 389 assertions.

```bash
php artisan test tests/Feature/AccountCounterpartyFiscalClassificationTest.php tests/Feature/MozambiqueCounterpartyNuitValidationTest.php tests/Feature/MozambiqueCashClosingTest.php tests/Feature/MozambiqueFiscalComplianceAlertServiceTest.php tests/Feature/AccountNotePrintAccessTest.php tests/Feature/PurchaseInvoiceTenantIsolationTest.php tests/Feature/SalesReturnTenantIsolationTest.php tests/Feature/PosFiscalComplianceTest.php
```

Resultado: 31 passed, 185 assertions.

### 4.3 Build frontend

```bash
npm run build
```

Resultado: passou. Avisos residuais:

- Browserslist desatualizado;
- warning CSS preexistente: `Expected identifier but found "-"`.

Nao bloquearam o build.

### 4.4 Backup/restore

Sintaxe e dry-run:

```bash
bash -n deploy/scripts/16_backup_restore_indicoerp.sh
DRY_RUN=1 DB_NAME=test DB_USER=test DB_PASS=test bash deploy/scripts/16_backup_restore_indicoerp.sh backup
```

Resultado: passou.

Backup real no MySQL temporario via container:

```bash
BACKUP_DIR=/tmp/sysgest_backup_restore_check MYSQL_CONTAINER_NAME=sysgest_mysql_migration_check DB_NAME=sysgest_migration_check DB_USER=sysgest_user DB_PASS=*** MYSQL_ADMIN_USER=root MYSQL_ADMIN_PASS=*** INCLUDE_APP_ARCHIVE=0 bash deploy/scripts/16_backup_restore_indicoerp.sh backup
```

Resultado:

- dump: `/tmp/sysgest_backup_restore_check/db_sysgest_migration_check_20260603_190102.sql.gz`;
- manifesto: `/tmp/sysgest_backup_restore_check/backup_sysgest_migration_check_20260603_190102.manifest`.

Restore de verificacao:

```bash
MYSQL_CONTAINER_NAME=sysgest_mysql_migration_check DB_NAME=sysgest_migration_check DB_USER=sysgest_user DB_PASS=*** MYSQL_ADMIN_USER=root MYSQL_ADMIN_PASS=*** RESTORE_FILE=/tmp/sysgest_backup_restore_check/db_sysgest_migration_check_20260603_190102.sql.gz VERIFY_DB_NAME=sysgest_restore_check bash deploy/scripts/16_backup_restore_indicoerp.sh verify
```

Resultado:

```text
OK: restore verification imported 283 table(s).
OK: temporary verification database dropped.
```

## 5. Pendencias que ainda bloqueiam producao sem ressalvas

1. Executar `php artisan migrate --force` no staging/producao real e guardar saida.
2. Executar `deploy/scripts/16_backup_restore_indicoerp.sh backup` no ambiente real.
3. Executar `verify` com `VERIFY_DB_NAME` no ambiente real usando credencial admin com `CREATE DATABASE`/`DROP DATABASE`.
4. Registar no readiness a evidencia de backup/restore: status, data, manifesto e notas.
5. Validar SAF-T com XSD/validador oficial da AT ou parecer fiscal documentado.
6. Configurar `SAFT_MZ_REQUIRE_XSD_VALIDATION=true` e `SAFT_MZ_XSD_PATH` se a politica de producao exigir bloqueio por XSD.
7. Validar tabelas legais fiscais/laborais com consultor local: IVA, IRPS, INSS, ADT, retencoes, quotas, indemnizacoes, periodos probatorios e prazos.
8. Preencher readiness: legal, comercial, piloto, payroll real, contabilidade real, E2E real e aprovacao formal.
9. Organizar worktree, rever todos ficheiros modificados/untracked e criar commit(s) de release.
10. Executar deploy, healthcheck, smoke HTTP, login real e smoke dos fluxos criticos em producao.

## 6. Riscos residuais

- SAF-T tecnico nao equivale a certificacao fiscal oficial.
- Testes automatizados cobrem cenarios principais, mas nao substituem UAT com dados reais de uma empresa mocambicana.
- O worktree esta grande e inclui alteracoes de varias fases; release deve ser revisto antes de commit.
- Restore real precisa credenciais admin corretas; sem isso o backup e apenas arquivo, nao recuperacao comprovada.
- A decisao legal final depende de validacao humana externa.

## 7. Conclusao

Os bloqueios tecnicos imediatos foram tratados. O sistema tem base tecnica forte para release candidate, com migrations validadas em MySQL, DR operacional versionado, readiness reforcado, IVA de importacao parametrizado e testes alvo passando.

Ainda nao e recomendavel declarar go-live final sem restricoes. O proximo passo seguro e preparar commit/release, executar as mesmas validacoes em staging/producao, preencher o readiness com evidencias reais e obter validacao legal/fiscal formal.
