# Relatório de Gap Analysis — Faturação, Tesouraria e Conformidade Fiscal Moçambique RF001-RF092

Data: 1 de Junho de 2026  
Âmbito: análise técnica do código actual do ERPGo/SysGest neste repositório, restrita ao módulo de faturação, tesouraria e conformidade fiscal com foco moçambicano.  
Base de avaliação: requisitos fornecidos pelo cliente nesta conversa.  
Nota: este relatório não substitui parecer de contabilista certificado, fiscalista ou consultor jurídico local.

## 1. Conclusão executiva

O módulo já tem um núcleo fiscal e financeiro relevante:

- perfil fiscal base da empresa;
- séries documentais fiscais, numeração e snapshots fiscais;
- regras de inalterabilidade e rectificação de documentos;
- hash fiscal encadeado;
- motor de IVA com suporte a reverse charge;
- export SAF-T MZ em XML;
- retenções na fonte com mapas mensais;
- contas a receber e contas a pagar com pagamentos parciais;
- contas bancárias, importação de extracto CSV e reconciliação automática;
- integração contabilística base com lançamentos automáticos.

Mas o módulo ainda não está completo face aos RF001-RF092. O estado real é:

- forte no núcleo de faturação fiscal, IVA base, SAF-T base, recebimentos/pagamentos e integração contabilística principal;
- parcial em parametrização legal, cadastro fiscal de clientes/fornecedores, relatórios legais e workflows de aprovação;
- claramente pendente em controlo cambial, GIFiM/branqueamento de capitais, moeda electrónica e parte dos alertas/compliance avançado.

Os maiores vazios para fecho legal/operacional são:

- controlo cambial e repatriamento;
- AML/GIFiM;
- contas de moeda electrónica e limites regulatórios;
- prazos legais específicos de emissão e submissão por cenário fiscal;
- classificação fiscal avançada de clientes e fornecedores não residentes;
- workflow robusto de pagamentos internacionais, ADT e dossiê documental;
- histórico formal de exportação/submissão SAF-T;
- motor de alertas automáticos por risco fiscal/tesouraria/cambial.

## 2. Evidência técnica revista

Principais ficheiros e testes usados nesta análise:

- `app/Models/SalesInvoice.php`
- `app/Models/PurchaseInvoice.php`
- `app/Observers/FiscalDocumentObserver.php`
- `app/Services/FiscalHashService.php`
- `app/Services/FiscalValidationService.php`
- `app/Services/DocumentFiscalSnapshotService.php`
- `app/Services/VatCalculationService.php`
- `app/Services/WithholdingTaxService.php`
- `app/Services/FiscalDeclarationService.php`
- `app/Services/SaftExportService.php`
- `app/Models/CompanyFiscalProfile.php`
- `app/Models/MzVatCode.php`
- `app/Models/WithholdingTaxRule.php`
- `app/Http/Controllers/FiscalDocumentSeriesController.php`
- `packages/workdo/Account/src/Models/Customer.php`
- `packages/workdo/Account/src/Models/Vendor.php`
- `packages/workdo/Account/src/Models/BankAccount.php`
- `packages/workdo/Account/src/Services/BankTransactionsService.php`
- `packages/workdo/Account/src/Http/Requests/StoreCustomerPaymentRequest.php`
- `packages/workdo/Account/src/Http/Requests/StoreVendorPaymentRequest.php`
- `packages/workdo/Account/src/Http/Controllers/MozambiqueTaxAccountMappingController.php`
- `tests/Feature/FiscalDocumentComplianceTest.php`
- `tests/Feature/FiscalDocumentImmutabilityHardeningTest.php`
- `tests/Feature/DocumentFiscalSnapshotTest.php`
- `tests/Feature/BankStatementImportReconciliationTest.php`
- `tests/Feature/SceTaxDeclarationEndpointsTest.php`

Observação importante:

- não encontrei módulos dedicados para `GIFiM`, `branqueamento de capitais`, `repatriamento cambial`, `dossier cambial`, `moeda electrónica` regulada ou `workflow de pagamentos internacionais` com validação legal completa;
- encontrei apenas suporte operacional básico a `mobile money` como método de pagamento (`mpesa`, `emola`, `mkesh`), o que não equivale ao módulo regulatório pedido em RF067-RF070.

## 3. Legenda de estado

- `Implementado`: existe funcionalidade relevante e utilizável, ainda que possa haver melhorias menores.
- `Parcial`: existe base técnica importante, mas faltam campos, regras, validações, workflow ou cobertura legal.
- `Pendente`: não encontrei implementação funcional específica para o requisito.

