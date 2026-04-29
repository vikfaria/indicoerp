# Estado de Implementacao - Mercado Mocambicano

Data de actualizacao: 29 de Abril de 2026 (rev. tecnica complementar)
Referencias:
- `docs/plano-implementacao-mocambique-2026-04-24.md`
- `docs/analise-adaptacao-mocambique-2026-04-24.md`

## Sequencia oficial em execucao
1. Fase 0 - Estabilizacao tecnica
2. Fase 1 - Seguranca e multiempresa
3. Fase 2 - Fiscalizacao documental
4. Fase 4 - Payroll e RH
5. Fase 3 - Accounting/Tax
6. Fase 5 - Integracoes locais
7. Fase 6 - QA, piloto e go-live

## Estado por fase

### Fase 0 - Estabilizacao tecnica
Status: CONCLUIDA

Concluido:
- Build frontend estabilizado (`npm run build` a passar).
- Hardening de dependencias JS com auditoria de producao limpa (`npm audit --omit=dev --audit-level=high` com 0 vulnerabilidades).
- Ajuste para testes nao carregarem assets frontend pesados (`@routes` e `@vite` desligados em `runningUnitTests()`).
- Correcao de fallback de SSL attribute no `config/database.php` para compatibilidade com PHP 8.5.
- Correcao de bloqueador de migration MySQL em `mz_tax_account_mappings` (nomes explicitos curtos para foreign keys).
- Hardening de testes com `memory_limit=512M` no `phpunit.xml` para execucao integral da suite local.

Observacoes:
- `composer` nao esta disponivel no ambiente para `composer audit`.
- Deprecations de vendor continuam visiveis em PHP 8.5 (nao bloqueiam execucao funcional).
- Validacao automatizada actual: `php artisan test` concluido sem falhas de assercao (suite com deprecations de vendor em PHP 8.5).

### Fase 1 - Seguranca e multiempresa
Status: CONCLUIDA (MVP)

Concluido:
- Isolamento multiempresa validado via testes de tenant para vendas, compras, devolucoes, propostas, POS e transferencias.
- Trilho minimo de auditoria transversal implementado:
  - tabela `audit_trails`
  - model `AuditTrail`
  - servico `AuditTrailService`
  - observer `ModelAuditTrailObserver`
  - provider `AuditTrailServiceProvider`
  - cobertura de teste em `tests/Feature/AuditTrailTest.php`

Cobertura actual de auditoria:
- `SalesInvoice`
- `SalesInvoiceReturn`
- `SalesProposal`
- `PurchaseInvoice`
- `PurchaseReturn`
- `Transfer`
  - `Workdo\Pos\Models\Pos`
  - `Workdo\Hrm\Models\Payroll`
- Correcao de consistencia em permissao de impressao de devolucoes de venda (`print-sales-return-invoices`).

### Fase 2 - Fiscalizacao documental
Status: CONCLUIDA

Concluido:
- Configuracao fiscal de empresa com `NUIT` e prefixos documentais validada por teste.
- Snapshots fiscais de emissor e contraparte nos documentos nucleares validados por teste.
- Relatorios/account endpoints com escopo e identidade fiscal cobertos por teste.
- Metadados fiscais transversais adicionados aos documentos nucleares e notas (`document_type`, `document_series`, `document_sequence`, `establishment_id`).
- Regras de series por estabelecimento activadas com fallback por empresa (`*_series_warehouse_{id}` e `*_series`).
- Estados de submissao/validacao fiscal com referencia operacional implementados (`pending/submitted/validated/rejected/not_required`) com endpoints dedicados.
- Camada de anulacao/rectificacao fiscal implementada com regra obrigatoria de `rectification_reference` para documentos ja validados.
- Cobertura de teste dedicada em `tests/Feature/FiscalDocumentComplianceTest.php`.

### Fase 4 - Payroll e RH
Status: CONCLUIDA (escopo tecnico implementado; validacao real depende de execucao operacional)

Concluido:
- Estruturas de parametrizacao legal criadas:
  - `mz_irps_tables` e `mz_irps_brackets` (escaloes por vigencia)
  - `mz_inss_rates` (taxas trabalhador/empregador por vigencia)
  - `mz_minimum_wages` (salario minimo por sector e vigencia)
- Servico `MozambiquePayrollTaxService` implementado para:
  - calculo IRPS por escaloes activos
  - calculo INSS trabalhador/empregador
  - validacao de conformidade com salario minimo
- Integracao no processamento de payroll (`PayrollController`):
  - calculo e persistencia de IRPS/INSS por colaborador
  - totais de IRPS/INSS no cabecalho de payroll
  - sinalizacao de conformidade de salario minimo por payslip
