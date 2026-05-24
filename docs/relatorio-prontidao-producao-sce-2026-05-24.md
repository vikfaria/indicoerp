# Relatório de Prontidão de Produção — SCE Moçambique

Data: 24 de Maio de 2026  
Projeto: ERPGo/SysGest (`/sysgest`)  
Escopo: validação técnica de prontidão de produção para o pacote SCE (RF001-RF096), com base no código real e execução local.

Referência complementar RF a RF (matriz detalhada): [relatorio-sce-mocambique-rf001-rf096-2026-05-22.md](./relatorio-sce-mocambique-rf001-rf096-2026-05-22.md).  
Este documento de 24/05/2026 é o **addendum de prontidão de produção**, com foco em evidência atual, bloqueadores e plano de execução.

## 1. Resultado executivo (Go/No-Go)

Estado recomendado: **NO-GO** para produção fiscal crítica neste momento.

Justificativa:
- Existe uma base SCE ampla (migrations, models, serviços, rotas, UI e comandos).
- Existem **bloqueadores de integração** entre controller/model/schema em pontos críticos (perfil fiscal, diários, fecho mensal).
- A alegação de “100% testes aprovados” **não pôde ser comprovada** neste ambiente devido a ligação MySQL indisponível.
- SAF-T MZ exporta XML, mas ainda sem validação XSD oficial no fluxo de exportação.

## 2. O que está implementado (confirmado)

### 2.1 Estrutura SCE no código
- Rotas SCE com módulos de fiscal/períodos/diários/PGC/IVA/IRPC/retenções/ativos/relatórios:
  - `routes/web.php` (grupo `Route::prefix('sce')`).
- Migrações dedicadas para SCE:
  - `company_fiscal_profiles`, `accounting_periods`, `pgc_account_catalogs`, `accounting_journals`, `vat codes`, `hash fiscal`, `irpc/withholding`, `fixed_assets`, `import_processes`, `cost_centers`, `fiscal_calendar`.
- Comando de setup inicial:
  - `php artisan sce:setup` em [SceSetupCommand.php](../app/Console/Commands/SceSetupCommand.php).

### 2.2 Plano de contas e fiscalidade base
- Importador PGC e catálogo JSON:
  - [PgcImportService.php](../app/Services/PgcImportService.php)
  - [pgc_nirf_accounts.json](../database/data/pgc_nirf_accounts.json)
- Códigos IVA e tipos documentais fiscais padrão:
  - [MzVatCode.php](../app/Models/MzVatCode.php)
  - [FiscalDocumentType.php](../app/Models/FiscalDocumentType.php)
- Motor de IVA, IRPC e retenções:
  - [VatCalculationService.php](../app/Services/VatCalculationService.php)
  - [IrpcCalculationService.php](../app/Services/IrpcCalculationService.php)
  - [WithholdingTaxService.php](../app/Services/WithholdingTaxService.php)

### 2.3 Imutabilidade e hash fiscal
- Hash SHA-256 e cadeia por série:
  - [FiscalHashService.php](../app/Services/FiscalHashService.php)
- Observer de documentos fiscais com bloqueios de edição pós-posting:
  - [FiscalDocumentObserver.php](../app/Observers/FiscalDocumentObserver.php)
  - registo em [AppServiceProvider.php](../app/Providers/AppServiceProvider.php)

### 2.4 SAF-T e relatórios SCE
- Exportador SAF-T com namespace MZ:
  - [SaftExportService.php](../app/Services/SaftExportService.php)
- Relatórios financeiros base (Balanço, DR, DFC):
  - [FinancialStatementsService.php](../app/Services/FinancialStatementsService.php)

## 3. Bloqueadores técnicos encontrados (críticos)

### B01 — Desalinhamento de campos no Perfil Fiscal (persistência inconsistente)
- Controller valida/salva `tax_regime`, `activity_code`, `nirf_classification`:
  - [FiscalProfileController.php](../app/Http/Controllers/FiscalProfileController.php)
- Model e migration usam `fiscal_regime`, `economic_activity_code`, `entity_classification`:
  - [CompanyFiscalProfile.php](../app/Models/CompanyFiscalProfile.php)
  - [2026_05_22_300000_create_company_fiscal_profiles_table.php](../database/migrations/2026_05_22_300000_create_company_fiscal_profiles_table.php)