## 4. Matriz RF001-RF092

### 4.1 Configuração fiscal e legal

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF001 | Parcial | `CompanyFiscalProfile` guarda `nuit`, `fiscal_regime`, `entity_classification`, `accounting_framework`, actividade económica, província, repartição fiscal e dados bancários estruturados. | Falta consolidar nome legal fiscal, obrigações fiscais aplicáveis, banco principal, tipo de contribuinte e estado de certificação do software. |
| RF002 | Parcial | Existem `MzVatCode`, `WithholdingTaxRule`, `MozTaxAccountMapping`, calendário fiscal e parâmetros fiscais dispersos. | Falta configuração central de regras de faturação electrónica, limites GIFiM, regras cambiais e tipos de operação mais completos. |
| RF003 | Parcial | Há vigência em `MzVatCode`, `MozTaxAccountMapping`, tabelas IRPS/INSS e séries fiscais com `valid_from/valid_to`. | A vigência não está uniformizada em todas as regras legais e documentos do módulo. |
| RF004 | Parcial | Há tabelas configuráveis para IVA, IRPS, INSS e mappings fiscais sem alterar código. | Faltam tabelas parametrizáveis para ADT, limites de numerário, limites de moeda electrónica e regras cambiais completas. |

### 4.2 Clientes

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF005 | Parcial | `Customer` guarda `company_name`, `tax_number`, moradas, contactos e `payment_terms`. | Faltam tipo de cliente, país de residência fiscal, moeda de faturação, regime IVA, tipo de operação e conta contabilística associada. |
| RF006 | Parcial | `FiscalValidationService` valida NUIT e existe suporte a NUIT em clientes e snapshots fiscais. | A validação ainda não está aplicada de forma dura em todo o fluxo de emissão fiscal. |
| RF007 | Pendente | Não encontrei classificação formal residente/não residente/entidade pública/isento. | Falta modelo e UI de classificação fiscal do cliente. |
| RF008 | Parcial | Os documentos capturam `counterparty_snapshot`, preservando dados fiscais no momento da emissão. | Falta bloquear alterações directas nos dados mestres do cliente após emissão e manter histórico/auditoria específico dessas alterações. |

### 4.3 Fornecedores

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF009 | Parcial | `Vendor` guarda `company_name`, `tax_number`, `currency_code`, contactos, moradas e `payment_terms`. | Faltam tipo de fornecedor, sujeição a retenção, ADT, tipo de bem/serviço, dados bancários estruturados e classificação fiscal robusta. |
| RF010 | Parcial | Há país nas moradas, `currency_code` no fornecedor e regras de retenção por residência. | Falta classificação operacional explícita de não residente para reverse charge, IRPC e pagamentos internacionais. |
| RF011 | Pendente | Não encontrei dossier documental de fornecedor com certificados, contratos e comprovativos fiscais. | Falta anexação/validação documental por fornecedor e por pagamento. |

### 4.4 Faturação

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF012 | Parcial | `SalesInvoice` e documentos associados suportam número, série, datas, cliente, itens, totais, descontos e `payment_terms`. | Falta fechar `data da operação`, moeda por documento, motivo de isenção obrigatório e validação fiscal total na emissão. |
| RF013 | Pendente | Não encontrei cálculo automático do 5.º dia útil nem bloqueio de faturação tardia. | Falta motor de prazo legal de emissão e registo de justificativa de atraso. |
| RF014 | Parcial | `FiscalDocumentType` semeia `FT`, `FR`, `FS`, `NC`, `ND`, `GR`, `GT`, `RC`, `AF`, `VD`; existem propostas e returns. | Faltam proforma/documento equivalente formalizados e cobertura uniforme de todos os tipos na operação. |
| RF015 | Implementado | Há séries fiscais, sequência por documento e estabelecimento, e teste em `FiscalDocumentComplianceTest`. | Pode melhorar em cronologia mais rígida, mas o núcleo está funcional. |
| RF016 | Parcial | `FiscalDocumentObserver` e `FiscalValidationService` bloqueiam edição de documentos finais; teste de hardening impede apagar documento postado/submetido. | A cobertura ainda precisa ser uniformizada em todos os documentos financeiros/fiscais. |
| RF017 | Implementado | Cancelamento fiscal exige rectificação quando aplicável e há criação automática de nota de crédito/débito a partir de returns. | Mantém margem para expandir regras por tipo documental, mas o mecanismo principal existe. |
| RF018 | Parcial | `FiscalHashService` gera hash SHA-256 encadeado por série e `FiscalDocumentObserver` emite hash no posting. | Falta cobertura homogénea em todos os documentos e empacotamento final de certificação/assinatura. |
| RF019 | Parcial | Há séries, hash, auditoria parcial, snapshots fiscais, export SAF-T e bloqueios de alteração. | Falta fechar histórico formal de submissões, trilha completa e requisitos operacionais de certificação legal. |

