# Backlog tecnica de implementacao go-live Mocambique

Base: relatorio final consolidado de prontidao go-live e execucao tecnica posterior.

## 1. Release, Git, worktree e controlo de versao

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| REL-001 | Release/Git | Rever worktree grande e separar alteracoes por tema | P0 | Sec. 10, 12, 14 | Deploy nao reproduzivel | Git, migrations, docs, tests | Auditar `git status`, classificar ficheiros e remover artefactos | `git diff --check` | So codigo/docs necessarios ficam no release | Lista de ficheiros aprovada |
| REL-002 | Release/Git | Commitar todas as alteracoes relevantes | P0 | Sec. 14.2 | Codigo novo fora do deploy | Repo completo | Criar commit(s) por bloco ou release unico controlado | CI alvo | Commit contem migrations, services, UI, tests, docs | Hash do commit |
| REL-003 | Release/Git | Criar tag/versionamento de release | P1 | Sec. 12, 14 | Rollback dificil | Git/deploy | Criar tag `vYYYY.MM.DD` ou similar | `git show --stat <tag>` | Tag aponta para commit validado | Tag publicada |
| REL-004 | Release/Git | Gerar pacote/release candidate | P1 | Sec. 13, 14 | Staging diferente do local | deploy/scripts | Empacotar release sem ficheiros gerados | Build + tests | Pacote instalavel em staging | Artefacto de release |
| REL-005 | Release/Git | Registar changelog tecnico | P2 | Sec. 15 | Baixa rastreabilidade | docs | Documentar mudancas criticas e riscos | N/A | Changelog inclui modulos alterados | Changelog versionado |

## 2. Base de dados, migrations e staging

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DB-001 | DB/Staging | Executar migrations em staging MySQL real | P0 | Sec. 8, 10, 14.1 | Migration falha em producao | `database/migrations`, package migrations | `php artisan migrate --force` | `migrate:status` | Todas migrations `Ran` | Output arquivado |
| DB-002 | DB/Staging | Validar `migrate:fresh` nao basta; testar upgrade incremental | P0 | Sec. 10 | Fresh passa, upgrade falha | Migrations recentes | Subir dump/staging e migrar incrementalmente | `migrate --force` | Sem erro em schema existente | Log staging |
| DB-003 | DB/Staging | Executar comandos SCE/RH pos-migration | P1 | Sec. 12 | Calendario/roles/compliance ausentes | Console commands | Rodar `sce:setup`, sync fiscal/RH/roles | Testes setup | Dados gerados por empresa | Logs comandos |
| DB-004 | DB/Staging | Validar backfill de campos novos | P1 | Sec. 14 | Registos antigos ficam inconsistentes | import VAT, fiscal exports, payments, HR | Criar scripts/backfill se necessario | Queries de integridade | Zero registos criticos invalidos | Relatorio DB |
| DB-005 | DB/Staging | Validar staging equivalente a producao | P0 | Sec. 13 | Resultado falso em ambiente local | `.env`, MySQL, Redis, queue | Configurar staging com mesmos drivers | Healthcheck | Staging reproduz producao | Checklist infra |

## 3. Readiness, atestações e gates de go-live

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| RDY-001 | Readiness | Preencher revisao legal/fiscal | P0 | Sec. 8, 12, 14.12 | Falso go-live legal | Go-live readiness | Fiscalista aprova e regista data/notas | `MozambiqueGoLiveReadinessTest` | Gate legal `pass` | Atestacao no sistema |
| RDY-002 | Readiness | Registar aprovacao comercial | P1 | Sec. 12 | Lancamento sem aceite interno | Readiness UI/API | Aprovacao comercial datada | Teste readiness | Gate comercial `pass` | Atestacao |
| RDY-003 | Readiness | Registar piloto com empresa real | P0 | Sec. 8, 14.8 | Sem prova operacional | `mz_pilot_companies` | Inserir empresa piloto e evidencia assinada | Readiness tests | `pilot_real_companies_validated=true` | Evidencia anexada/ref |
| RDY-004 | Readiness | Registar casos reais payroll/contabilidade | P0 | Sec. 8, 12 | Calculos legais nao provados | `mz_pilot_validation_cases` | Criar casos `payroll` e `accounting` | Readiness tests | Ambos validados | Casos `passed` |
| RDY-005 | Readiness | Registar E2E final | P0 | Sec. 12, 14.9 | Fluxos integrados quebram | Readiness | Marcar vendas, compras, POS, payroll | E2E manual/autom. | `e2e_scenarios_completed=true` | Evidencia E2E |
| RDY-006 | Readiness | Registar backup/restore no readiness | P0 | Sec. 14.11 | Sem DR comprovado | Readiness + script backup | Guardar manifesto e data de restore | Verify restore | `backup_restore_verified=true` | Manifesto |
| RDY-007 | Readiness | Aprovacao formal go-live | P0 | Sec. 12, 13 | Go-live sem owner | Readiness | Aprovacao final apos gates | Readiness endpoint | `recommended_for_launch=true` | Atestacao final |