- Impacto: parte dos dados fiscais não é guardada no schema correto.

### B02 — `sce:setup` usa atributo fiscal incorreto
- `SceSetupCommand` cria perfil com `tax_regime` e imprime `profile->tax_regime`, mas a coluna/modelo é `fiscal_regime`:
  - [SceSetupCommand.php](../app/Console/Commands/SceSetupCommand.php)
- Impacto: setup “verde” pode mascarar configuração fiscal incompleta.

### B03 — Controller de Diários usa campos que não existem na tabela
- Controller usa `prefix`, `default_debit_account`, `default_credit_account`, `orderBy('prefix')`:
  - [AccountingJournalController.php](../app/Http/Controllers/AccountingJournalController.php)
- Schema real usa `code`, `numbering_prefix`, `default_debit_account_id`, `default_credit_account_id`:
  - [2026_05_22_300010_create_accounting_journals_table.php](../database/migrations/2026_05_22_300010_create_accounting_journals_table.php)
- Impacto: CRUD de diários com risco alto de falha funcional.

### B04 — Fecho mensal: inconsistência entre Controller, Service e tabela checklist
- Controller chama assinatura de service incompatível (`year/month` vs `periodId/companyId`):
  - [AccountingJournalController.php](../app/Http/Controllers/AccountingJournalController.php)
  - [MonthlyClosingService.php](../app/Services/MonthlyClosingService.php)
- Controller consulta colunas inexistentes em checklist (`fiscal_year`, `period_number`, `check_order`) e atualiza `is_completed` inexistente:
  - [AccountingJournalController.php](../app/Http/Controllers/AccountingJournalController.php)
  - [2026_05_22_300011_create_recurring_templates_and_closing_checklists.php](../database/migrations/2026_05_22_300011_create_recurring_templates_and_closing_checklists.php)
  - [MonthlyClosingChecklist.php](../app/Models/MonthlyClosingChecklist.php)
- Impacto: fluxo de fecho mensal não está operacional de ponta a ponta.

### B05 — PGC “0-9” incompleto e validação estrutural não cobre Classe 9
- Catálogo atual inclui classes 0..8, sem classe 9:
  - [pgc_nirf_accounts.json](../database/data/pgc_nirf_accounts.json)
- `validateStructure()` exige apenas classes 1..8:
  - [PgcImportService.php](../app/Services/PgcImportService.php)
- Impacto: cobertura parcial de RF006/RF077-RF079 (analítica/gestão).

### B06 — SAF-T sem validação XSD oficial no pipeline
- Serviço gera XML, mas não executa validação `schemaValidate`.
- Inclui secção custom `PurchaseInvoices` com comentário de não aderência estrita:
  - [SaftExportService.php](../app/Services/SaftExportService.php)
- Impacto: risco de rejeição no validador oficial AT.

### B07 — Lançamentos recorrentes sem agendamento ativo
- Comando existe:
  - [ProcessRecurringJournals.php](../app/Console/Commands/ProcessRecurringJournals.php)
- Scheduler não agenda execução:
  - [Kernel.php](../app/Console/Kernel.php)
- Impacto: RF013 implementado no código, mas não operacional em produção.

### B08 — Lançamento de salários ainda simplificado no motor legacy
- `createPayrollJournal()` lança apenas líquido (`Dr gasto salário / Cr banco`) e usa conta legacy `5200`:
  - [JournalService.php](../packages/workdo/Account/src/Services/JournalService.php)
- Impacto: RF047 incompleto para passivos IRPS/INSS no fluxo legacy.

### B09 — Testes de conformidade não executáveis neste ambiente
- Execução local:
  - `php artisan test tests/Feature/MozambiqueGoLiveReadinessTest.php tests/Feature/FiscalDocumentComplianceTest.php`
- Resultado: falhas por `SQLSTATE[HY000] [2002] Connection refused` (MySQL indisponível).
- Impacto: não há evidência reprodutível local de “100% aprovado”.

## 4. Cobertura funcional por macro-módulo (RF001-RF096)

Leitura rápida:
- **Implementado/forte**: base técnica existe e opera em parte relevante.
- **Parcial**: existe estrutura, mas com gaps funcionais, de integração ou de conformidade.
- **Pendente**: não encontrado fluxo completo no sistema atual.