### 4.5 IVA

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF020 | Parcial | `MzVatCode` cobre normal, reduzida, isento, zero, não sujeito, reverse charge e importação. | Falta fechar `digital`, `não dedutível` como código operacional e cobertura integral de todos cenários do requisito. |
| RF021 | Parcial | `VatCalculationService` calcula IVA liquidado, suportado, dedutível, não dedutível, a pagar, a recuperar e reverse charge. | Ainda falta acoplamento total ao fluxo de documentos e às declarações operacionais. |
| RF022 | Parcial | Existem códigos e resumos de IVA por conta. | Falta classificar operações de forma explícita por exportação, serviços digitais e operações sem direito à dedução. |
| RF023 | Parcial | `MzVatCode` possui `exemption_reason`. | Não encontrei validação obrigatória antes de emitir documento isento/não sujeito. |
| RF024 | Pendente | Não encontrei identificação funcional específica para licenças, cloud, streaming e outros serviços digitais. | Falta classificação operacional de bens/serviços digitais. |
| RF025 | Parcial | `VatCalculationService` trata `reverse_charge` e lança simultaneamente IVA input/output. | Falta ligar isso a fornecedor não residente, documento, reporte e workflow fiscal completo. |
| RF026 | Parcial | Existem mapa/declaração IVA e calendário fiscal. | Os prazos legais diferenciados do requisito não estão parametrizados como dia 10, 15 e último dia do mês por cenário. |
| RF027 | Parcial | Há checklist mensal com reconciliação de IVA e cálculo periódico. | Falta fecho mensal de IVA como workflow dedicado com validações finais do período. |
| RF028 | Parcial | `FiscalCalendarEvent` oferece lógica de pendente/overdue/upcoming. | Faltam alertas específicos para operações digitais, facturas sem classificação e diferentes deadlines legais. |

### 4.6 SAF-T MZ

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF029 | Parcial | `SaftExportService` exporta XML com empresa, plano de contas, clientes, fornecedores, impostos, documentos e lançamentos. | Falta garantir cobertura total de todos os tipos documentais e aderência final ao pacote legal pretendido. |
| RF030 | Parcial | `SaftExportService::validateXml()` valida XML bem formado e opcionalmente XSD. | Falta validação funcional completa de NUITs, séries, motivos, totais, documentos anulados e coerência fiscal antes da exportação. |
| RF031 | Pendente | Não encontrei tabela ou serviço de histórico de exportação SAF-T com período, hash, utilizador e estado de submissão. | Falta registo formal de exportações/submissões SAF-T. |
| RF032 | Parcial | O sistema exporta XML e suporta fluxo manual. | Falta submissão estruturada, comprovativo de submissão e integração futura por webservice. |
| RF033 | Parcial | Os documentos têm `fiscal_submission_status` e `fiscal_submission_reference`. | Falta módulo operacional dedicado ao reporte sistemático de facturas/documentos equivalentes à AT. |

### 4.7 Contas a receber

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF034 | Parcial | Há facturas pendentes, pagamentos parciais, recibos/credit notes, saldo e overdue em `SalesInvoice`. | Falta fechar idade da dívida, cobranças em atraso e adiantamentos de clientes com workflow fiscal completo. |
| RF035 | Parcial | `CustomerPayment` integra com `BankTransactionsService`, banco, reconciliação e contabilidade. | Falta suporte robusto a moeda estrangeira, conta corrente legal e alguns cenários avançados de tesouraria. |
| RF036 | Pendente | Não encontrei recebimento com moeda, taxa cambial, valor original em FX e diferença cambial registada. | Falta motor de recebimentos em moeda estrangeira. |
| RF037 | Pendente | Não encontrei identificação e controlo de receitas de exportação com repatriamento. | Falta módulo de receitas de exportação e controlo cambial associado. |

