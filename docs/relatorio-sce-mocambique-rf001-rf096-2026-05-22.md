# Relatório de Gap Analysis SCE Moçambique RF001-RF096

Data: 22 de Maio de 2026  
Âmbito: análise técnica do código actual do ERPGo/SysGest neste repositório.  
Fonte normativa usada: lista de requisitos funcionais fornecida pelo cliente. Este relatório não substitui validação jurídica/fiscal por contabilista certificado ou consultor fiscal.

## 1. Conclusão executiva

O sistema actual é um ERP SaaS genérico com módulos relevantes já implementados: multiempresa, permissões, clientes, fornecedores, facturação, compras, POS, bancos, reconciliação bancária, plano de contas, lançamentos, relatórios contabilísticos, stock básico, RH/folha salarial e algumas adaptações para Moçambique.

Apesar disso, o sistema ainda não pode ser tratado como plenamente conforme ao SCE moçambicano. O núcleo contabilístico continua assente num plano de contas genérico por intervalos `1000-5999`, enquanto os requisitos pedem PGC moçambicano com classes `0-9`, suporte PGC-NIRF/PGC-PE, regras fiscais parametrizáveis, ciclos de fecho, imutabilidade fiscal, assinatura/hash, IVA avançado, IRPC, retenções, SAF-T MZ e relatórios legais.

O que já existe reduz bastante o esforço, mas a implementação deve ser feita como uma camada de conformidade Moçambique sobre o ERP, não apenas como tradução de labels. Traduzir “Chart of Accounts” e tipos de contas é necessário para UX, mas não resolve conformidade contabilística.

## 2. Evidência técnica observada

Principais áreas existentes:

| Área | Evidência no código | Observação |
|---|---|---|
| Configuração da empresa | `app/Http/Controllers/SettingController.php` | Nome, endereço, contacto, país, moeda/default settings, NUIT e séries documentais. Falta cadastro fiscal completo. |
| NUIT | `app/Support/MozambiqueTaxNumber.php`, requests de cliente/fornecedor | Validação de 9 dígitos e normalização. |
| Plano de contas | `packages/workdo/Account/src/Database/Migrations/2025_09_22_101716_create_chart_of_accounts_table.php` | Tem código, nome, tipo, pai, nível, saldo normal, saldos. Falta PGC-MZ formal, conta de movimento e parametrização fiscal completa. |
| Lançamentos | `packages/workdo/Account/src/Models/JournalEntry.php`, `JournalService.php` | Numeração `JE-YYYY-###`, partidas dobradas e validação débito/crédito. |
| Fecho fiscal | `database/migrations/2026_04_28_221000_create_mz_fiscal_closings_table.php`, `JournalEntryObserver.php` | Bloqueia criação de lançamentos em períodos fechados. Falta período contabilístico mensal estruturado e bloqueio transversal a documentos. |
| Facturação fiscal | `FiscalDocumentComplianceService.php`, migrations fiscais de 2026-04 | Campos de série, sequência, submissão fiscal, cancelamento e snapshots. Falta hash/assinatura e bloqueio total de edição pós-emissão. |
| IVA/SAF-T | `packages/workdo/Account/src/Services/ReportService.php` | Há declaração IVA, mapa fiscal e export SAF-T XML. Export usa namespace `PT_1.04_01`; precisa validação SAF-T MZ oficial. |
| Payroll Moçambique | `MozambiquePayrollTaxService.php`, migrations `mz_irps_*`, `mz_inss_rates` | IRPS por tabela, INSS 3%/4%, salário mínimo por sector e export de mapa. Falta integração contabilística completa da folha. |
| Auditoria | `AuditTrailServiceProvider.php`, `AuditTrailService.php` | Auditoria em documentos críticos seleccionados. Falta cobertura total de operações críticas. |
| Relatórios contabilísticos | `packages/workdo/DoubleEntry/src/Services/BalanceSheetService.php`, `ReportService.php` | Balanço, DR, balancete, razão, fluxo de caixa. Layout e classes são genéricos, não SCE/PGC-MZ. |
| Inventário | `ProductService`, `warehouse_stocks`, `TransferController` | Artigos, categorias, unidades, impostos, armazéns e stock quantitativo. Falta FIFO, custo médio e ledger de stock. |
| Bancos | `bank_accounts`, `bank_transactions`, rotas de reconciliação | Contas bancárias com conta GL e reconciliação CSV. |