## 4. SAF-T, certificação fiscal, tabelas legais e conformidade moçambicana

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SAF-001 | SAF-T | Validar XML contra XSD/validador oficial | P0 | Sec. 8, 10, 14.5 | SAF-T rejeitado | `SaftExportService`, config `sce.saft` | Obter XSD oficial/validador AT | Export SAF-T | XML aceite | Relatorio validacao |
| SAF-002 | SAF-T | Configurar gate XSD em producao | P0 | Sec. 11, 14.5 | Export invalido sem bloqueio | `.env`, readiness | Definir `SAFT_MZ_REQUIRE_XSD_VALIDATION` e path | Readiness | Gate XSD `pass` | Config + output |
| SAF-003 | Fiscal | Preparar dossier de certificacao fiscal | P0 | Sec. 10, 11 | Software nao certificado | Fiscal docs, hash, series, audit | Reunir evidencias de inalterabilidade/series/auditoria | Fluxos fiscais | Dossier completo | Parecer/certificacao |
| SAF-004 | Legal | Validar tabelas IVA/IRPS/INSS/ADT/GIFiM/IME | P0 | Sec. 8, 11, 14.6 | Calculos legais errados | `MzVatCode`, withholding, HR settings | Fiscalista/contabilista valida vigencias | Unit + amostras | Tabelas aprovadas | Ata/parecer |
| SAF-005 | Fiscal | Validar prazos fiscais e calendario | P1 | Sec. 12, 14 | Obrigacoes fora do prazo | `SyncFiscalCalendar` | Conferir eventos 2026+ | Calendar tests | Eventos corretos | Export calendario |
| SAF-006 | Fiscal | Validar submissao manual SAF-T | P1 | Sec. 5 RF029-RF033 | Sem historico AT | `FiscalExportHistory` | Submeter/exportar piloto e registar comprovativo | Export history tests | Status `submitted/validated` | Referencia AT |

## 5. Faturação, IVA, documentos fiscais e séries

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FAT-001 | Faturação | Testar matriz completa de documentos fiscais | P0 | Sec. 5 RF012-RF019 | Documento invalido | Sales invoice, credit/debit notes, guides | Validar factura, FR, NC, ND, guias, proforma/autofactura se escopo | Fiscal compliance tests | Todos fluxos OK | Matriz assinada |
| FAT-002 | Faturação | Validar series por filial/estabelecimento/terminal | P0 | Sec. 5 RF015, RF089-RF092 | Numeracao fiscal errada | `FiscalDocumentSeries` | Criar series e emitir documentos | Series tests | Sequencia unica/cronologica | Log de emissao |
| FAT-003 | IVA | Validar cenarios IVA reais | P0 | Sec. 5 RF020-RF028 | IVA declarado errado | `VatCalculationService`, invoices | Testar normal, zero, isento, nao sujeito, digital, reverse, importacao | VAT tests | Totais reconciliados | Mapa IVA |
| FAT-004 | Faturação | Validar bloqueio de alteracoes fiscais em clientes/fornecedores | P1 | Sec. 5 RF005-RF011 | SAF-T inconsistente | Customer/Vendor requests | Testar alteracao apos documento fiscal | NUIT validation tests | Bloqueio ou override auditado | Audit trail |
| FAT-005 | Faturação | Validar retificacao por NC/ND apenas | P1 | Sec. 5 RF016-RF017 | Alteracao direta ilegal | Invoice/notes controllers | Confirmar sem edicao pos-emissao | Fiscal tests | Correcao so por documento rectificativo | Evidencia UI/API |
| FAT-006 | IVA Importacao | Validar `import_vat_rate` em staging/prod | P1 | Sec. 14.7; execucao posterior | Taxa importacao incorreta | `ImportProcess`, migration | Conferir codigo `IMP` ativo e processos antigos | Import VAT tests | Taxa parametrizada aplicada | Query/relatorio |