### 4.8 Contas a pagar

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF038 | Parcial | `PurchaseInvoice` cobre fornecedor, datas, montantes, impostos e items. | Faltam centro de custo obrigatório, retenção aplicada por linha/serviço e gestão documental mais forte. |
| RF039 | Parcial | `StoreVendorPaymentRequest` valida fornecedor, conta bancária, alocações, notas de débito e coerência de montantes. | Falta validação fiscal completa de NUIT, IVA, retenção, documentação cambial e ADT antes do pagamento. |
| RF040 | Parcial | O sistema suporta `bank_transfer`, `cash`, `cheque`, `card`, `mobile_money` e compensação por notas de débito. | Falta tratar limites legais, caixa formal e regras específicas por meio de pagamento. |
| RF041 | Parcial | Existem `iban`/`swift` em contas bancárias e `currency_code` em fornecedor. | Falta workflow de pagamento internacional com país do fornecedor, contrato, retenção, ADT e autorização cambial. |
| RF042 | Pendente | Não encontrei bloqueio de remessa ao exterior sem comprovação de retenção/isenção/documentação. | Falta validação impeditiva para pagamentos internacionais não conformes. |

### 4.9 Retenções na fonte / IRPC

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF043 | Parcial | `WithholdingTaxRule` suporta `income_type`, taxa, residência e contas contabilísticas; `WithholdingTaxService` calcula e regista. | Faltam país do beneficiário, ADT, estabelecimento estável, vigência detalhada e aprovação fiscal por cenário. |
| RF044 | Pendente | Não encontrei regra dedicada de 10% para bens/serviços digitais e agentes de moeda electrónica não residentes. | Falta regra legal específica e sua automação. |
| RF045 | Parcial | Existem regras default de 20% para royalties, assistência técnica, gestão, dividendos e serviços não residentes. | Falta ligação a pagamento internacional, documentação e excepções legais completas. |
| RF046 | Pendente | Não encontrei modelo específico de ADT com certificado de residência fiscal e taxa reduzida. | Falta cadastro e workflow de ADT. |
| RF047 | Pendente | Não encontrei comparação automática entre taxa local e taxa ADT. | Falta motor comparativo e sugestão da taxa correcta. |
| RF048 | Parcial | `FiscalDeclarationService` e `TaxController` geram declaração mensal/export CSV com totais e regras. | Falta mapa mais completo com país, tipo de rendimento, estado de pagamento ao Estado e workflow de liquidação. |
| RF049 | Implementado | `WithholdingTaxTransaction` mantém histórico por fornecedor, período e regra. | Pode ganhar mais filtros, mas o histórico base já existe. |

### 4.10 Tesouraria

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF050 | Parcial | `BankAccount` guarda conta, banco, branch, tipo, saldo, `iban`, `swift` e conta GL. | Falta país, moeda da conta e classificação mais orientada ao requisito regulatório. |
| RF051 | Parcial | Há movimentos bancários e meios de pagamento que usam caixa/numerário. | Falta módulo formal de caixa com fundo fixo, fecho diário, conferência e limites de numerário. |
| RF052 | Parcial | Existem relatórios de fluxo de caixa em `FinancialStatementsService` e `DoubleEntry`. | Falta previsão detalhada por cliente, fornecedor, projecto, moeda e banco. |
| RF053 | Pendente | Não encontrei workflow multi-etapa de aprovação por valor, moeda, fornecedor ou risco. | Falta motor de aprovação de pagamentos. |
| RF054 | Implementado | `BankTransactionsService` importa CSV, evita duplicados e reconcilia automaticamente; há teste dedicado. | Pode crescer em formatos bancários locais, mas a funcionalidade base existe. |
| RF055 | Parcial | Existem retainers e pagamentos/alocações parciais. | Falta gestão consolidada de adiantamentos a fornecedores, trabalhadores e clientes com prestação de contas. |

### 4.11 Controlo cambial

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF056 | Pendente | Não encontrei identificação formal de operações sujeitas a controlo cambial. | Falta motor cambial por tipo de operação. |
| RF057 | Pendente | Não encontrei validação para obrigar pagamentos via sistema financeiro autorizado. | Falta regra impeditiva/registo dessa conformidade. |
| RF058 | Pendente | Não encontrei bloqueio de pagamentos domésticos em moeda estrangeira. | Falta política operacional de MZN obrigatório em operações domésticas. |
| RF059 | Pendente | Não encontrei associação factura de exportação -> recebimento externo -> repatriamento. | Falta módulo de repatriamento de receitas de exportação. |
| RF060 | Pendente | Não encontrei registo/controlo de rendimentos de investimentos no exterior. | Falta submódulo próprio. |
| RF061 | Pendente | Não encontrei dossiê cambial com contrato, aduana, retenção, banco e correspondência. | Falta dossier documental cambial por operação. |

