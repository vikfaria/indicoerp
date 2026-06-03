# Relatorio final consolidado de desenvolvimento e prontidao go-live - Mocambique

Data da auditoria: 03 de Junho de 2026  
Repositorio auditado: `sysgest`  
Decisao tecnica: **Nao apto para go-live imediato**. Pode passar para **apto com restricoes** somente depois de cumprir as correcoes obrigatorias da seccao 14.

## 1. Resumo executivo

Esta auditoria cruzou os relatorios de adaptacao, implementacao, SCE, RH, faturacao/tesouraria/conformidade fiscal, roadmap de fecho e prontidao de producao. A conclusao e objetiva: o codigo evoluiu muito alem dos primeiros relatorios, e existe evidencia tecnica forte para grande parte dos requisitos fiscais, financeiros e laborais. Contudo, a entrada imediata em producao ainda nao deve ser autorizada.

Principais evidencias positivas:

- Faturacao, tesouraria e conformidade fiscal: 85 testes alvo passaram, cobrindo clientes/fornecedores fiscais, pagamentos em moeda estrangeira, remessas internacionais, ADT, reverse charge, GIFiM, moeda electronica, caixa, SAF-T/historico de exportacao, alertas fiscais, permissoes e hardening.
- RH: 81 testes alvo passaram, cobrindo perfis legais do trabalhador, INSS, dependentes, quotas de estrangeiros, probatorio, assiduidade biometrica, ferias, licencas, horas extra, assedio, disciplina, offboarding, payroll, Modelo 19, INSS, import/export e dashboard de compliance.
- Readiness/SCE/setup: 11 testes passaram, incluindo painel de go-live, atestacoes, pilotos, calendario fiscal e sincronizacao de roles financeiras.
- `npm run build` passou. Existem avisos nao bloqueantes: Browserslist desatualizado e warning de minificacao CSS existente.

Bloqueadores objetivos:

- `php artisan migrate:status` falhou por `SQLSTATE[HY000] [2002] Connection refused`; portanto a aplicacao local nao validou migrations contra uma base MySQL real nesta auditoria.
- O worktree contem muitas alteracoes modificadas e nao rastreadas, incluindo migrations novas; sem commit/tag/deploy controlado, o pacote nao e reproduzivel.
- O painel de readiness exige revisao legal/fiscal, aprovacao comercial, piloto com empresa real, evidencias assinadas, validacao real de payroll/contabilidade, cenarios E2E e aprovacao formal. A existencia do mecanismo nao prova que essas evidencias ja estejam registadas.
- SAF-T MZ, certificacao fiscal de software, tabelas legais oficiais, parametros laborais e valores fiscais exigem validacao juridica/fiscal externa antes de serem assumidos como conformidade legal final.

## 2. Escopo da analise

Modulos analisados:

- Faturacao;
- Tesouraria;
- Contabilidade/SCE;
- Recursos Humanos;
- Conformidade fiscal;
- Integracoes entre vendas, compras, bancos, payroll, contabilidade, fiscalidade e auditoria.

Fora do escopo desta auditoria:

- Validacao juridica formal por advogado/consultor fiscal licenciado;
- Certificacao oficial do software de faturacao;
- Validacao contra ambiente de producao real, porque a base MySQL local estava indisponivel;
- Assinatura do cliente/piloto, salvo mecanismo tecnico ja implementado.

## 3. Documentos analisados

- `analise-adaptacao-mocambique-2026-04-24.md`
- `plano-implementacao-mocambique-2026-04-24.md`
- `plano-sprints-fecho-faturacao-tesouraria-conformidade-fiscal-mocambique-2026-06-03.md`
- `relatorio-faturacao-tesouraria-conformidade-fiscal-mocambique-rf001-rf092-2026-06-01.md`
- `relatorio-prontidao-producao-sce-2026-05-24.md`
- `relatorio-rh-mocambique-rf001-rf103-2026-05-27.md`
- `relatorio-rh-mocambique-rf001-rf103-status-atual-2026-06-01.md`
- `relatorio-sce-mocambique-rf001-rf096-2026-05-22.md`
- `roadmap-fecho-faturacao-tesouraria-conformidade-fiscal-mocambique-2026-06-03.md`

## 4. Metodologia de cruzamento dos requisitos

Criterio usado nesta auditoria:

- **Implementado**: existe evidencia objetiva no codigo, migrations, controllers, requests, models, services, rotas, UI ou testes automatizados.
- **Parcialmente Implementado**: existe estrutura funcional, mas falta cobertura total de cenarios, UI, parametros legais, prova de producao ou validacao juridica/fiscal.
- **Pendente**: nao foi encontrada evidencia suficiente de fluxo funcional correspondente.
- **Duplicado**: requisito aparece em mais de um relatorio cobrindo a mesma capacidade.
- **Conflitante**: relatorio antigo afirma lacuna que foi fechada depois, ou ha divergencia de escopo/estado.
- **Requer Validacao**: ha implementacao tecnica, mas a conformidade depende de dados reais, valores legais, schema oficial, piloto ou aprovacao manual.

Comandos executados nesta auditoria:

- `php artisan test tests/Feature/AccountForeignCurrencyPaymentsTest.php ... tests/Feature/VatCalculationSpecialVatCodesTest.php`: **85 passed, 558 assertions**.
- `php artisan test tests/Feature/HrmEmployeeLegalProfilesTest.php ... tests/Feature/ContractLabourComplianceTest.php`: **81 passed, 504 assertions**.
- `php artisan test tests/Feature/MozambiqueGoLiveReadinessTest.php tests/Feature/SceSetupCommandTest.php tests/Feature/SyncFiscalCalendarCommandTest.php tests/Feature/SyncAccountFinanceRolesCommandTest.php`: **11 passed, 265 assertions**.
- `npm run build`: **passou**, com warnings nao bloqueantes.
- `php artisan migrate:status`: **falhou** por MySQL indisponivel.

## 5. Matriz consolidada de requisitos funcionais