### Módulo 2 — Configuração inicial (RF001-RF004)
- Estado: **Parcial**
- Gap dominante: perfil fiscal com campos desalinhados (B01/B02) e bloqueios ainda não consolidados em todo o sistema.

### Módulo 3 — Plano de contas (RF005-RF009)
- Estado: **Parcial**
- Gap dominante: classe 9 não fechada e validação estrutural incompleta (B05).

### Módulo 4 — Lançamentos e diários (RF010-RF014)
- Estado: **Parcial**
- Gap dominante: inconsistências no CRUD/fecho mensal e recorrências sem scheduler (B03/B04/B07).

### Módulos 5 e 6 — Clientes/Fornecedores (RF015-RF020)
- Estado: **Parcial**
- Gap dominante: validações fiscais mais estritas no ponto de faturação/dedutibilidade e parametrização de retenções por entidade.

### Módulo 7 — Faturação (RF021-RF026)
- Estado: **Parcial**
- Pontos fortes: tipos documentais fiscais e hash chain base.
- Gap dominante: robustez de imutabilidade completa em todos os fluxos e cobertura integral de autofacturação operacional.

### Módulo 8 — IVA (RF027-RF032)
- Estado: **Parcial-avançado**
- Pontos fortes: códigos IVA, cálculo periódico, contas PGC IVA.
- Gap dominante: regras finas de dedutibilidade/validade fiscal e validação formal de mapa para submissão AT.

### Módulo 9 — IRPC (RF033-RF039)
- Estado: **Parcial**
- Pontos fortes: configuração IRPC, ajustes fiscais, cálculo base.
- Gap dominante: tributação autónoma e mais-valias plenamente integradas no ciclo operacional.

### Módulo 10 — IRPS e retenções (RF040-RF043)
- Estado: **Parcial**
- Pontos fortes: regras/lançamentos de retenção.
- Gap dominante: cobertura de todos cenários legais + guias completas.

### Módulo 11 — INSS e salários (RF044-RF047)
- Estado: **Parcial**
- Pontos fortes: cálculo INSS/IRPS e tabelas.
- Gap dominante: lançamento contabilístico integral da folha no fluxo principal (B08).

### Módulo 12 — Inventário (RF048-RF052)
- Estado: **Parcial**
- Pontos fortes: serviço FIFO/camadas existe.
- Gap dominante: integração operacional end-to-end com compras/vendas/ajustes e cobertura total de ativos biológicos.

### Módulo 13 — Ativos fixos (RF053-RF057)
- Estado: **Parcial**
- Pontos fortes: cadastro, depreciação e lançamentos base.
- Gap dominante: alienação/mais-valias/reavaliação/imparidades em fluxo completo de negócio.

### Módulo 14 — Tesouraria (RF058-RF062)
- Estado: **Parcial**
- Pontos fortes: contas bancárias e reconciliação base.
- Gap dominante: cobertura total de caixa operacional e câmbio com diferenças cambiais.

### Módulo 15 — Relatórios financeiros (RF063-RF068)
- Estado: **Parcial**
- Pontos fortes: Balanço/DR/DFC em estrutura SCE.
- Gap dominante: notas às contas completas, variações de capital próprio e analítica avançada.

### Módulo 16 — Obrigações fiscais (RF069-RF072)
- Estado: **Parcial**
- Pontos fortes: calendário fiscal base.
- Gap dominante: pacote formal de Modelo 20/declaração anual/guias finais.

### Módulo 17 — SAF-T e faturação eletrónica (RF073-RF076)
- Estado: **Parcial**
- Gap dominante: validação XSD oficial e workflow formal de submissão/histórico (B06).

### Módulo 18 — Analítica (RF077-RF079)
- Estado: **Parcial**
- Gap dominante: classe 9 + distribuição automática e relatórios de rentabilidade.

### Módulo 19 — Importações e aduanas (RF080-RF082)
- Estado: **Parcial**
- Pontos fortes: modelagem de import process e landed cost base.
- Gap dominante: bloqueio por licença/documentação obrigatória no processo.

### Módulo 20 — Auditoria e segurança (RF083-RF087)
- Estado: **Parcial**
- Pontos fortes: trilha já existente em módulos core.
- Gap dominante: cobertura total de ações críticas + runbook formal de backup/restore.