## 3. Matriz RF001-RF096

Legenda de estado:

- Implementado: existe funcionalidade próxima do requisito.
- Parcial: existe base técnica, mas faltam campos, regras, validações ou cobertura legal.
- Não implementado: não foi encontrada funcionalidade específica.

| RF | Estado actual | Evidência/resumo | Backlog recomendado |
|---|---|---|---|
| RF001 Cadastro da empresa | Parcial | Settings têm nome, endereço, contactos, país, NUIT, séries e moeda. | Criar perfil fiscal da empresa com actividade económica, regime fiscal, tipo de entidade, ano fiscal, dados bancários estruturados e alvará/licença. |
| RF002 Classificação da entidade | Não implementado | Não há classificação grande/média/pequena/ISPC. | Adicionar tabela `company_fiscal_profiles` com classificação, critérios e histórico. |
| RF003 Referencial contabilístico | Não implementado | Não há selector PGC-NIRF/PGC-PE/ISPC. | Criar catálogo de referenciais e regra de sugestão automática pela classificação da entidade. |
| RF004 Exercício contabilístico | Parcial | Existe `mz_fiscal_closings` e bloqueio de lançamentos fechados. | Criar períodos mensais `accounting_periods` com estados aberto/em fecho/fechado e aplicar bloqueio a todos os documentos contabilísticos. |
| RF005 Plano Geral de Contas | Parcial | CRUD de plano de contas existe. | Importador PGC-MZ, versionamento por referencial e validação de estrutura moçambicana. |
| RF006 Classes obrigatórias PGC | Não implementado | Relatórios usam classes genéricas `1000-5999`. | Implementar classes 0-9 do PGC e mapear relatórios para SCE. |
| RF007 Hierarquia de contas | Parcial | `parent_account_id` e `level` existem. | Formalizar níveis: classe, conta, subconta, analítica e movimento. |
| RF008 Bloqueio de contas não movimentáveis | Não implementado | Não há flag clara `is_movement_account`. | Adicionar flag de conta movimento e impedir lançamentos em contas sintéticas. |
| RF009 Parametrização fiscal das contas | Parcial | Existe `mz_tax_account_mappings` para IVA/retenção/IRPC. | Expandir conta fiscal: tipo de imposto, código IVA, dedutibilidade, rubrica DF, Modelo 20, SAF-T e centro de custo obrigatório. |
| RF010 Partidas dobradas | Parcial | `JournalService` valida débito igual a crédito. | Garantir validação também na UI/API manual, documento suporte obrigatório e bloqueio por período. |
| RF011 Tipos de diário | Parcial | `entry_type` e `reference_type` existem. | Criar tabela de diários configuráveis: caixa, bancos, compras, vendas, salários, regularizações, abertura, fecho, activos fixos e fiscal. |
| RF012 Numeração automática de lançamentos | Implementado | `JournalEntry::generateJournalNumber()` gera `JE-YYYY-###`; campo é único. | Tornar sequência por empresa/exercício/diário com controlo concorrente transaccional. |
| RF013 Lançamentos recorrentes | Não implementado | Não foi encontrada agenda de lançamentos recorrentes. | Criar templates recorrentes e job mensal com aprovação antes de publicar. |
| RF014 Anexação de documentos | Parcial | Há suporte genérico de media e anexos em alguns módulos. | Criar anexos específicos em lançamentos com tipo documental e obrigatoriedade por diário. |
| RF015 Cadastro de clientes | Parcial | Cliente tem empresa, contacto, NUIT, moradas e condições. | Adicionar tipo de cliente, regime IVA, país fiscal estruturado e conta contabilística associada. |
| RF016 Contas a receber | Parcial | Facturas, pagamentos, saldos, ageing e notas de crédito existem. | Completar adiantamentos, extracto legal por cliente e alertas de vencimento. |
| RF017 Integração vendas-contabilidade | Parcial | `JournalService` gera lançamentos para vendas e pagamentos. | Remapear para PGC-MZ, IVA Classe 4 e regras por tipo de documento. |
| RF018 Cadastro de fornecedores | Parcial | Fornecedor tem empresa, contacto, NUIT, moradas e termos. | Adicionar país fiscal, regime, retenção na fonte, acordo dupla tributação e conta associada. |
| RF019 Contas a pagar | Parcial | Facturas de compra, pagamentos, notas de débito/crédito e ageing existem. | Implementar retenções, adiantamentos formais e validações fiscais antes de dedução. |
| RF020 Validação de NUIT | Parcial | NUIT é validado em settings/clientes/fornecedores. | Validar NUIT no momento da factura e condicionar dedução de IVA/retenções. |
| RF021 Emissão de facturas | Parcial | Facturas têm número, data, cliente, itens, taxa e totais. | Adicionar motivo de isenção, snapshots obrigatórios e regras fiscais por estado definitivo. |
| RF022 Documentos comerciais | Parcial | Factura, pagamentos/recibos, notas crédito/débito, propostas e POS existem. | Implementar factura-recibo formal, guias de remessa/transporte e autofactura. |
| RF023 Inalterabilidade | Parcial | Há cancelamento fiscal e estados de submissão. | Bloquear edição/apagamento de documentos finalizados; correcções só por documento rectificativo. |
| RF024 Numeração sequencial | Parcial | Existem prefixos, séries e sequências documentais. | Reforçar unicidade por empresa/série/tipo, cronologia e bloqueio contra gaps não justificados. |
| RF025 Assinatura/hash fiscal | Não implementado | Não foi encontrado hash fiscal de documentos. | Implementar hash encadeado por série, payload canónico e armazenamento imutável. |
| RF026 Autofacturação | Não implementado | Não há documento de autofactura. | Criar tipo documental `self_billing_invoice` com regras de fornecedor informal/regime especial. |
| RF027 Taxas de IVA | Parcial | Há impostos/taxes simples em ProductService. | Criar tabela de códigos IVA MZ: normal, zero, isento, não sujeito, reverse charge, importação e digitais. |
| RF028 Cálculo automático IVA | Parcial | Totais de imposto são calculados em vendas/compras e relatório IVA. | Separar IVA liquidado, suportado, dedutível, não dedutível, a pagar e a recuperar. |
| RF029 Contabilização automática IVA | Parcial | `mz_tax_account_mappings` define contas de IVA output/input. | Mapear contas correctas da Classe 4 PGC-MZ e regras por código IVA. |
| RF030 Declaração periódica IVA | Parcial | Existe rota/CSV de declaração IVA Moçambique. | Adaptar campos ao modelo oficial, operações isentas/não sujeitas e regularizações. |
| RF031 Reverse charge serviços digitais | Não implementado | Não há autoliquidação simultânea. | Criar tipo de operação reverse charge com lançamento simultâneo IVA suportado/liquidado. |
| RF032 Dedutibilidade IVA | Parcial | Há validação NUIT básica. | Criar motor de dedutibilidade por fornecedor, documento, despesa e código IVA. |
| RF033 Resultado contabilístico | Parcial | DR genérica calcula receita/despesa por `4000-5999`. | Recalcular com Classes 6, 7 e 8 do PGC-MZ. |
| RF034 Correcções fiscais | Não implementado | Não há modelo de correcções fiscais. | Criar `tax_adjustments` para acrescer/deduzir, diferenças temporárias/permanentes e benefícios. |
| RF035 Matéria colectável | Não implementado | Não há apuramento IRPC ajustado. | Implementar cálculo resultado contabilístico + correcções fiscais. |
| RF036 Cálculo IRPC | Não implementado | Existe só conta IRPC em mappings. | Criar parametrização de taxas IRPC, regimes especiais e cálculo anual. |
| RF037 Pagamentos por conta | Não implementado | Não há calendário/cálculo Maio-Julho-Setembro. | Implementar obrigações de pagamentos por conta e reconciliação com pagamentos efectuados. |
| RF038 Tributação autónoma | Não implementado | Não há classificação dessas despesas. | Criar regras para despesas não documentadas/confidenciais/NUIT inválido e cálculo autónomo. |
| RF039 Mais-valias | Não implementado | Não há módulo de activos fixos/alienação. | Implementar alienação de activos e impacto fiscal. |
| RF040 Retenções na fonte | Parcial | Mappings têm contas de retenção, mas não motor fiscal. | Criar motor de retenções por tipo de rendimento, país, taxa e documento. |
| RF041 Retenções por fornecedor | Não implementado | Fornecedor não tem campos fiscais de retenção/DDT. | Expandir cadastro de fornecedor com regime, país, rendimento e acordo dupla tributação. |
| RF042 Guias de retenção | Não implementado | Não há mapas/guias específicos. | Criar relatório mensal de retenções, liquidação e export. |
| RF043 IRPS em salários | Parcial | Payroll calcula IRPS por tabelas configuráveis. | Adicionar dependentes, benefícios em espécie e regras completas de remunerações tributáveis. |
| RF044 Cadastro de trabalhadores | Parcial | Empregado tem dados pessoais, cargo/departamento, salário, NUIT e banco. | Adicionar número INSS, dependentes, tipo contrato fiscal e dados bancários completos. |
| RF045 Processamento salarial | Parcial | Folha calcula salário, extras, deduções, IRPS, INSS e líquido. | Completar benefícios, dependentes, subsídios tributáveis/não tributáveis e mapas legais. |
| RF046 Cálculo INSS | Implementado | Serviço usa 3% trabalhador e 4% empregador por defeito, configurável. | Validar vigência e guardar base de incidência por processamento. |
| RF047 Lançamento folha salarial | Parcial | `JournalService` tem rotina de pagamento líquido. | Gerar lançamento completo de salários, encargos patronais, IRPS e INSS a pagar. |
| RF048 Gestão de artigos | Parcial | Produto tem código/SKU, descrição, unidade, categoria, imposto e stock. | Adicionar conta contabilística, armazém padrão e método de valorização. |
| RF049 FIFO/custo médio | Não implementado | Stock actual é quantitativo. | Criar ledger de movimentos e camadas de custo FIFO/custo médio. |
| RF050 Movimentos de stock | Parcial | Há stock por armazém e transferências. | Implementar entradas, saídas, ajustes, quebras, devoluções, inventário físico e auditoria de custo. |
| RF051 Integração stock-contabilidade | Parcial | Existem lançamentos para compras, COGS e transferências. | Recalcular por método de valorização e suportar regularizações, imparidades e produção. |
| RF052 Activos biológicos | Não implementado | Não há módulo de activos biológicos. | Criar módulo opcional com justo valor menos custos de venda. |
| RF053 Activos fixos | Não implementado | Não foi encontrado módulo de activos fixos instalado. | Criar cadastro de activos com conta, localização, responsável e centro de custo. |
| RF054 Depreciações/amortizações | Não implementado | Não há cálculo fiscal de depreciação. | Implementar planos de depreciação contabilísticos/fiscais e lançamentos automáticos. |
| RF055 Imparidade de activos | Não implementado | Não há registo de imparidade. | Adicionar perdas/reversões e impacto nos relatórios. |
| RF056 Reavaliação de activos | Não implementado | Não há justo valor/reavaliação. | Criar eventos de reavaliação e reservas quando aplicável. |
| RF057 Alienação de activos | Não implementado | Não há baixa/venda de activos. | Criar venda, abate, transferência, ganho/perda, IVA e lançamento contabilístico. |
| RF058 Contas bancárias | Implementado | Banco tem `gl_account_id` associado ao plano de contas. | Validar que conta bancária só aceita Classe 1 no PGC-MZ. |
| RF059 Reconciliação bancária | Parcial | Import CSV, auto-reconcile e mark-reconciled existem. | Adicionar tolerâncias, regras, import de formatos bancários locais e aprovação. |
| RF060 Caixa | Parcial | Há receitas/despesas, transferências e bancos. | Criar gestão formal de caixa/fundo maneio/conferência de saldo. |
| RF061 Moeda estrangeira | Parcial | Existe moeda base/default; operações FX não estão completas. | Adicionar moeda por documento, câmbio, diferenças cambiais realizadas/não realizadas. |
| RF062 Fluxo de caixa | Parcial | DoubleEntry tem relatório cash-flow. | Adaptar classificação operacional/investimento/financiamento ao SCE. |
| RF063 Balanço | Parcial | Balanço existe, mas por classes genéricas. | Reestruturar por activos/passivos correntes/não correntes e capital próprio via PGC-MZ. |
| RF064 Demonstração de resultados | Parcial | Profit/loss existe. | Gerar DR por natureza SCE com classes 6/7/8 e rubricas legais. |
| RF065 Demonstração de fluxos de caixa | Parcial | Cash-flow existe. | Formalizar método configurado e mapeamentos legais. |
| RF066 Alterações no capital próprio | Não implementado | Não foi encontrado mapa específico. | Criar demonstração de alterações no capital próprio. |
| RF067 Notas às contas | Parcial | Balance sheet tem notas simples. | Criar módulo de notas às contas por rubrica, políticas e divulgações. |
| RF068 Balancete | Parcial | Trial balance existe. | Adicionar filtros por centro custo, projecto, moeda e exercício PGC-MZ. |
| RF069 Calendário fiscal | Não implementado | Há Calendar genérico, não calendário fiscal. | Criar calendário fiscal MZ com obrigações IVA, IRPC, IRPS, INSS, Modelo 20 e alertas. |
| RF070 Modelo 20 | Não implementado | Não há campos Modelo 20. | Mapear contas/rubricas para Modelo 20 e exportar suporte anual. |
| RF071 Declaração anual | Não implementado | Não há consolidação anual fiscal. | Criar workflow de declaração anual e pacote de submissão. |
| RF072 Guias fiscais | Parcial | IVA/payroll têm exports parciais. | Gerar guias para IVA, IRPC, IRPS, INSS, retenções e tributação autónoma. |
| RF073 SAF-T MZ | Parcial | Existe XML SAF-T, mas com namespace PT e sem prova de schema MZ. | Implementar dicionário SAF-T MZ, campos obrigatórios e dataset completo. |
| RF074 Validação SAF-T | Não implementado | Há readiness checks, não validador XML fiscal completo. | Criar validador XML/schema, NUITs, sequências, taxas, motivos e totais. |
| RF075 Exportação AT | Parcial | Export XML/CSV existe. | Criar pacote compatível com AT, assinatura do ficheiro e workflow de submissão. |
| RF076 Histórico de submissões | Não implementado | Não há histórico formal de ficheiros submetidos. | Criar `fiscal_submission_batches` com período, utilizador, estado, versão e ficheiro. |
| RF077 Centros de custo | Parcial | Existem departamentos, projectos, armazéns e filiais parciais. | Criar entidade contabilística `cost_centers` e associar a lançamentos/linhas. |
| RF078 Alocação custos/proveitos | Não implementado | Lançamentos não têm alocação analítica estruturada. | Adicionar distribuição por centro de custo/projecto/obra/filial. |
| RF079 Relatórios analíticos | Não implementado | Não há rentabilidade contabilística por dimensão. | Criar relatórios por centro custo, projecto, cliente, produto e actividade. |
| RF080 Importações | Não implementado | Não há processo aduaneiro. | Criar módulo de importação com fornecedor estrangeiro, DU, direitos, IVA, CIF e licença. |
| RF081 Custeio importações | Não implementado | Não há landed cost. | Implementar rateio de frete, seguro, direitos, taxas e despacho no custo do inventário. |
| RF082 Bloqueio por licença | Não implementado | Não há workflow de licença/documentos obrigatórios. | Criar checklist documental e bloqueio antes de finalizar importação/compra. |
| RF083 Perfis de utilizador | Parcial | Spatie roles/permissions existe. | Criar perfis standard: administrador, contabilista, fiscalista, gestor financeiro, caixa, vendedor, auditor e RH. |
| RF084 Permissões por função | Parcial | Permissões por módulo/operação existem; empresa via tenant. | Expandir para filial, centro de custo e tipo documental. |
| RF085 Trilha de auditoria | Parcial | Auditoria cobre vendas, compras, transferências, POS e payroll. | Cobrir aprovação, anulação, exportação, fecho/reabertura, settings fiscais e plano de contas. |
| RF086 Bloqueio alterações críticas | Parcial | Cancelamento fiscal e fecho bloqueiam parte dos casos. | Bloquear facturas finalizadas, NUIT com histórico, numeração fiscal e lançamentos fechados em todos os fluxos. |
| RF087 Backup/recuperação | Não implementado | Não foi encontrado módulo aplicacional de backup. | Implementar política operacional: dump DB, storage, retenção, restore testado e logs. |
| RF088 Fecho mensal | Parcial | `mz_fiscal_closings` fecha períodos e guarda snapshot. | Criar checklist: IVA, banco, clientes, fornecedores, stock, salários, depreciações e retenções. |
| RF089 Fecho anual | Parcial | DoubleEntry tem year-end close genérico. | Adaptar para Classe 8, IRPC estimado, encerramento classes 6/7 e abertura automática seguinte. |
| RF090 Reabertura controlada | Parcial | Há endpoint de reabertura com razão. | Exigir perfil superior, workflow de aprovação e auditoria explícita. |
| RF091 Alertas fiscais | Parcial | Go-live/readiness checks existem. | Criar motor de alertas fiscais operacional por prazo, inconsistência e pendência. |
| RF092 Validações automáticas | Parcial | Existem validações dispersas. | Criar motor central de validações por documento, período, imposto, conta, centro de custo e sequência. |
| RF093 Multiempresa | Implementado | SaaS multiempresa usa `created_by`, roles e módulos. | Reforçar políticas tenant e testes de isolamento em todos os novos módulos fiscais. |
| RF094 Filiais | Parcial | Existem branches HR, warehouses e establishment em documentos. | Criar modelo unificado de filial/loja/armazém/unidade operacional. |
| RF095 Parametrização de impostos | Parcial | Há taxes, mappings e tabelas payroll. | Criar motor fiscal parametrizável sem alteração de código: IVA, IRPC, IRPS, INSS, retenções e prazos. |
| RF096 Importação/exportação dados | Parcial | Existem importações/exports pontuais: banco, relatórios, SAF-T. | Implementar import/export de plano, clientes, fornecedores, produtos, lançamentos, extractos e relatórios fiscais. |