- Gestao administrativa no UI para tabelas legais:
  - pagina `Mozambique Payroll Compliance` em HRM > System Setup
  - CRUD de tabelas/escaloes IRPS
  - CRUD de taxas INSS
  - CRUD de salarios minimos por sector
- Exportacao operacional de payroll:
  - endpoint `hrm.payrolls.mozambique-map`
  - exportacao CSV com bruto/liquido, IRPS, INSS e conformidade de salario minimo
- Cobertura de teste em `tests/Feature/MozambiquePayrollTaxServiceTest.php`.
- Regras laborais locais configuraveis para horas extra e licencas:
  - politica operacional em `HRM > System Setup > Mozambique Payroll Compliance` (limites diario/mensal/anual de overtime, aviso minimo e limite consecutivo de licenca, contagem de fins-de-semana e feriados)
  - validacao backend aplicada em `OvertimeController` e `LeaveApplicationController`
  - correccao da validacao de licencas para usar o intervalo solicitado (em vez da data corrente)
- Cobertura de teste para politica laboral em `tests/Feature/MozambiqueLabourRulesTest.php`.

Pendente critico:
- Nenhum no escopo tecnico.

Dependencia operacional externa (fora do codigo):
- executar validacao funcional real por sector durante o piloto e anexar evidencias.

### Fase 3 - Accounting/Tax
Status: CONCLUIDA (escopo tecnico implementado; validacao local final depende de execucao operacional)

Concluido:
- Mapeamento fiscal contabilistico configuravel por empresa:
  - tabela `mz_tax_account_mappings`
  - UI em Account > System Setup > Mozambique Tax Mapping
  - vigencia por data e activacao por registo
- Integracao do mapeamento no motor contabilistico automatico (`JournalService`):
  - IVA de vendas/notas/POS usa conta de IVA liquidado configurada (com fallback)
  - IVA de compras/notas usa conta de IVA dedutivel configurada (com fallback)
- Relatorio operacional fiscal:
  - endpoint `account.reports.mozambique-fiscal-map`
  - exportacao CSV `account.reports.mozambique-fiscal-map.export`
  - consolidacao de vendas, compras, notas de credito/debito e IVA liquido
- Relatorio complementar de declaracao/submissao fiscal:
  - endpoint `account.reports.mozambique-fiscal-submission-register`
  - exportacao CSV `account.reports.mozambique-fiscal-submission-register.export`
  - consolidacao mensal por tipo documental e estado de submissao (`pending/submitted/validated/rejected/not_required`)
- Fecho fiscal mensal/anual com rastreabilidade:
  - tabela `mz_fiscal_closings` (periodo, estado, motivos, utilizador, timestamps)
  - snapshot fiscal no momento do fecho (tax summary + mapa fiscal + resumo de journals)
  - UI em Reports > Fiscal Closing para fechar e reabrir periodos
  - bloqueio de novos `journal_entries` em periodos fechados via `JournalEntryObserver`
- Registo de validacoes locais reais de piloto (integrado no readiness):
  - tabela `mz_pilot_validation_cases` com dominio `payroll/accounting`
  - campos de evidencias (`executed_at`, `evidence_ref`, resultado e notas)
  - endpoints CRUD em `account.reports.mozambique-go-live-readiness.validation-cases.*`
  - checks criticos `payroll.sector.real_cases` e `accounting.local.real_cases`
  - criterios formais `payroll_real_cases_validated` e `accounting_real_cases_validated`

Pendente critico:
- Nenhum no escopo tecnico.

Dependencia operacional externa (fora do codigo):
- executar validacao contabilistica local real durante o piloto e anexar evidencias.

### Fase 5 - Integracoes locais
Status: CONCLUIDA (escopo planeado da fase implementado)

Concluido:
- Importacao de extracto bancario por CSV no modulo Account:
  - endpoint `account.bank-transactions.import-csv`
  - template `account.bank-transactions.template`
  - suporte a colunas padrao e aliases comuns (`date/data`, `type/tipo`, `amount/valor`, etc.)
  - deduplicacao por conta/data/tipo/valor/referencia/descricao
- Reconciliacao assistida:
  - endpoint `account.bank-transactions.auto-reconcile`
  - matching automatico com `customer_payments` e `vendor_payments` por referencia, valor e janela de datas
  - marcacao automatica de `reconciliation_status`
- UI operacional em Bank Transactions:
  - bloco `Import & Reconcile` com upload, descarga do template e parametros de reconciliacao