Observacao: esta matriz consolida os RFs por bloco funcional para evitar duplicacao entre relatorios. Quando um bloco contem RFs com excecoes, a coluna de lacunas explicita os RFs que ainda nao estao fechados. As descricoes completas dos RFs individuais permanecem nos relatorios de origem.

### Faturacao, tesouraria e conformidade fiscal RF001-RF092

| Requisito(s) | Descricao consolidada | Origem | Modulo | Estado atual | Evidencia objetiva | Lacunas identificadas | Impacto em producao | Prioridade | Acao antes do go-live |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| RF001-RF004 | Perfil fiscal da empresa, parametros fiscais, vigencia legal e tabelas legais. | Relatorio faturacao RF001-RF092; SCE RF001-RF004 | Fiscal/SCE | Parcialmente Implementado / Requer Validacao | `CompanyFiscalProfile`, `MzVatCode`, `WithholdingTaxRule`, `WithholdingTaxTreatyRate`, calendario fiscal, `SceSetupCommand`. | Validar valores legais oficiais, vigencias, estado de certificacao, obrigacoes por empresa e ausencia de parametros hardcoded. | Parametros fiscais incorretos podem gerar declaracoes, IVA, retencoes e documentos fiscais errados. | Critica | Executar setup em staging/producao, validar tabelas com fiscalista e bloquear go-live se faltarem parametros ativos. |
| RF005-RF008 | Cadastro fiscal, NUIT, classificacao e historico/auditoria de clientes. | Faturacao RF005-RF008; SCE RF015-RF020 | Clientes/Faturacao | RF005-RF007 Implementado; RF008 Parcial | `StoreCustomerRequest`, `Customer`, testes `AccountCounterpartyFiscalClassificationTest`, snapshots fiscais em documentos. | Bloqueio de alteracao direta de dados criticos apos emissao ainda requer validacao em fluxo real completo. | Alteracao de NUIT/nome fiscal apos documentos emitidos pode quebrar SAF-T e auditoria fiscal. | Alta | Confirmar bloqueio/auditoria de alteracoes em clientes com documentos fiscais emitidos. |
| RF009-RF011 | Cadastro, classificacao fiscal e dossier documental de fornecedores. | Faturacao RF009-RF011; SCE RF018-RF020/RF040-RF042 | Fornecedores/Compras | RF009-RF010 Implementado; RF011 Parcial | `StoreVendorRequest`, ADT, reverse charge, compliance documents, testes `AccountCounterpartyFiscalClassificationTest` e `AccountForeignCurrencyPaymentsTest`. | Dossier documental existe sobretudo por fornecedor/pagamento internacional; falta validar cobertura documental geral para todos os tipos de fornecedor. | Pagamentos e deducoes fiscais podem ocorrer sem suporte documental suficiente. | Alta | Tornar documentos obrigatorios por tipo de fornecedor/operacao e validar em piloto. |
| RF012-RF019 | Emissao de documentos fiscais, prazo legal, numeracao, inalterabilidade, rectificativos, hash e base de certificacao. | Faturacao RF012-RF019; SCE RF021-RF026 | Faturacao | Implementado tecnicamente; RF014/RF019 Requerem Validacao | `SalesInvoiceController`, requests de sales invoice, `FiscalDocumentObserver`, `FiscalHashService`, `FiscalDocumentSeries`, testes `SalesInvoiceFiscalComplianceRulesTest`, `FiscalDocumentComplianceTest`. | Proforma/documento equivalente/autofactura e certificacao legal final precisam validacao operacional/juridica. | Documento fiscal invalido ou editavel pode causar incumprimento fiscal grave. | Critica | Executar fluxo real de factura, factura-recibo, nota credito/debito, guia e cancelamento; validar com fiscalista. |
| RF020-RF028 | IVA, isencoes, digital services, reverse charge, declaracoes e alertas. | Faturacao RF020-RF028; SCE RF027-RF032/RF069-RF072 | IVA/Fiscal | RF020-RF025 Implementado; RF026-RF028 Parcial/Requer Validacao | `VatCalculationService`, `MzVatCode`, `FiscalDeclarationService`, `SalesInvoiceFiscalComplianceRulesTest`, `VatCalculationSpecialVatCodesTest`, fiscal calendar. | Deadlines especificos e fecho mensal de IVA devem ser validados em dados reais; operacoes sem actividade/regras especiais dependem de parametrizacao. | Declaracao IVA e autoliquidacao erradas podem gerar multas e imposto incorreto. | Critica | Validar mapa IVA com casos reais: tributado, isento, zero, importacao, digital e reverse charge. |
| RF029-RF033 | SAF-T MZ, validacao, historico de exportacao e submissao AT. | Faturacao RF029-RF033; SCE RF073-RF076 | SAF-T/Fiscal | Implementado tecnicamente; RF030/RF032/RF033 Requerem Validacao | `SaftExportService`, `FiscalExportHistory`, rotas `mozambique-saft.export`, testes `MozambiqueFiscalExportsHistoryTest`. | Validacao contra schema oficial SAF-T MZ e certificacao/submissao real nao comprovadas nesta auditoria. | SAF-T rejeitado ou fiscalmente incompleto pode bloquear conformidade. | Critica | Validar XML com schema/validador oficial, guardar comprovativo e registar submissao real/piloto. |
| RF034-RF037 | Contas a receber, recebimentos, moeda estrangeira e receitas de exportacao/repatriamento. | Faturacao RF034-RF037; SCE RF015-RF017/RF058-RF062 | Tesouraria/AR | Implementado | `CustomerPayment`, `StoreCustomerPaymentRequest`, exchange report, repatriation endpoint, testes `AccountForeignCurrencyPaymentsTest`. | Requer validacao com bancos e documentos reais de recebimento externo. | Falha de repatriamento ou FX pode gerar risco cambial e contabilistico. | Alta | Testar recebimento exportacao, parcial/completo, diferenca cambial e comprovativo bancario. |
| RF038-RF042 | Facturas de fornecedor, pagamentos nacionais/internacionais e bloqueio de remessas sem conformidade. | Faturacao RF038-RF042; SCE RF018-RF020/RF040-RF042 | AP/Tesouraria | Implementado tecnicamente; RF038 Parcial | `VendorPayment`, `StoreVendorPaymentRequest`, ADT/retenção/dossier, testes de remessa internacional. | Factura de fornecedor e retencao por linha/servico ainda devem ser validadas em compras reais. | Remessa internacional sem retencao/ADT/documentos pode gerar incumprimento fiscal/cambial. | Critica | Executar piloto com fornecedor residente, nao residente, ADT e pagamento internacional. |
| RF043-RF049 | Retencoes na fonte, ADT, taxas 10/20%, guia e historico. | Faturacao RF043-RF049; SCE RF040-RF042 | Retencoes/IRPC | Implementado tecnicamente | `WithholdingTaxService`, `WithholdingTaxTreatyRate`, `TaxController`, `WithholdingTaxTransaction`, testes ADT e reports internacionais. | Validar taxas e acordos reais por pais/rendimento com fiscalista. | Retencao incorreta altera liquido pago, imposto a entregar e risco de remessa. | Critica | Conferir tabela ADT oficial, certificado de residencia fiscal e mapa mensal de retencoes. |
| RF050-RF055 | Bancos, caixa, fluxo de caixa, aprovacao de pagamentos, reconciliacao e adiantamentos. | Faturacao RF050-RF055; SCE RF058-RF062 | Tesouraria | Implementado tecnicamente; adiantamentos Requerem Validacao | `BankAccount`, `BankTransactionsService`, `MozCashClosing`, approval fields, testes `MozambiqueCashClosingTest` e payments. | Adiantamentos/trabalhadores/prestacao de contas nao possuem a mesma evidencia de ponta a ponta dos pagamentos. | Saldos de caixa/banco e contas correntes podem ficar inconsistentes. | Alta | Validar caixa diario, reconciliacao, aprovacao e adiantamentos em piloto. |
| RF056-RF061 | Controlo cambial, moeda domestica, repatriamento e dossier cambial. | Faturacao RF056-RF061 | Tesouraria/Cambial | Implementado tecnicamente | `ExchangeControlDossier`, `ExchangeControlDossierService`, fields FX/repatriation, reports e testes. | Validar requisitos documentais reais com banco/intermediario; importacoes requerem piloto especifico. | Risco de incumprimento cambial e bloqueio bancario. | Critica | Testar dossier cambial com contrato, factura, banco, retencao e comprovativos. |
| RF062-RF066 | GIFiM/AML: numerario, cheque/electronico, comunicacoes e high-value approval. | Faturacao RF062-RF066 | Compliance financeiro | Implementado tecnicamente | Campos GIFiM em payments, `mozambique-gifim-compliance-report`, thresholds 250.000/750.000 MT, testes. | Validar formato real de comunicacao e politica interna AML. | Operacoes comunicaveis nao reportadas podem gerar risco regulatorio grave. | Critica | Validar workflow de comunicacao GIFiM com responsavel compliance e evidencias anexas. |
| RF067-RF070 | Moeda electronica: contas, limites, excecoes e alertas. | Faturacao RF067-RF070 | Tesouraria/IME | Implementado tecnicamente | Campos em `BankAccount`, relatorio electronic money, validations de limites e testes. | Validar niveis/limites aplicaveis com provedor/lei e empresa concreta. | Pagamentos por IME podem violar limites regulatórios. | Alta | Configurar limites oficiais e executar cenarios acima/abaixo do limite. |
| RF071-RF081 | Relatorios de faturacao, IVA, reverse charge, retencoes, tesouraria, cambial, compliance e alertas. | Faturacao RF071-RF081 | Reporting/Compliance | Implementado tecnicamente | `ReportService`, rotas Mozambique reports, paginas React de reports, testes de relatórios. | Confirmar layouts oficiais e filtros necessarios ao cliente real. | Gestao pode tomar decisao com indicadores incompletos. | Alta | Validar cada relatorio com dados reais e assinar aceite funcional. |
| RF082-RF085 | Roles, permissoes, auditoria e bloqueio de eliminacao definitiva. | Faturacao RF082-RF085; SCE RF083-RF087 | Seguranca/Auditoria | Parcialmente Implementado | `account:sync-finance-roles`, `AuditTrailService`, hardening tests, permissions em reports/payments. | Regras por valor, moeda, conta bancaria, filial e centro de custo devem ser testadas em roles reais. | Acesso indevido a dados financeiros/fiscais ou anulacao sem prova. | Critica | Rodar seed/sync de roles, revisar matriz de permissoes por perfil e validar auditoria. |
| RF086-RF088 | Integracao contabilistica, mapeamento de contas e centros de custo. | Faturacao RF086-RF088; SCE RF009-RF014/RF077-RF079 | Contabilidade | Parcialmente Implementado | `JournalService`, `VatCalculationService`, `WithholdingTaxService`, `cost_centers`, payroll accounting exports. | Mapeamento PGC-MZ, diferencas cambiais, comissoes bancarias e centros de custo exigem validacao end-to-end. | Lancamentos podem ficar contabilisticamente incorretos. | Critica | Executar E2E venda/compra/pagamento/payroll/FX com journal e balancete. |
| RF089-RF092 | Multiempresa, filiais/estabelecimentos, series e import/export. | Faturacao RF089-RF092; SCE RF093-RF096 | Administracao | RF089 Implementado; RF090-RF092 Parcial | Tenant `created_by`, series fiscais por estabelecimento, import banco/PGC, exports fiscais. | Filiais/terminais/utilizadores e import/export massivo ainda requerem hardening. | Mistura de dados por empresa/filial ou falha de serie fiscal. | Alta | Testar tenant isolation, series por filial/estabelecimento e import/export critico. |