## 4. Backlog por fases

### Fase 0 - Base de conformidade e segurança fiscal

Objectivo: impedir que novos dados contabilísticos sejam criados sobre estruturas incompatíveis com o SCE.

Entregáveis:

- Modelo `company_fiscal_profiles` com NUIT, regime fiscal, classificação, referencial, exercício fiscal e licença.
- Modelo `accounting_periods` mensal com estados e bloqueio transversal.
- Motor central `FiscalComplianceService` para validar período, NUIT, sequência, documento suporte, conta e imposto.
- Política de imutabilidade para documentos finalizados e lançamentos publicados.
- Auditoria obrigatória de settings fiscais, plano de contas, documentos, exportações e fechos.

### Fase 1 - PGC Moçambique e plano de contas

Objectivo: substituir a semântica genérica `1000-5999` por PGC-NIRF/PGC-PE.

Entregáveis:

- Catálogo PGC-MZ com classes 0-9, rubricas e contas base.
- Importador/versionador de plano de contas por empresa.
- Conta sintética vs conta movimento.
- Parametrização fiscal por conta.
- Migração controlada dos saldos e mapeamento dos lançamentos actuais.

### Fase 2 - Núcleo contabilístico

Objectivo: tornar o módulo de lançamentos suficientemente forte para auditoria.