### 4.12 Branqueamento de capitais e GIFiM

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF062 | Pendente | Não encontrei controlo explícito de pagamentos em numerário >= 250.000 MT. | Falta alerta/comunicação obrigatória ao GIFiM. |
| RF063 | Pendente | Não encontrei controlo explícito de transacções por cheque/meio electrónico >= 750.000 MT. | Falta alerta regulatório correspondente. |
| RF064 | Pendente | Não encontrei relatório de operações suspeitas ou comunicáveis. | Falta módulo AML/GIFiM completo. |
| RF065 | Pendente | Não encontrei aprovação reforçada por operação de alto valor. | Falta workflow de high-risk payment/compliance. |
| RF066 | Pendente | Não encontrei histórico de comunicações ao GIFiM. | Falta trilha de comunicações AML. |

### 4.13 Moeda electrónica

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF067 | Pendente | O sistema só trata `mobile_money` como método de pagamento, sem cadastro regulatório de contas IME. | Falta cadastro de contas de moeda electrónica. |
| RF068 | Pendente | Não encontrei níveis ou limites por conta de moeda electrónica. | Falta parametrização de limites por nível. |
| RF069 | Pendente | Não encontrei excepção regulatória para médias/grandes empresas nesses limites. | Falta regra empresarial por porte. |
| RF070 | Pendente | Não encontrei alertas sobre aproximação/excesso de limites de moeda electrónica. | Falta painel/alertas dedicados. |

### 4.14 Relatórios fiscais e financeiros

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF071 | Parcial | Há relatórios fiscais e de faturação no pacote `Account`, inclusive fiscal map/export. | Falta relatório orientado exactamente pelos cortes do requisito: série, isenções, digitais, moeda e estado documental. |
| RF072 | Parcial | Existe cálculo e mapa de IVA. | Falta fechar todas as visões legais pedidas, incluindo operações sem actividade e autoliquidação detalhada. |
| RF073 | Pendente | Não encontrei mapa específico de reverse charge por fornecedor não residente e tipo de serviço. | Falta relatório dedicado. |
| RF074 | Parcial | Existem retenções mensais e histórico transaccional. | Falta relatório internacional focado em não residentes, ADT e documentos de suporte. |
| RF075 | Parcial | Há bancos, reconciliação, AP/AR e cash flow. | Falta relatório consolidado de tesouraria como painel único com realizado/projectado. |
| RF076 | Pendente | Não encontrei relatório cambial com exportações, repatriamento e diferenças cambiais. | Falta cobertura cambial. |
| RF077 | Parcial | Existem fiscal map, submission register e sinais de backlog fiscal em relatórios. | Falta dashboard de compliance financeiro exactamente alinhado ao requisito. |

### 4.15 Alertas e validações

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF078 | Parcial | Há statuses fiscais, integridade documental e alguma visibilidade em relatórios. | Falta motor automático de alertas para emissão fora de prazo, falhas de série e isenções sem motivo. |
| RF079 | Parcial | O calendário fiscal suporta eventos pendentes/overdue/upcoming. | Faltam alertas específicos para IVA por cenário, retenções, digital ops e SAF-T pendente/submeter. |
| RF080 | Parcial | Existem overdue em facturas, reconciliação bancária e saldos bancários. | Faltam alertas operacionais de tesouraria por vencimento, insuficiência, altos valores e pendências internacionais. |
| RF081 | Pendente | Não encontrei alertas cambiais específicos. | Falta camada de alertas para repatriamento, FX doméstico e documentação cambial. |

### 4.16 Segurança e auditoria

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF082 | Parcial | O sistema usa roles/permissions de forma transversal. | Faltam perfis financeiros padronizados exactamente como os do requisito. |
| RF083 | Parcial | Há permissões por módulo/operação e isolamento multiempresa. | Faltam restrições por valor da operação, moeda, conta bancária, filial e centro de custo. |
| RF084 | Parcial | Existem audit trail e registos de estados/documentos em áreas críticas. | Falta cobertura completa de export SAF-T, submissão fiscal, alterações bancárias e pagamentos internacionais. |
| RF085 | Parcial | Documentos fiscais finais não podem ser alterados facilmente e posted invoices não podem ser apagadas. | Falta política homogénea de anulação controlada em todos os documentos fiscais/financeiros. |