### Recursos Humanos RF001-RF103

| Requisito(s) | Descricao consolidada | Origem | Modulo | Estado atual | Evidencia objetiva | Lacunas identificadas | Impacto em producao | Prioridade | Acao antes do go-live |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| RF001-RF004 | Empresa, quotas estrangeiros, politicas internas e codigo de conduta. | RH RF001-RF004 | RH/Compliance | RF001-RF002 Implementado tecnicamente; RF003 Parcial; RF004 Implementado tecnicamente | `MozambiqueHrComplianceDashboardService`, quota tests, code of conduct alerts. | Governanca/versionamento formal de politicas internas ainda parcial. | Falta documental pode afetar auditoria laboral e assedio. | Alta | Registrar politicas internas, versao, aprovacao e comprovativo de ciencia. |
| RF005-RF008 | Recrutamento, idade minima, profissao regulada e anti-discriminacao. | RH RF005-RF008 | Recrutamento | Implementado | `RecruitmentMozambiqueComplianceTest`, requests de custom questions. | Validar lista real de profissoes reguladas. | Contratacao ilegal de menor/profissao regulada sem licenca. | Critica | Rever matriz de profissoes reguladas com RH/juridico. |
| RF009-RF014 | Contratos, geracao, justificacao a prazo, presuncao, anexos e historico. | RH RF009-RF014 | Contratos | RF009/RF011/RF012 Implementado; RF010/RF013/RF014 Parcial | `ContractLabourComplianceTest`, contract metadata, documentos. | Clausulas legais parametrizadas e timeline contratual unica ainda nao estao completas. | Contratos juridicamente fracos ou historico disperso. | Alta | Validar modelos contratuais por regime e criar timeline/document taxonomy final. |
| RF015-RF018 | Periodo probatorio, alertas, avaliacao e cessacao no probatorio. | RH RF015-RF018 | Contratos/Probatorio | RF015-RF017 Implementado tecnicamente; RF018 Pendente | `HrmEmployeeLegalProfilesTest` valida limites de probatorio. | Fluxo de cessacao especifica no periodo probatorio sem ponta a ponta dedicado. | Risco de cessacao mal documentada no probatorio. | Alta | Implementar/validar fluxo dedicado de cessacao em probatorio antes de usar em producao. |
| RF019-RF022 | Cadastro trabalhador, dependentes, dossier digital e dados sensiveis. | RH RF019-RF022 | Cadastro RH | Implementado tecnicamente; RF021 Parcial | `EmployeeLegalProfileController`, dependents, masking sensitive data, tests. | Dossier digital orientado a compliance ainda requer consolidacao documental. | Dados pessoais/fiscais incompletos ou exposicao indevida. | Critica | Revisar permissoes por dado sensivel e checklist documental por trabalhador. |
| RF023-RF032 | Assiduidade, biometria, faltas, horarios, horas extra, noturno e descanso. | RH RF023-RF032 | Assiduidade | Implementado tecnicamente; RF023/RF028/RF029/RF031 Requerem Validacao | `MozambiqueLabourRulesTest`, biometric ingest tests, attendance cancellation tests. | Escalas/remoto complexos, adicional noturno e defaults legais finais precisam validacao juridica. | Salario, faltas, ferias e disciplina podem ser calculados errado. | Critica | Validar regras de overtime/noturno/descanso por Lei 13/2023 e politica interna. |
| RF033-RF037 | Ferias: calculo, plano anual, aprovacao, compensacao e faltas. | RH RF033-RF037 | Ferias | Implementado tecnicamente | `MozambiqueLabourRulesTest`, `HrmAnnualLeavePlanWorkflowTest`, reports. | Indicadores/reportes finais de ferias ainda requerem aceite operacional. | Saldos incorretos de ferias e passivo laboral. | Alta | Simular 1o, 2o e 3o ano, cash-out e faltas injustificadas. |
| RF038-RF042 | Licencas maternidade, paternidade, adocao, doenca e outras ausencias. | RH RF038-RF042 | Ausencias | Implementado tecnicamente | `MozambiqueLabourRulesTest` cobre maternidade, paternidade, doenca, adocao. | Tipos adicionais devem ser parametrizados pela empresa. | Licencas legais podem ser recusadas/calculadas erradamente. | Alta | Cadastrar tipos de ausencia e validar documentos exigidos. |
| RF043-RF047 | Payroll, folha, recibo, pagamento bancario, INSS/IRPS base. | RH RF043-RF047 | Payroll | Implementado tecnicamente; RF043-RF046 Parcial/Requer Validacao | `HrmPayrollSubmissionExportsTest`, `HrmPayrollSubmissionFormatsAndApiTest`, payroll journal tests. | Matriz tributavel/nao tributavel e assinatura/confirmacao digital de recibo precisam validacao final. | Salario liquido, IRPS/INSS e recibos podem sair incorretos. | Critica | Validar folha com casos reais por sector, dependentes e componentes salariais. |
| RF048-RF056 | INSS, IRPS, Modelo 19, historico fiscal e declaracoes. | RH RF048-RF056 | Payroll fiscal | Implementado tecnicamente | Export Modelo 19/INSS CSV/XML/XLSX/API, fiscal history tests. | Valores/tabelas oficiais e prazos precisam revisao externa e setup ativo em producao. | Submissao fiscal/parafiscal incorreta. | Critica | Validar tabelas oficiais, export gerado e prazos com contabilista/fiscalista. |
| RF057-RF061 | Trabalhadores estrangeiros, regimes, quotas, documentos e cessacao. | RH RF057-RF061 | Expatriados | Implementado tecnicamente | Quota tests, foreign worker profile, cessation notification five-day test, expatriate report. | Validar decreto/regimes especiais e documentos reais. | Excesso de quota ou comunicacao tardia a entidades. | Critica | Testar admissao/cessacao de estrangeiro com comprovativos reais. |
| RF062-RF067 | Avaliacao, planos de melhoria, formacao e formacao obrigatoria. | RH RF062-RF067 | Desempenho/Formacao | Parcialmente Implementado | Training compliance reports e dashboard. | Plano formal de melhoria e plano anual de formacao legal ainda precisam estrutura/aceite final. | Falta de evidencia de capacitacao obrigatoria. | Media | Consolidar workflow de plano de melhoria e plano anual de formacao. |
| RF068-RF077 | Disciplina, nota de culpa, prazos, assedio, denuncia e codigo assinado. | RH RF068-RF077 | Disciplina/Assedio | Implementado tecnicamente | `HrmHarassmentDisciplinaryWorkflowTest`, disciplinary reports, confidential access. | Validar prazos legais, templates e testemunhas em documentos reais. | Processo disciplinar pode ser anulavel. | Critica | Revisao juridica dos templates e simulacao de processo completo. |
| RF078-RF083 | Cessacao/offboarding, pre-aviso, indemnizacao, acerto final e checklist. | RH RF078-RF083 | Offboarding | Parcialmente Implementado | Offboarding fields/reports; payroll/acerto parcial. | Taxonomia legal, pre-aviso por caso e calculo de indemnizacao exigem validacao juridica final. | Pagamento final ou cessacao incorreta gera passivo laboral. | Critica | Validar cenarios de cessacao e formulas com juridico/RH. |
| RF084-RF088 | Perfis, permissoes, dados sensiveis, auditoria e historico inalteravel. | RH RF084-RF088 | Seguranca RH | Parcialmente Implementado | Sensitive masking, audit trail, cancellation controls, tests. | Granularidade por filial/departamento/nivel hierarquico precisa revisao final. | Vazamento de dados sensiveis e fraqueza de auditoria. | Critica | Revisar matriz de permissoes RH antes de dados reais. |
| RF089-RF096 | Relatorios legais/gerenciais, alertas e dashboard de compliance. | RH RF089-RF096 | Reporting RH | Implementado tecnicamente; RF089-RF092 Parcial | Compliance dashboard tests, workforce/payroll/leave/attendance/disciplinary/expatriate reports. | Layouts/filtros e apresentacao legal precisam aceite final. | Gestao pode ignorar riscos laborais/fiscais. | Alta | Validar relatorios com dados simulados e reais de piloto. |
| RF097-RF099 | Integracao payroll-contabilidade, centros de custo e exportacao contabilistica. | RH RF097-RF099 | Integracao RH/Contabilidade | Implementado tecnicamente | `HrmPayrollAccountingJournalTest`, `MozambiquePayrollAccountingExportService`, API exports. | Mapeamentos PGC e centros de custo reais exigem conciliacao com contabilidade. | Lancamentos de payroll podem afetar balancete/INSS/IRPS. | Critica | Executar payroll E2E ate journal e reconciliar contas. |
| RF100-RF103 | Multiempresa, filiais, parametrizacao legal e import/export. | RH RF100-RF103 | Administracao RH | Parcialmente Implementado | Import APIs, tenant scoped tests, legal settings service. | Cobertura uniforme de import/export e parametros legais precisa fechamento operacional. | Dados entre empresas ou parametros incompletos podem comprometer operacao. | Alta | Testar import/export e isolamento multiempresa em staging. |