Entregáveis:

- Diários configuráveis.
- Numeração por empresa/exercício/diário.
- Anexos obrigatórios por diário/tipo de operação.
- Lançamentos recorrentes.
- Fecho mensal com checklist e bloqueios.
- Year-end close adaptado à Classe 8.

### Fase 3 - Facturação, IVA e documentos fiscais

Objectivo: colocar facturação e IVA em padrão fiscal moçambicano.

Entregáveis:

- Tipos documentais fiscais completos.
- Motivos de isenção e códigos IVA MZ.
- IVA liquidado/suportado/dedutível/não dedutível.
- Reverse charge/autoliquidação.
- Hash fiscal encadeado por série.
- Regras de rectificação por nota de crédito/débito.
- Bloqueio definitivo de edição/apagamento após emissão.

### Fase 4 - IRPC, IRPS, INSS e retenções

Objectivo: completar fiscalidade directa e folha.

Entregáveis:

- Motor IRPC: resultado fiscal, correcções, matéria colectável, taxa e pagamentos por conta.
- Motor de retenções na fonte por fornecedor/rendimento/país.
- Guias de retenções e fiscais.
- Payroll com dependentes, benefícios e remunerações tributáveis.
- Lançamentos contabilísticos completos da folha.

### Fase 5 - Inventário, activos fixos e importações