## 6. Tesouraria, bancos, caixa, FX, GIFiM, moeda eletrónica e remessas

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| TES-001 | Tesouraria | Validar caixa diario e reabertura | P1 | Sec. 5 RF050-RF055 | Saldos caixa errados | `MozCashClosing` | Fechar/reabrir caixa real | Cash closing tests | Saldo conferido | Fecho assinado |
| TES-002 | Bancos | Testar reconciliacao com extratos locais | P1 | Sec. 6, SCE RF058-RF062 | Banco nao reconcilia | `BankTransactionsService` | Importar extrato real | Bank tests/manual | Movimentos reconciliados | Relatorio |
| TES-003 | FX | Validar recebimento externo/repatriamento | P0 | Sec. 5 RF034-RF037, RF056-RF061 | Incumprimento cambial | Customer payments, exchange control | Associar factura exportacao a recebimento | FX tests | Valor repatriado controlado | Dossier cambial |
| TES-004 | Remessas | Validar pagamento internacional com retencao/ADT | P0 | Sec. 5 RF038-RF049 | Remessa irregular | Vendor payments, withholding | Testar sem/ADT/com certificado | Payment tests | Bloqueios e calculo corretos | Comprovativos |
| TES-005 | GIFiM | Validar processo oficial de comunicacao | P0 | Sec. 5 RF062-RF066 | Risco AML/regulatorio | Payment requests, GIFiM report | Validar limites e formato com compliance | GIFiM tests | Alertas e estado comunicacao OK | Relatorio GIFiM |
| TES-006 | IME | Validar limites de moeda electronica | P1 | Sec. 5 RF067-RF070 | Violacao de limites | Bank accounts, payments | Configurar niveis/limites oficiais | IME tests | Bloqueio/alerta correto | Evidencia |
| TES-007 | Adiantamentos | Fechar E2E de adiantamentos/prestacao de contas | P2 | Sec. 5 RF055 | Saldos pendentes errados | Payments/accounting | Validar fornecedores/trabalhadores/clientes | E2E manual | Saldos regularizados | Relatorio |

## 7. Contabilidade/SCE, PGC-MZ, journals, IRPC, Modelo 20 e demonstrações financeiras

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ACC-001 | SCE | Validar PGC-MZ oficial | P0 | Sec. 5 SCE RF005-RF009 | Contas erradas | PGC import, chart accounts | Importar/reconciliar PGC oficial | Accounting tests | Contas/classes aprovadas | Parecer contabilistico |
| ACC-002 | Journals | Validar E2E ate balancete | P0 | Sec. 14.9 | Integracao contabilistica falha | Journals, VAT, payments, payroll | Venda/compra/payroll/FX geram journals | E2E tests | Balancete bate | Relatorio |
| ACC-003 | IRPC/Modelo 20 | Validar formulas oficiais | P0 | Sec. 5 SCE RF033-RF039, RF069-RF076 | Declaracao anual errada | `TaxController`, IRPC services | Testar com contabilista | Tax tests + manual | Modelo validado | Parecer |
| ACC-004 | Demonstrações | Validar Balanco, DR, DFC, DCP, notas | P0 | Sec. 8, 10, 11 | SCE incompleto | Reports/SCE | Conferir mapas oficiais | Reports tests | Mapas aceites | Assinatura contabilista |
| ACC-005 | Fechos | Testar fecho mensal/anual e reabertura | P1 | Sec. 5 SCE RF088-RF092 | Lancamentos apos fecho | Fiscal closings | Fechar periodo real | Closing tests | Bloqueio/reabertura auditados | Ata de fecho |
| ACC-006 | Inventario | Validar FIFO/custo medio/COGS | P1 | Sec. 5 SCE RF048-RF052 | CMVMC errado | Stock services | Testar compra-venda-stock | Inventory tests/manual | Custo reconciliado | Relatorio stock |
| ACC-007 | Ativos | Decidir escopo de ativos avancados | P2 | Sec. 8 | Demonstracoes incorretas se usado | Fixed assets | Validar depreciacao/baixa/reavaliacao se necessario | Asset tests/manual | Escopo aprovado | Decisao formal |
| ACC-008 | Centros custo | Validar dimensoes analiticas | P1 | Sec. 5 RF086-RF088, SCE RF077-RF079 | Custos mal alocados | Cost centers | Mapear por dept/projeto/filial | Cost allocation tests | Relatorios consistentes | Mapa aprovado |