### SCE RF001-RF096

| Requisito(s) | Descricao consolidada | Origem | Modulo | Estado atual | Evidencia objetiva | Lacunas identificadas | Impacto em producao | Prioridade | Acao antes do go-live |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| RF001-RF004 | Configuracao inicial SCE, perfil fiscal, classificacao, referencial e exercicio. | SCE RF001-RF004; prontidao SCE | SCE | Parcialmente Implementado | `CompanyFiscalProfile`, `SceSetupCommand`, fiscal periods/closing. | Classificacao/referencial devem ser validados por empresa e com regras oficiais. | Base contabilistica errada contamina todos os mapas. | Critica | Executar `sce:setup`, revisar perfil fiscal e referencial contabilistico. |
| RF005-RF009 | PGC, classes, hierarquia, contas movimento e parametrizacao fiscal. | SCE RF005-RF009 | Contabilidade | Implementado tecnicamente; Requer Validacao | `PgcImportService`, campos PGC, `is_movement_account`, `mz_tax_account_mappings`. | Validar catalogo PGC-MZ, classes 0-9 e contas de movimento contra contabilista. | Lancamentos em contas erradas e SAF-T inconsistente. | Critica | Importar PGC oficial e reconciliar mapas com SCE. |
| RF010-RF014 | Partidas dobradas, diarios, numeracao, recorrentes e anexos. | SCE RF010-RF014 | Contabilidade | Parcialmente Implementado | `AccountingJournalController`, `ProcessRecurringJournals`, `JournalNumberingService`. | Anexos/documento suporte por diario e bloqueio por periodo precisam validacao total. | Lancamentos sem suporte ou fora de periodo fechado. | Alta | Validar journal manual/automatico, recorrente e fecho de periodo. |
| RF015-RF026 | Clientes, fornecedores, AR/AP, faturacao, documentos, hash e autofactura. | SCE RF015-RF026; Faturacao RF005-RF019 | Faturacao/AR/AP | Implementado tecnicamente; autofactura/proforma Requer Validacao | Mesmas evidencias do bloco de faturacao; series/hash/snapshots/tests. | Cobertura de todos os tipos documentais fiscais deve ser testada. | Documento fiscal invalido ou nao aceite. | Critica | Executar matriz documental completa em staging. |
| RF027-RF032 | IVA, declaracao, reverse charge e dedutibilidade. | SCE RF027-RF032 | IVA | Implementado tecnicamente; Requer Validacao | `VatCalculationService`, `FiscalDeclarationService`, tests de IVA especial. | Dedutibilidade e modelos oficiais precisam validacao. | Declaracao IVA incorreta. | Critica | Validar mapa IVA com contabilista. |
| RF033-RF039 | Resultado contabilistico, correcoes fiscais, materia colectavel, IRPC e tributacao autonoma. | SCE RF033-RF039 | IRPC/Fecho anual | Parcialmente Implementado / Requer Validacao | `IrpcCalculationService`, `TaxController`, annual declaration endpoints/tests. | Formulas oficiais, Modelo 20, beneficios, correcoes e tributacao autonoma precisam validacao profunda. | IRPC anual errado e risco fiscal relevante. | Critica | Fazer validacao anual com dados de exemplo e contabilista. |
| RF040-RF047 | Retencoes, IRPS, trabalhadores, payroll, INSS e lancamento salarial. | SCE RF040-RF047; RH RF043-RF056/RF097 | Payroll/Contabilidade | Implementado tecnicamente; Requer Validacao | Withholding/Payroll services, tests de Modelo 19, INSS e journal payroll. | Valores oficiais/tabelas e mapeamento PGC requerem aceite. | Folha e lancamentos fiscais incorretos. | Critica | Conciliar payroll, INSS, IRPS e journal em staging. |
| RF048-RF052 | Artigos, FIFO/custo medio, movimentos stock, stock-contabilidade e biologicos. | SCE RF048-RF052 | Inventario | Parcialmente Implementado | `InventoryCostingService`, `StockMovement`, `StockCostLayer`. | Ativos biologicos nao evidenciados; inventario precisa E2E real com compras/vendas/COGS. | Margem, CMVMC e stock fiscal incorretos. | Alta | Testar inventario FIFO/custo e definir se biologicos ficam fora do go-live. |
| RF053-RF057 | Ativos fixos, depreciacao, imparidade, reavaliacao e alienacao. | SCE RF053-RF057 | Ativos | Parcialmente Implementado | `FixedAsset`, `DepreciationService`, `FixedAssetController`, tests de acesso/criacao. | Imparidade, reavaliacao e alienacao requerem fluxo fiscal completo. | Balanco/DR podem ficar incorretos para ativos. | Media/Alta | Validar se cliente usara ativos no go-live; se sim, testar depreciacao e baixa. |
| RF058-RF062 | Bancos, reconciliacao, caixa, moeda estrangeira e cash-flow. | SCE RF058-RF062; Faturacao RF050-RF061 | Tesouraria | Implementado tecnicamente; Requer Validacao | Bank import/reconciliation, cash closings, FX reports/tests. | Formatos bancarios locais e cash-flow SCE precisam aceite. | Saldos bancarios/caixa inconsistentes. | Alta | Testar extratos reais e fecho diario de caixa. |
| RF063-RF068 | Balanco, DR, DFC, DCP, notas e balancete. | SCE RF063-RF068 | Relatorios financeiros | Parcialmente Implementado / Requer Validacao | DoubleEntry reports, fiscal maps, annual declaration endpoints. | DCP e notas legais completas nao estao comprovadas; mapeamento SCE oficial precisa validacao. | Demonst. financeiras podem nao cumprir SCE. | Critica | Validar mapas oficiais com contabilista antes de vender como SCE completo. |
| RF069-RF076 | Calendario fiscal, Modelo 20, declaracao anual, guias, SAF-T, validacao e historico. | SCE RF069-RF076 | Obrigações fiscais | Implementado tecnicamente; SAF-T/Modelo 20 Requer Validacao | `SyncFiscalCalendar`, `TaxController`, `SaftExportService`, `FiscalExportHistory`, tests. | Validacao oficial SAF-T/Modelo 20/submissao real nao comprovada. | Rejeicao fiscal ou declaracoes incompletas. | Critica | Validar XML/CSV oficiais e comprovativos. |
| RF077-RF079 | Centros de custo, alocacao e relatorios analiticos. | SCE RF077-RF079; RH RF098 | Analitica | Parcialmente Implementado | `cost_centers`, payroll cost allocation tests. | Uso consistente em faturacao, pagamentos e journals precisa validacao. | Custos/proveitos por centro incorretos. | Alta | Definir dimensoes analiticas e testar journals/reporting. |
| RF080-RF082 | Importacoes, landed cost e bloqueio documental/licencas. | SCE RF080-RF082 | Importacoes/Aduanas | Parcialmente Implementado | `ImportProcess`, customs fields, exchange dossier customs refs. | Workflow de importacao com bloqueio por licenca/documentacao nao esta comprovado. Ha IVA de importacao hardcoded a 16% em `ImportProcess`. | Custo de importacao, IVA e documentos aduaneiros podem sair errados. | Alta | Parametrizar IVA importacao e executar piloto de importacao se for escopo do cliente. |
| RF083-RF087 | Roles, permissoes, auditoria, bloqueios criticos e backup. | SCE RF083-RF087 | Seguranca/Operacao | RF083-RF086 Parcialmente Implementado; RF087 Pendente | Roles/perms, audit trail, hardening tests. | Nao ha evidencia de politica operacional de backup/restore testado nesta auditoria. | Perda de dados ou falta de recuperacao em incidente. | Critica | Definir e testar backup/restore antes de producao. |
| RF088-RF092 | Fecho mensal/anual, reabertura, alertas e validacoes automaticas. | SCE RF088-RF092 | Fecho/Compliance | Parcialmente Implementado | `mz_fiscal_closings`, checklist, fiscal compliance alerts, tests. | Reabertura/aprovacao e checklist completo devem ser testados com periodo real. | Lancamentos apos fecho ou fecho incompleto. | Critica | Rodar fecho mensal piloto e validar bloqueios. |
| RF093-RF096 | Multiempresa, filiais, parametrizacao de impostos e import/export. | SCE RF093-RF096 | Administracao | Parcialmente Implementado | Tenant isolation, establishment series, tax settings, exports/imports. | Filial unificada e import/export massivo ainda nao estao completamente fechados. | Mistura de dados ou operacao manual excessiva. | Alta | Validar tenant/filial/series e plano de importacao inicial. |