Objectivo: cobrir áreas que têm impacto material nas demonstrações financeiras.

Entregáveis:

- Ledger de stock.
- FIFO e custo médio ponderado.
- Landed cost/importações/aduanas.
- Activos fixos, depreciações, imparidades, reavaliações e alienações.
- Activos biológicos quando aplicável.

### Fase 6 - Relatórios legais, SAF-T e Modelo 20

Objectivo: preparar submissões e relatórios legais.

Entregáveis:

- Balanço, DR por natureza, DFC, alterações no capital próprio e notas às contas em formato SCE.
- Modelo 20 e declaração anual.
- SAF-T MZ completo, validado por schema e regras fiscais.
- Histórico de submissões e versões de ficheiros.

### Fase 7 - Analítica, alertas e administração operacional

Objectivo: fechar controlos de gestão e operação.

Entregáveis:

- Centros de custo e alocações.
- Relatórios analíticos de rentabilidade.
- Calendário fiscal e alertas.
- Backup/restore aplicacional ou runbook operacional testado.
- Import/export completo de dados mestres e transaccionais.

## 5. Priorização recomendada

Prioridade 1:

- PGC-MZ, períodos contabilísticos, bloqueio de contas sintéticas e mapeamento fiscal das contas.
- Imutabilidade de documentos fiscais, numeração cronológica e hash.
- IVA com códigos fiscais, dedutibilidade e declaração.
- SAF-T MZ com validação.