## 8. RH, contratos, assiduidade, payroll, IRPS, INSS, cessação e indemnizações

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| HR-001 | RH | Fechar cessacao no periodo probatorio | P1 | Sec. 8 RH RF018 | Cessacao mal documentada | Contracts/terminations | Fluxo dedicado ou validacao E2E | Contract/termination tests | Documento + auditoria | Caso validado |
| HR-002 | RH | Validar cessacao, indemnizacao e acerto final | P0 | Sec. 8 RH RF078-RF083 | Passivo laboral errado | Terminations, settlement | Testar motivos e formulas por caso | Hrm compensation tests + manual | Calculo aprovado | Parecer juridico |
| HR-003 | Payroll | Validar IRPS/INSS/tabelas salariais reais | P0 | Sec. 8, 11 | Folha ilegal | Payroll services/settings | Conferir tabelas oficiais por ano/setor | Payroll tax tests | Valores batem | Parecer fiscal |
| HR-004 | Payroll | Validar payroll -> pagamento -> journal | P0 | Sec. 14.9 | Contabilidade salarial errada | Payroll accounting | Rodar folha real e exportar journal | Payroll accounting tests | Journal reconciliado | Relatorio |
| HR-005 | RH | Politicas internas e ciencia do trabalhador | P1 | Sec. 5 RH RF001-RF004 | Falha compliance laboral | Hrm documents/acknowledgments | Registrar regulamento/codigo/assedio | HR docs tests/manual | Politica assinada | Dossier RH |
| HR-006 | RH | Validar menores/profissoes reguladas | P1 | Sec. 5 RH RF005-RF008 | Contratacao ilegal | Recruitment | Lista legal externa | Recruitment tests | Bloqueios corretos | Lista aprovada |
| HR-007 | Assiduidade | Validar horarios/noturno/horas extra/descanso | P0 | Sec. 5 RH RF023-RF032 | Salario/faltas errados | Attendance/overtime | Testar turnos reais | Labour rules tests | Limites corretos | Evidencia RH |
| HR-008 | Ferias/licencas | Validar saldos e ausencias reais | P1 | Sec. 5 RH RF033-RF042 | Passivo ferias errado | Leave services | Simular 1o, 2o e 3o ano e licencas | Leave tests | Saldos corretos | Relatorio |
| HR-009 | RH Admin | Testar import/export e multiempresa RH | P2 | Sec. 5 RH RF100-RF103 | Dados misturados | HR import/export/API | Importar massa piloto | Isolation tests | Isolamento OK | Relatorio |

## 9. Segurança, roles, permissões, auditoria e dados sensíveis

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SEC-001 | Seguranca | Sincronizar roles financeiras/RH | P0 | Sec. 12 | Permissoes ausentes | Permission seeders/commands | Rodar `account:sync-finance-roles` | Permission tests | Roles criadas | Log comando |
| SEC-002 | Seguranca | Validar matriz por perfil real | P0 | Sec. 10, 14 | Acesso indevido | Spatie permissions, UI | Testar admin/RH/payroll/tesouraria/auditor | Access tests/manual | Acesso conforme matriz | Matriz assinada |
| SEC-003 | Seguranca | Validar permissoes por valor/moeda/conta/filial | P1 | Sec. 8 | Aprovacao fraca | Payments/workflows | Testar workflows de aprovacao | Payment tests/manual | Bloqueio por regra | Evidencia |
| SEC-004 | Auditoria | Confirmar trilha em acoes criticas | P0 | Sec. 5 RF084, SCE RF083-RF087 | Sem prova fiscal/laboral | `AuditTrailService` | Testar alteracao fiscal, banco, SAF-T, payroll | Audit tests | Audit trail completo | Registos audit |
| SEC-005 | Dados sensiveis | Validar masking/acesso a salario, banco, fiscal, disciplina | P0 | Sec. 8 RH RF022 | Exposicao LGPD/local | HR models/controllers/UI | Testar perfis reais | Employee access tests | Dados restritos | Evidencia UI/API |
| SEC-006 | Inalterabilidade | Confirmar ausencia de delete definitivo critico | P1 | Sec. 5 RF085, RH RF088 | Perda de historico | Fiscal/HR/payment models | Soft cancel com motivo | Cancellation tests | Registo preservado | Audit trail |