## 6. Estado por modulo

| Modulo | Estado tecnico | Estado para go-live | Observacao |
| --- | --- | --- | --- |
| Faturacao | Forte | Apto com restricoes apos migrate/smoke/legal validation | Series, hash, prazo legal, IVA, reverse charge e relatorios existem; tipos documentais e certificacao exigem validacao. |
| Tesouraria | Forte | Apto com restricoes | FX, repatriamento, GIFiM, moeda electronica, caixa, aprovacoes e reports existem; precisa piloto bancario/caixa real. |
| Contabilidade/SCE | Medio-forte | Apto com restricoes apenas para escopo validado | PGC, journals, fiscal maps, ativos, inventario e fechos existem, mas demonstracoes oficiais, IRPC anual e SAF-T oficial exigem validacao contabilistica. |
| Recursos Humanos | Forte | Apto com restricoes | Payroll, INSS, IRPS, RH legal e dashboards existem; cessacao probatorio, politicas, indemnizacoes e validacao juridica ainda precisam fechamento. |
| SCE | Medio-forte | Parcialmente apto | Muitos gaps antigos foram reduzidos; backup/restore, importacao aduaneira, mapas SCE oficiais e validacao legal ainda impedem assinatura sem ressalvas. |
| Conformidade fiscal | Forte tecnicamente | Requer validacao formal | O sistema controla riscos; conformidade final depende de tabelas oficiais, schema SAF-T, certificacao e atestacoes. |
| Integracoes | Medio-forte | Requer E2E em staging/producao | Payroll-contabilidade, vendas-contabilidade, compras, IVA, bancos e FX precisam reconciliacao real. |