### Módulo 21 — Fecho contabilístico (RF088-RF090)
- Estado: **Parcial (com bloqueio funcional)**
- Gap dominante: correção do fluxo controller/service/schema (B04).

### Módulo 22 — Alertas e conformidade (RF091-RF092)
- Estado: **Parcial**
- Gap dominante: centralização de validações e alertas automáticos em produção.

### Módulo 23 — Administração do sistema (RF093-RF096)
- Estado: **Parcial-avançado**
- Pontos fortes: multiempresa funcional.
- Gap dominante: parametrização fiscal sem código em todos os impostos e import/export completo de dados fiscais.

## 5. Backlog de implementação (prioridade de produção)

## Fase A — Correções bloqueadoras (obrigatória antes de piloto final)
RF foco: RF001-RF014, RF073-RF076, RF088-RF090

1. Alinhar campo fiscal `tax_regime` -> `fiscal_regime` e `activity_code` -> `economic_activity_code` em controller/UI/command.
2. Corrigir CRUD de diários para schema real (`code`, `numbering_prefix`, `*_account_id`).
3. Corrigir fluxo de fecho mensal (controller e service na mesma assinatura, checklist por `accounting_period_id`).
4. Implementar validação XSD oficial no export SAF-T (pré-download) e remover/normalizar elementos fora do schema.
5. Fechar classe 9 no catálogo PGC e no validador estrutural.
6. Garantir execução de testes em ambiente CI com MySQL disponível.

Critério de saída da Fase A:
- Testes SCE verdes em CI.
- `sce:setup` idempotente sem warnings de campos inválidos.
- Fecho mensal executável via UI sem erro de coluna/método.
- SAF-T validado contra XSD oficial em pipeline.

## Fase B — Conformidade fiscal integral
RF foco: RF015-RF047, RF069-RF072

1. Reforçar inalterabilidade e retificação formal em todos documentos fiscais finalizados.
2. Completar dedutibilidade IVA com regras de NUIT/documento/fornecedor.
3. Completar guias e mapas operacionais de IRPC/IRPS/retenções.
4. Consolidar Modelo 20 e declaração anual em saída auditável.

Critério de saída:
- Relatórios fiscais reconciliados com razão/balancete.
- Casos de retificação e rejeição fiscal cobertos por testes.

## Fase C — Operação contabilística avançada
RF foco: RF048-RF068, RF077-RF082

1. Integrar FIFO/custos em fluxos reais de compra/venda/ajustes.
2. Completar ciclo de ativos fixos (alienação, mais/menos-valias, imparidade, reavaliação).
3. Fechar analítica (classe 9 + centros de custo + relatórios por projeto/cliente/produto).
4. Completar aduanas com bloqueio por licença/documentação.

Critério de saída:
- Demonstrações financeiras e analíticas reconciliadas com movimentos reais.

## Fase D — Hardening operacional de produção
RF foco: RF083-RF087, RF091-RF096

1. Cobertura total de auditoria em ações críticas.
2. Scheduler de recorrências e tarefas fiscais.
3. Runbook de backup/restore testado.
4. Import/export fiscal completo e monitoramento de submissões.

Critério de saída:
- Operação contínua com alertas, recuperação e trilha de auditoria completa.

## 6. Passos imediatos recomendados

1. Corrigir os bloqueadores B01-B06 numa branch de estabilização SCE.
2. Configurar ambiente de teste MySQL dedicado para CI e rodar suíte SCE.
3. Validar SAF-T no validador oficial AT (ambiente de testes) só após B06.
4. Reexecutar checklist de go-live e assinar aprovação formal com evidências.

---

## Anexo — Comandos úteis

```bash
# setup base
php artisan migrate --force
php artisan sce:setup --company=<ID_EMPRESA> --framework=pgc_nirf --year=2026

# testes SCE (exemplo)
php artisan test tests/Feature/MozambiqueGoLiveReadinessTest.php \
  tests/Feature/FiscalDocumentComplianceTest.php \
  tests/Feature/MozambiqueAccountingFiscalMapTest.php

# export SAF-T
php artisan tinker --execute="app(\App\Services\SaftExportService::class)->exportToFile(<ID_EMPRESA>, '2026-01-01', '2026-12-31');"
```