### 4.17 Integração com contabilidade

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF086 | Parcial | `JournalService`, `VatCalculationService` e `WithholdingTaxService` geram lançamentos para vendas, compras, pagamentos, IVA e retenções. | Falta cobertura total para diferenças cambiais, comissões bancárias e cenários internacionais completos. |
| RF087 | Parcial | Há plano de contas, mappings fiscais e associação GL em bancos. | Falta mapeamento mais granular por cliente, fornecedor, bancos, retenções e diferenças cambiais em todos fluxos. |
| RF088 | Parcial | Já existem `cost_centers` no pacote SCE e algumas bases analíticas. | Falta uso consistente em faturação, pagamentos, tesouraria e relatórios. |

### 4.18 Administração do sistema

| RF | Estado | Evidência actual | Gap principal |
| --- | --- | --- | --- |
| RF089 | Implementado | O sistema é multiempresa e usa isolamento por `created_by`, com vários testes de tenant isolation. | Requer apenas continuidade de hardening. |
| RF090 | Parcial | Há `establishment_id` por armazém/warehouse e séries por estabelecimento. | Falta modelo unificado de filial/loja/unidade operacional aplicado a toda faturação e tesouraria. |
| RF091 | Parcial | Existem `FiscalDocumentSeries`, controller de séries e parametrização por série/ano/estabelecimento. | Falta granularidade por utilizador, terminal e regime fiscal, como pedido. |
| RF092 | Parcial | Há import PGC, import de extracto bancário CSV, export SAF-T e exportações fiscais várias. | Faltam import/export amplos de clientes, fornecedores, produtos, facturas, pagamentos e relatórios cambiais. |

## 5. Síntese por prioridade

### 5.1 O que já está forte

- numeração e séries fiscais;
- snapshots fiscais de emitente/contraparte;
- hash fiscal encadeado;
- rectificação com nota de crédito/débito;
- IVA base com cálculo e reverse charge contabilístico;
- export SAF-T base;
- retenções na fonte com histórico e declaração mensal;
- reconciliação bancária importada por CSV;
- integração contabilística principal de vendas, compras e pagamentos;
- isolamento multiempresa.

### 5.2 O que falta para “fechar legalmente” o módulo

- classificação fiscal avançada de clientes e fornecedores;
- prazos legais automáticos de emissão e submissão;
- ADT e pagamentos internacionais;
- controlo cambial e repatriamento;
- AML/GIFiM;
- moeda electrónica regulada;
- alertas automáticos completos;
- histórico formal de exportação/submissão SAF-T;
- relatórios cambiais e de compliance financeiro final.

## 6. Backlog recomendado por sprint

### Sprint 1 — Fecho fiscal documental

- RF005-RF011
- RF013
- RF019
- RF023
- RF031-RF033
- RF078-RF079

Objectivo:
- endurecer cadastro fiscal de clientes/fornecedores;
- fechar motivo de isenção, deadlines legais e histórico formal de SAF-T/submissão.

### Sprint 2 — Tesouraria e pagamentos internacionais

- RF036
- RF039-RF042
- RF050-RF055

Objectivo:
- completar pagamentos/recebimentos em moeda estrangeira, aprovações e documentação antes de pagamento.

### Sprint 3 — Cambial e compliance financeiro

- RF056-RF061
- RF076-RF081

Objectivo:
- criar motor cambial, repatriamento, dossier cambial e alertas associados.

### Sprint 4 — AML/GIFiM e moeda electrónica

- RF062-RF070

Objectivo:
- implementar o bloco regulatório hoje ausente.

### Sprint 5 — Relatórios e parametrização final

- RF071-RF075
- RF082-RF088
- RF090-RF092

Objectivo:
- fechar dashboards, relatórios legais, perfis, permissões finas e administração final do módulo.

## 7. Veredicto final

O módulo de faturação/tesouraria/conformidade fiscal não está concluído face aos RF001-RF092.

O diagnóstico mais correcto é:

- núcleo fiscal e contabilístico: `bom e utilizável`;
- conformidade moçambicana avançada: `parcial`;
- cambial, GIFiM e moeda electrónica: `pendente`.

Ou seja, antes de avançar para implementação, a melhor estratégia é tratar o módulo em três blocos:

1. `fiscal documental e reporting`,
2. `tesouraria internacional e cambial`,
3. `AML/GIFiM + moeda electrónica`.