## 7. Requisitos concluidos

Concluidos tecnicamente com evidencia objetiva:

- Faturacao/tesouraria/fiscal: NUIT e classificacao de clientes/fornecedores, emissao fiscal base, prazo de emissao tardia com justificativa, series, hash, bloqueios, IVA especial, reverse charge, SAF-T export/historico, AR/AP, FX, repatriamento, ADT, retencoes, GIFiM, moeda electronica, caixa, relatorios financeiros/fiscais, permissoes e auditoria base.
- RH: recrutamento legal, contratos a prazo com justificativa, probatorio com limites, dependentes, INSS, estrangeiros/quotas, assiduidade/biometria, ferias/licencas, horas extra, payroll, Modelo 19, INSS, relatórios de RH, assedio/disciplina, offboarding base, auditoria, import/export e integracao payroll-contabilidade.
- SCE: setup, perfil fiscal, PGC/import, journals, recorrentes, stock costing, ativos fixos/depreciacao base, calendario fiscal, declaracoes fiscais tecnicas, fiscal closings, alertas, series, SAF-T e readiness.

## 8. Requisitos pendentes ou incompletos

Pendentes/incompletos por prioridade:

- Critica: migrations nao validadas em MySQL; sem isso nenhum RF novo pode ser considerado operacional em producao.
- Critica: atestacoes de go-live nao comprovadas: revisao legal/fiscal, aprovacao comercial, piloto real, evidencias assinadas, validacao payroll/contabilidade, E2E e aprovacao formal.
- Critica: SAF-T MZ/schema/certificacao fiscal requer validacao oficial.
- Critica: backup/restore operacional nao comprovado.
- Critica: demonstracoes financeiras SCE oficiais, IRPC anual, Modelo 20 e PGC-MZ completo requerem validacao contabilistica.
- Alta: RH RF018 cessacao no periodo probatorio ainda nao tem fluxo dedicado de ponta a ponta.
- Alta: HR RF078-RF083 cessacao/indemnizacoes/acerto final precisam validacao juridica por caso.
- Alta: importacoes/aduanas RF080-RF082 estao parciais; IVA de importacao nao deve ficar hardcoded.
- Alta: matriz de permissoes por valor/moeda/conta/filial/centro de custo precisa teste com roles reais.
- Media/Alta: ativos fixos avancados, imparidade, reavaliacao e alienacao precisam fluxo completo se forem usados no go-live.
- Media: import/export massivo e formatos bancarios locais podem ficar para pos-producao se nao forem requisito do primeiro cliente.