## 10. Backup/restore, healthcheck, smoke tests e operação

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OPS-001 | Operacao | Executar backup real antes do deploy | P0 | Sec. 14.11 | Perda de dados | `16_backup_restore_indicoerp.sh` | Rodar `backup` em producao/staging | Gzip check | Dump + manifesto gerados | Manifesto |
| OPS-002 | Operacao | Executar restore de verificacao real | P0 | Sec. 14.11 | Backup nao recuperavel | DB admin creds | Rodar `verify` com `VERIFY_DB_NAME` | Restore verify | DB temporaria importada | Output restore |
| OPS-003 | Operacao | Healthcheck pos-deploy | P0 | Sec. 12, 14 | Produção degradada | deploy scripts | Rodar `06_post_deploy_healthcheck` | Healthcheck | Sem falhas | Log healthcheck |
| OPS-004 | Operacao | Smoke HTTP/login/modulos criticos | P0 | Sec. 12 | App sobe mas fluxo quebra | App web | Testar login, dashboard, faturacao, HR, reports | Manual/smoke | Fluxos OK | Checklist |
| OPS-005 | Operacao | Smoke carga controlada | P1 | Sec. performance/runbook | Saturacao FPM/DB | k6 scripts | Rodar 25/50 VUs conforme janela | k6 | Sem erro, p95 aceitavel | Summary k6 |
| OPS-006 | Operacao | Monitorizar logs 30 min pos-deploy | P0 | Sec. 8 criterio final | Erros silenciosos | Laravel/systemd | Ver logs app/nginx/fpm/queue | grep errors | Sem erros criticos | Log arquivado |
| OPS-007 | Operacao | Ensaiar rollback | P1 | Sec. 7 runbook | Falha sem reversao | Git/deploy/DB restore | Testar rollback de release e DB restore | Smoke apos rollback | Sistema recupera | Ata tecnica |

## 11. Testes E2E e evidências finais de produção

| ID da tarefa | Bloco | Descricao | Prioridade | Origem no relatorio | Risco se nao for feito | Ficheiros ou areas provaveis do codigo | Acao tecnica recomendada | Testes obrigatorios | Criterio objetivo de aceitacao | Evidencia final esperada |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| E2E-001 | E2E | Venda -> documento fiscal -> pagamento -> journal -> IVA -> SAF-T | P0 | Sec. 14.9 | Integracao fiscal falha | Sales, payments, journals, SAF-T | Executar com dados reais | Fiscal/payment tests + manual | Totais reconciliados | Evidencia assinada |
| E2E-002 | E2E | Compra -> pagamento -> retencao -> journal | P0 | Sec. 14.9 | AP/retencoes erradas | Purchases, vendor payments | Testar fornecedor residente/nao residente | AP tests | Retencao/journal correto | Evidencia |
| E2E-003 | E2E | Payroll -> IRPS/INSS -> pagamento -> journal | P0 | Sec. 14.9 | Folha/contabilidade errada | HR/payroll/accounting | Rodar folha piloto | Payroll tests + manual | Mapas e journal batem | Relatorio payroll |
| E2E-004 | E2E | POS/faturacao rapida com fiscal status/cancelamento | P1 | Sec. 5 RF014, POS tests | Venda POS nao conforme | POS fiscal | Testar emissao/cancelamento | POS fiscal tests | Hash/status corretos | Evidencia POS |
| E2E-005 | E2E | Exportacao/FX/repatriamento/dossier cambial | P0 | Sec. 5 RF037, RF056-RF061 | Incumprimento cambial | Exchange control | Testar invoice export + recebimento externo | FX tests/manual | Dossier completo | Evidencia banco |
| E2E-006 | E2E | UAT cliente com massa representativa | P0 | Sec. 8, 12 | Sistema tecnicamente OK mas operacionalmente nao aceite | Todos modulos | Executar roteiro de apresentacao/piloto | Checklist UAT | Cliente assina aceite | Termo de aceite |

## Notas finais

- `Requer validacao legal/fiscal/contabilistica` aplica-se sobretudo a SAF-T, tabelas legais, PGC-MZ, IRPC/Modelo 20, payroll legal, ADT, GIFiM e limites IME.
- Itens P0 sao bloqueadores reais de producao.
- Itens P1 devem entrar no release candidate se o cliente usar o respetivo fluxo.
- Itens P2/P3 podem ficar para pos-go-live apenas se forem explicitamente excluidos do escopo contratual inicial.