Prioridade 2:

- IRPC, retenções e payroll contabilístico.
- Fecho mensal/anual com checklist.
- Relatórios legais SCE.

Prioridade 3:

- Inventário avançado, activos fixos, importações/aduanas e contabilidade analítica.
- Alertas, backup/restore e import/export amplo.

## 6. Estimativa técnica preliminar

Assumindo uma equipa mínima com 2 programadores sénior, 1 QA e validação funcional por contabilista/fiscalista:

- Fundação de conformidade e PGC-MZ: 8 a 12 semanas.
- Fiscalidade principal, documentos, IVA e fechos: 10 a 16 semanas adicionais.
- Cobertura completa RF001-RF096 com activos, importações, SAF-T validado, relatórios legais e analítica: 6 a 9 meses.

Estas estimativas dependem de uma decisão crítica: se será feita migração dos dados actuais para PGC-MZ ou se o PGC-MZ será aplicado apenas a novas empresas/exercícios. Para produção, a migração deve ser tratada como projecto próprio, com backup, ensaio em staging e validação contabilística.

## 7. Decisão técnica imediata

Antes de avançar para implementação, deve ser fechado um desenho funcional do PGC-MZ:

- Lista oficial de contas PGC-NIRF e PGC-PE a carregar como seed/import.
- Tabela de equivalência entre contas actuais genéricas e contas PGC-MZ.
- Regras de quando uma conta é sintética ou de movimento.
- Mapeamento das contas para IVA, Modelo 20, SAF-T e demonstrações financeiras.
- Estratégia para empresas existentes em produção: migrar, manter histórico ou abrir novo exercício já em PGC-MZ.

Sem esta decisão, qualquer implementação fiscal posterior ficará frágil, porque IVA, IRPC, relatórios, SAF-T e fechos dependem directamente do plano de contas correcto.