## 9. Requisitos duplicados, sobrepostos ou conflitantes

Duplicacoes/sobreposicoes identificadas:

- Clientes/fornecedores, NUIT, classificacao fiscal e contas correntes aparecem no relatorio de faturacao e no SCE.
- Faturacao fiscal, series, hash, inalterabilidade, SAF-T e submissao aparecem no SCE e no relatorio de faturacao/tesouraria.
- IVA, reverse charge e retencoes aparecem no SCE e no relatorio fiscal/tesouraria.
- Payroll, IRPS, INSS, Modelo 19 e lancamentos contabilisticos aparecem em RH e SCE.
- Centros de custo aparecem em RH, SCE e faturacao/tesouraria.
- Auditoria, permissoes e multiempresa aparecem em todos os relatorios.

Conflitos por obsolescencia:

- O relatorio SCE de 22/05 e o relatorio de faturacao de 01/06 marcam como pendentes itens que hoje possuem evidencia tecnica posterior, como hash fiscal, reverse charge, historico SAF-T, ADT, GIFiM, moeda electronica e controlo cambial.
- O relatorio RH de 27/05 ficou parcialmente ultrapassado pelo status de 01/06 e pelos testes atuais; deve ser considerado documento historico, nao fonte final de estado.
- O roadmap de 03/06 dizia que P0/P1/P2 ainda seriam executados; parte substancial foi implementada depois, mas os gates de producao continuam pendentes.

## 10. Riscos criticos para producao

1. **Base de dados nao validada**: `migrate:status` falhou por MySQL indisponivel. Impacto: migrations podem falhar em producao.
2. **Worktree nao versionado**: muitas migrations e ficheiros novos estao untracked. Impacto: deploy nao reproduzivel.
3. **Conformidade legal nao assinada**: mecanismo de atestacao existe, mas evidencias reais nao foram verificadas. Impacto: falso go-live legal.
4. **SAF-T/certificacao**: XML gerado tecnicamente, mas schema oficial/certificacao fiscal nao comprovados. Impacto: rejeicao/nao conformidade fiscal.
5. **Valores legais parametrizados**: IRPS, INSS, IVA, ADT, limites GIFiM/IME e regras laborais precisam validacao externa. Impacto: calculos legais errados.
6. **SCE oficial**: mapas financeiros, IRPC, Modelo 20 e PGC exigem reconciliacao com contabilista. Impacto: demonstracoes incorretas.
7. **Backup/restore**: sem evidencia de teste de recuperacao. Impacto: risco operacional inaceitavel.
8. **Importacao/aduanas**: fluxo parcial e IVA hardcoded em modelo de importacao. Impacto: custo fiscal/aduaneiro incorreto.
9. **Permissoes reais**: roles existem, mas precisam validacao por utilizadores reais. Impacto: acesso indevido a dados sensiveis.
10. **Piloto real**: sem piloto com dados reais, E2E e assinaturas, nao ha prova de operacao.