- Pagamentos locais por mobile money (M-Pesa, e-Mola, mKesh) no modulo Account:
  - suporte de dados em `customer_payments` e `vendor_payments` (`payment_method`, `mobile_money_provider`, `mobile_money_number`)
  - validacao condicional backend para obrigar provedor e numero quando `payment_method=mobile_money`
  - formulários de criacao de pagamentos actualizados com seleccao de metodo e dados de carteira movel
  - listagens/detalhes de pagamentos com visualizacao do metodo e provedor
  - descricao operacional de movimentos bancarios inclui metodo/provedor para rastreabilidade
- Exportacao operacional para declaracao de IVA (Mozambique):
  - novo endpoint JSON `account.reports.mozambique-vat-declaration`
  - exportacao CSV `account.reports.mozambique-vat-declaration.export`
  - consolidacao mensal de IVA de vendas/compras/notas e apuramento liquido por periodo
  - nova aba de relatorio `Mozambique VAT Declaration` no modulo Reports

Pendente critico:
- Nenhum no escopo da fase.

### Fase 6 - QA, piloto e go-live
Status: CONCLUIDA (escopo tecnico implementado; fechamento final depende apenas de execucao operacional real)

Concluido:
- Painel tecnico de prontidao de go-live no modulo Account > Reports:
  - endpoint `account.reports.mozambique-go-live-readiness`
  - aba `Go-Live Readiness` com estado geral (`ready/attention/blocked`) e checks detalhados
- Checklist automatizado cobre itens criticos e operacionais:
  - existencia de tabelas de localizacao/compliance
  - mapeamento fiscal activo
  - setup legal de payroll (IRPS/INSS/salario minimo)
  - historico de fecho fiscal
  - backlog de submissao fiscal
  - reconciliacao bancaria pendente antiga
  - actividade recente de audit trail
  - integridade de registos mobile money
  - disponibilidade das rotas de exportacao de declaracao de IVA
- Cobertura de teste para permissao e estrutura do endpoint em `MozambiqueGoLiveReadinessTest`.
- Correcoes de robustez no fluxo base de conta/perfil para manter estabilidade da suite:
  - `ProfileController::destroy` implementado.
  - `ProfileUpdateRequest::authorize` definido para utilizador autenticado.
  - Fluxos de registo e reset password resilientes quando nao existe superadmin inicial.
- Checklist legal/comercial final operacionalizado no proprio readiness:
  - registo de status e data de revisao legal/fiscal local
  - registo de status e data de prontidao comercial
  - registo de estado de piloto (incluindo numero de empresas e data de conclusao)
  - registo de aprovacao formal de go-live
  - endpoint de actualizacao `account.reports.mozambique-go-live-readiness.attestation`
- Criterio formal de go-live calculado automaticamente no payload:
  - `formal_go_live_criteria` com `critical_checks_passed`, `pilot_completed`, `formal_approval_granted` e `recommended_for_launch`
- Checklist E2E operacional integrado no readiness:
  - registo dos 4 cenarios obrigatorios (`sales`, `purchase`, `pos`, `payroll`)
  - data de conclusao E2E e notas
  - novo check critico `qa.e2e_business_scenarios`
  - criterio formal actualizado com `e2e_scenarios_completed`
- Registo operacional de empresas piloto integrado:
  - tabela `mz_pilot_companies`
  - endpoints CRUD para pilot companies no readiness
  - secao UI `Pilot Company Registry` em Reports > Go-Live Readiness
  - check critico `pilot.registry.companies`
  - criterio formal actualizado com `pilot_registry_populated`
- Evidencia formal de piloto real por empresa integrada:
  - campos de validacao por empresa piloto (`company_nuit`, `validation_result`, `validation_signed_at`, `validation_evidence_ref`, `validation_notes`)
  - check critico `pilot.real_companies.evidence`
  - criterio formal actualizado com `pilot_real_companies_validated`
- Registo de casos reais de validacao local integrado:
  - tabela `mz_pilot_validation_cases` (dominios `payroll` e `accounting`)
  - endpoints CRUD `account.reports.mozambique-go-live-readiness.validation-cases.*`
  - secao UI `Pilot Validation Cases (Payroll/Accounting)` em Reports > Go-Live Readiness
  - checks criticos `payroll.sector.real_cases` e `accounting.local.real_cases`
  - criterios formais `payroll_real_cases_validated` e `accounting_real_cases_validated`
- Validacoes funcionais finais integradas no readiness:
  - validacao setorial de payroll (salario minimo + regras laborais) com estado/data/notas
  - validacao contabilistica local (mapas e declaracoes) com estado/data/notas
  - checks criticos `payroll.sector.validation.final` e `accounting.local.validation.final`
  - criterio formal actualizado com `payroll_sector_validation_completed` e `accounting_local_validation_completed`

Pendente critico:
- Nenhum no escopo tecnico.

Dependencia operacional externa (fora do codigo):
- executar e registar piloto real com empresas locais para obter estado final `ready` em ambiente produtivo.