## 11. Validacao de conformidade mocambicana

Estado de conformidade por tema:

- Faturacao: tecnicamente forte; requer validacao de certificacao, todos os tipos documentais e regra oficial de SAF-T/AT.
- IVA: calculo e codigos existem; requer validacao dos prazos, declaracao oficial e cenarios sem actividade.
- Retencoes/ADT: implementado tecnicamente; requer validacao juridica por pais, rendimento, certificado e taxa.
- GIFiM/AML: thresholds e comunicacao existem; requer validacao do formato/processo oficial de comunicacao.
- Controlo cambial: implementado tecnicamente; requer validacao documental com banco e casos reais.
- RH/Laboral: regras principais existem; cessacoes, indemnizacoes e politica interna exigem revisao juridica final.
- Payroll/IRPS/INSS: export e calculos existem; precisa validar tabelas oficiais, dependentes e componentes tributaveis.
- SCE/Contabilidade: base tecnica existe; mapas oficiais, PGC, Modelo 20, IRPC e fecho anual precisam validacao contabilistica.

## 12. Checklist final de prontidao para go-live

Checklist obrigatorio:

- [ ] Limpar/organizar worktree, incluir migrations e codigo relevante no Git.
- [ ] Criar commit e tag de release.
- [ ] Subir para staging equivalente a producao.
- [ ] Executar `php artisan migrate --force`.
- [ ] Executar `php artisan optimize:clear` e `php artisan config:cache`.
- [ ] Executar `php artisan sce:setup --company=<id> --year=2026`.
- [ ] Executar `php artisan account:sync-finance-roles`.
- [ ] Executar `php artisan sce:sync-fiscal-calendar --years=2`.
- [ ] Executar `php artisan sce:sync-fiscal-compliance-alerts`.
- [ ] Executar `php artisan hrm:sync-compliance-alerts`.
- [ ] Executar suite alvo de testes em CI/staging.
- [ ] Executar `npm run build`.
- [ ] Validar login, dashboard, faturacao, pagamento, SAF-T, relatorios, payroll e HR com usuario real.
- [ ] Registar tabelas legais ativas: IVA, IRPS, INSS, ADT, limites GIFiM/IME, prazos fiscais e regras laborais.
- [ ] Registar piloto real: empresas piloto, casos payroll/contabilidade, evidencias e assinaturas.
- [ ] Registar aprovacao legal/fiscal e aprovacao comercial no painel de readiness.
- [ ] Testar backup e restore antes do go-live.
- [ ] Executar smoke test HTTP e healthcheck apos deploy.

## 13. Decisao recomendada

Decisao atual: **Nao apto para producao imediata**.

Motivo: apesar de a implementacao tecnica estar avancada e com boa cobertura de testes, nao ha validacao de migrations em base real, ha alteracoes nao versionadas, e os gates formais de readiness/legal/piloto nao foram comprovados.

Decisao possivel apos correcoes obrigatorias: **Apto com restricoes**.

Restricoes aceitaveis para primeira versao:

- Sem webservice automatico oficial com AT, desde que export/submissao manual fiquem auditados.
- Import/export massivo completo pode ficar para pos-producao.
- BI/dashboards avancados podem ficar para pos-producao.
- Regras avancadas de moeda electronica por nivel podem evoluir depois se o cliente inicial nao usar esse canal.
- Ativos fixos avancados, importacoes e aduanas podem ficar restritos se o cliente nao os usar no primeiro go-live.

## 14. Correcoes obrigatorias antes do go-live

1. Resolver base MySQL/staging e executar `php artisan migrate --force` com sucesso.
2. Commitar todas as alteracoes relevantes, incluindo migrations novas, comandos, services, controllers, requests, tests e docs.
3. Rodar suite alvo completa em CI/staging e arquivar output.
4. Rodar `npm run build` e decidir se o warning CSS deve ser corrigido antes da release.
5. Validar SAF-T com schema/validador oficial ou parecer fiscal documentado.
6. Validar tabelas legais: IVA, IRPS, INSS, ADT, GIFiM, moeda electronica, prazos fiscais e regras laborais.
7. Remover ou parametrizar IVA hardcoded de importacao em `ImportProcess`.
8. Executar e documentar piloto com pelo menos uma empresa real ou massa de dados representativa assinada.
9. Executar E2E: venda -> documento fiscal -> pagamento -> journal -> IVA -> SAF-T; compra -> pagamento -> retencao -> journal; payroll -> INSS/IRPS -> journal.
10. Validar matriz de permissoes e dados sensiveis com perfis reais.
11. Testar backup/restore e documentar RPO/RTO.
12. Preencher readiness: revisao legal/fiscal, comercial, piloto, validacoes reais, E2E e aprovacao formal.

## 15. Conclusao final

O projeto esta tecnicamente muito mais maduro do que os relatorios iniciais indicavam. A maior parte dos requisitos criticos de faturacao, tesouraria, conformidade fiscal e RH tem implementacao objetiva e testes. A arquitetura tambem ja contem mecanismos adequados para readiness, auditoria, compliance alerts, historico fiscal e integracoes contabilisticas.

Mesmo assim, a decisao segura antes de producao e bloquear go-live imediato. O sistema so deve ir para producao depois de validar migrations em ambiente real, versionar o pacote, assinar as validacoes legais/fiscais, executar piloto/E2E com evidencias e confirmar backup/restore. Sem esses passos, o risco nao e falta de codigo; o risco e operar legalmente e financeiramente sem prova suficiente de conformidade.
