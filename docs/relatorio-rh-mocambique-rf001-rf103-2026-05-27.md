# Relatorio RH Mocambique - RF001 a RF103

Data: 27 de Maio de 2026

Escopo: auditoria funcional e tecnica das funcionalidades de Recursos Humanos ja implementadas no `sysgest`, cruzadas com os requisitos RF001-RF103 enviados para o contexto mocambicano.

## 1. Conclusao executiva

O sistema tem uma base ampla de RH, mas o modulo ainda nao esta completo para operar como RH legalmente robusto em Mocambique.

O que ja existe:
- Cadastro de trabalhadores, filiais, departamentos, cargos, turnos, documentos e dossiers basicos.
- Recrutamento com vagas, candidatos, entrevistas, avaliacoes, ofertas e onboarding.
- Contratos genericos com tipos, anexos, assinaturas, renovacoes, notas e comentarios.
- Assiduidade, clock-in/clock-out, ferias/licencas genericas, horas extra e payroll.
- Parametrizacao inicial de IRPS, INSS, salario minimo, limites de overtime e regras de contagem de licencas.
- Exportacao CSV de mapa de payroll com IRPS, INSS e salario minimo.
- Performance e formacao com funcionalidades basicas.
- Advertencias, reclamacoes, documentos internos e acknowledgments.

O que falta para conformidade forte em Mocambique:
- Modelo legal completo de trabalhador: INSS, dependentes IRPS, documentos de identificacao, estrangeiro, visto, autorizacao de trabalho, quota, passaporte, validade documental.
- Contratos localizados: tipo legal, justificacao obrigatoria para prazo, periodo probatorio por categoria, presuncao de contrato sem termo, modelos com clausulas obrigatorias.
- Ferias conforme Lei n.o 13/2023, licencas legais de maternidade/paternidade/adopcao, e impactos legais de faltas.
- Processo disciplinar formal com nota de culpa, prazos, duas testemunhas em recusa, fase de defesa, decisao e invalidade.
- Trabalhadores estrangeiros conforme Decreto n.o 88/2024: quotas, regimes, documentos, prazo de comunicacao de cessacao.
- Offboarding com pre-aviso, indemnizacao, acerto final, checklist, comunicacoes ao INSS/migracao.
- Alertas e painel de compliance laboral.
- Protecao de dados sensiveis por campo, historico inalteravel e auditoria transversal para actos criticos de RH.
- Integracao contabilistica real do payroll com journals.

Conclusao: o estado actual e um MVP operacional de RH/payroll, nao uma implementacao completa dos RF001-RF103.

## 2. Referencias legais usadas

- Lei do Trabalho n.o 13/2023, MTGAS: https://www.mtgas.gov.mz/sites/default/files/public/Lei%20Nr%2013-2023%2C%20de%2025%20de%20Agosto%20-%20Lei%20do%20Trabalho.pdf
- Decreto n.o 88/2024, contratacao de cidadaos estrangeiros, MTGAS: https://www.mtgas.gov.mz/sites/default/files/public/88-2024%2C%20DE%2017%20DE%20DEZEMBRO.pdf
- INSS taxa contributiva, 4% empregador + 3% trabalhador: https://www.inss.gov.mz/taxa-contributiva-contribuinte/
- Autoridade Tributaria, FAQ IRPS: https://www.at.gov.mz/por/Perguntas-Frequentes2/IRPS
- AT e-Declaracao, Guia de Pagamento IRPS Modelo 19: https://edeclaracao.at.gov.mz/formularios/irps/view/formulario_irps.aspx

Notas legais importantes:
- A lista RF recebida contem pontos que devem ser corrigidos antes de implementar: por exemplo, a Lei n.o 13/2023 indica para horas extraordinarias ate 8h/semana, 96h/trimestre e 200h/ano; a lista enviada menciona 16h/semana.
- Para ferias, a Lei n.o 13/2023 indica 12 dias no primeiro ano e 30 dias nos anos subsequentes; a lista enviada usa a regra anterior/proposta de 1 dia por mes no primeiro ano, 2 dias por mes no segundo e 30 dias a partir do terceiro.
- Para denuncia pelo trabalhador em contrato por tempo indeterminado, a Lei n.o 13/2023 indica 15 dias se superior a 6 meses e ate 3 anos, e 30 dias se superior a 3 anos; a lista enviada indica 30/60 dias.

## 3. Evidencia de codigo

Principais areas existentes:
- `packages/workdo/Hrm`: empregados, assiduidade, licencas, payroll, turnos, documentos, advertencias, reclamacoes, cessacao, configuracao de INSS/IRPS/salario minimo.
- `packages/workdo/Recruitment`: vagas, candidatos, entrevistas, avaliacoes, ofertas e onboarding.
- `packages/workdo/Contract`: contratos, tipos, anexos, comentarios, notas, renovacoes e assinaturas.
- `packages/workdo/Performance`: indicadores, objectivos, ciclos e avaliacoes.
- `packages/workdo/Training`: formacoes, formadores, tarefas e feedback.
- `app/Services/MozambiquePayrollTaxService.php`: calculo IRPS/INSS e validacao de salario minimo.
- `app/Services/MozambiqueLabourComplianceService.php`: regras configuraveis de overtime e licencas.
- `app/Providers/AuditTrailServiceProvider.php`: auditoria transversal ainda limitada; inclui `Payroll`, mas nao cobre empregados, contratos, faltas, ferias, disciplina e offboarding.

Problemas tecnicos observados:
- `employees` usa coluna `shift`, mas o model declara `shift_id` como fillable e a relacao chama-se `shift`; isto cria risco de atribuicao/relacao inconsistente.
- `AttendanceController` valida dia util/licenca/feriado usando `Carbon::today()` em vez da data submetida no formulario manual.
- `AttendanceController` atribui `shift_id` com valor que pode ser objecto `Shift` em vez de id, dependendo de como a relacao for resolvida.
- Payroll calcula IRPS sobre `gross_pay`; deve ser revisto contra regra aplicavel, pois a FAQ da AT indica que a retencao usa a informacao do Modelo 11 e tabelas de retencao da primeira categoria, considerando situacao pessoal/familiar.
- O service de IRPS nao considera dependentes, residencia fiscal, Modelo 11, nao residentes ou beneficios tributaveis de forma estruturada.
- Payroll, payslip e payroll entry podem ser apagados; RF088 pede anulacao controlada e historico critico inalteravel.

## 4. Cobertura por macro-modulo

| Area | Estado | Comentario |
| --- | --- | --- |
| Configuracao da empresa | Parcial | Existe perfil fiscal e settings, mas faltam campos laborais, DPT, acordos colectivos, quotas e politicas obrigatorias. |
| Recrutamento | Parcial | Fluxo robusto genericamente, mas faltam bloqueios legais de idade, habilitacao profissional e anti-discriminacao. |
| Contratos | Parcial | Existe CRUD generico, anexos e assinatura. Falta localizacao laboral mocambicana. |
| Periodo probatorio | Em falta | Nao ha modelagem por categoria, alertas ou avaliacao probatoria formal. |
| Cadastro de trabalhadores | Parcial | Falta dependentes IRPS, INSS formal, dados de estrangeiro, documentos com validade e controlo de sensibilidade. |
| Assiduidade | Parcial | Existe ponto e calculo de horas, mas faltam classificacoes legais completas, biometria e triggers disciplinares. |
| Horas extra e horarios | Parcial | Existe overtime e politica configuravel, mas faltam limite semanal/trimestral legal, aprovacao formal e trabalho nocturno remunerado. |
| Ferias | Parcial | Existe leave generico. Falta motor legal de ferias por antiguidade e plano anual. |
| Licencas legais | Parcial | Pode ser configurado como tipos de licenca, mas faltam regras legais obrigatorias por tipo. |
| Payroll | Parcial | Existe processamento, INSS/IRPS minimo e recibo. Falta banco, Modelo 19 robusto, dependentes, nao residentes e contabilizacao. |
| INSS | Parcial | Taxa 3%/4% implementada; faltam inscricao formal, guias oficiais e contabilizacao automatica. |
| IRPS | Parcial | Tabelas configuraveis; faltam Modelo 11, dependentes, nao residente, Modelo 19 completo e historico fiscal anual. |
| Estrangeiros | Em falta | Nao ha quota, regimes, vistos, passaportes, autorizacoes, valididades ou comunicacao de cessacao. |
| Performance | Parcial/Boa | Existe estrutura de avaliacoes, indicadores e objectivos; falta ligar ao periodo probatorio e planos formais de melhoria. |
| Formacao | Parcial | Existe gestao de formacoes; faltam obrigacoes legais e alertas de validade. |
| Disciplina | Parcial fraco | Advertencias/reclamacoes existem, mas nao um processo disciplinar legal. |
| Assedio e conduta | Parcial fraco | Ha complaints e acknowledgments, mas falta canal confidencial, workflow restrito e codigo de conduta obrigatorio. |
| Offboarding | Parcial fraco | Termination/resignation existem, mas faltam calculos legais, acerto final e checklist. |
| Seguranca/auditoria | Parcial | Permissoes existem, mas sem field-level privacy e sem auditoria inalteravel de RH. |
| Relatorios | Parcial | Ha listas e mapa payroll CSV; faltam relatorios legais/gerenciais completos. |
| Alertas/compliance | Em falta | Falta motor de alertas e dashboard de risco laboral. |
| Contabilidade | Parcial fraco | Nao foi encontrada geracao automatica de journals de payroll. |
| Administracao | Parcial | Multiempresa e filiais existem; parametrizacao legal ainda incompleta. |

## 5. Matriz RF001-RF103

Legenda: `OK`, `Parcial`, `Falta`, `Ajustar legal`.

| RF | Estado | Gap principal |
| --- | --- | --- |
| RF001 | Parcial | Faltam regime laboral, acordos colectivos e DPT. |
| RF002 | Falta | Sem classificacao/quotas de estrangeiros. |
| RF003 | Parcial | Documentos existem, mas sem obrigatoriedade/politica legal. |
| RF004 | Ajustar legal | Alerta 7+ trabalhadores nao existe e deve ser validado juridicamente. |
| RF005 | Parcial | Candidato nao tem nacionalidade/documento/NUIT estruturado. |
| RF006 | Falta | Sem validacao de idade minima. |
| RF007 | Falta | Sem profissao regulamentada/licenca obrigatoria. |
| RF008 | Parcial | Custom questions existem, mas sem bloqueio anti-discriminacao. |
| RF009 | Parcial | Tipos de contrato genericos, sem taxonomia legal MZ. |
| RF010 | Parcial | Preview/assinatura existem; clausulas legais incompletas. |
| RF011 | Falta | Sem justificacao obrigatoria para contrato a prazo. |
| RF012 | Falta | Sem alerta de presuncao de contrato sem termo. |
| RF013 | OK parcial | Anexos existem; falta validade/classificacao legal. |
| RF014 | Parcial | Historico existe disperso, nao unificado. |
| RF015 | Falta | Sem periodo probatorio por categoria. |
| RF016 | Falta | Sem alertas de fim de probatorio. |
| RF017 | Parcial | Performance existe, mas nao ligada ao probatorio. |
| RF018 | Falta | Sem cessacao especifica no probatorio. |
| RF019 | Parcial | Employee e incompleto para Mocambique. |
| RF020 | Falta | Sem dependentes fiscais. |
| RF021 | Parcial | Dossier digital basico existe. |
| RF022 | Parcial fraco | Sem permissoes por campo sensivel. |
| RF023 | Parcial | Entrada/saida existe; faltam atrasos/remoto/escalas avancadas. |
| RF024 | Falta | Sem biometria/cartao. |
| RF025 | Parcial | Faltas/licencas ainda genericas. |
| RF026 | Parcial | Payroll deduz algumas ausencias; sem impacto legal completo. |
| RF027 | Ajustar legal | Trigger recebido diverge da Lei n.o 13/2023 e nao existe no sistema. |
| RF028 | Parcial | Shifts/working days existem; faltam regimes completos. |
| RF029 | Ajustar legal | Falta limite semanal/trimestral legal; requisito recebido diverge da lei. |
| RF030 | Falta | Sem workflow de aprovacao de overtime. |
| RF031 | Parcial fraco | Night shift existe, mas sem calculo legal do adicional nocturno. |
| RF032 | Falta | Sem controlo de descanso semanal. |
| RF033 | Ajustar legal | Motor de ferias por antiguidade falta; regra recebida diverge da lei vigente. |
| RF034 | Falta | Sem plano anual de ferias. |
| RF035 | Parcial | Pedido/aprovacao existe, mas sem dupla validacao RH robusta. |
| RF036 | Falta | Sem venda/substituicao parcial de ferias. |
| RF037 | Falta | Faltas injustificadas nao ajustam ferias. |
| RF038 | Parcial fraco | Leave type pode simular maternidade, mas sem regra legal. |
| RF039 | Parcial fraco | Leave type pode simular paternidade, mas sem regra legal. |
| RF040 | Falta | Sem adopcao/familia de acolhimento. |
| RF041 | Parcial | Licenca com anexo existe; falta regra medica/impacto completo. |
| RF042 | Parcial | Tipos configuraveis existem, sem catalogo legal. |
| RF043 | Parcial | Componentes salariais existem, sem tributabilidade estruturada. |
| RF044 | Parcial | Payroll existe, mas IRPS/INSS ainda incompletos. |
| RF045 | Parcial | Folha existe, faltam mapas por todos os eixos legais. |
| RF046 | Parcial | Recibo existe; falta assinatura/confirmacao digital robusta. |
| RF047 | Falta | Sem ficheiro bancario. |
| RF048 | Falta | Sem inscricao INSS estruturada no trabalhador. |
| RF049 | OK parcial | INSS 3%/4% implementado e configuravel. |
| RF050 | Parcial | Existe CSV, mas nao guia oficial INSS. |
| RF051 | Falta | Sem contabilizacao automatica do INSS. |
| RF052 | Parcial | IRPS por escaloes, sem dependentes/Modelo 11/residencia. |
| RF053 | Parcial | Tabelas existem, sem dependentes/residente/nao residente. |
| RF054 | Falta | Sem regra para nao residentes. |
| RF055 | Parcial fraco | Sem geracao completa do Modelo 19. |
| RF056 | Parcial | Historico mensal existe; falta historico fiscal anual formal. |
| RF057 | Falta | Sem cadastro especifico de estrangeiro. |
| RF058 | Falta | Sem regimes de estrangeiros. |
| RF059 | Falta | Sem quota automatica. |
| RF060 | Falta | Sem alertas de documentos de estrangeiro. |
| RF061 | Falta | Sem workflow de cessacao de estrangeiro em 5 dias. |
| RF062 | OK parcial | Avaliacoes/ciclos existem. |
| RF063 | OK parcial | Indicadores/criterios existem. |
| RF064 | Parcial | Objectivos existem; plano de melhoria formal ainda fraco. |
| RF065 | Parcial | Training existe; sem plano anual por obrigacao legal. |
| RF066 | Parcial | Formacoes existem; validade/certificado incompletos. |
| RF067 | Falta | Sem alertas de formacao obrigatoria. |
| RF068 | Parcial | Warnings/complaints cobrem ocorrencias genericas. |
| RF069 | Falta | Sem processo disciplinar formal. |
| RF070 | Falta | Sem nota de culpa. |
| RF071 | Falta | Sem duas testemunhas em recusa. |
| RF072 | Falta | Sem prazos disciplinares legais. |
| RF073 | Parcial fraco | Sem decisoes disciplinares legais completas. |
| RF074 | Parcial fraco | Historico sem protecao forte. |
| RF075 | Parcial fraco | Complaints existem; sem canal confidencial/restrito. |
| RF076 | Falta | Sem workflow automatico de assedio. |
| RF077 | Parcial | Acknowledgments existem; sem obrigatoriedade por codigo de conduta. |
| RF078 | Parcial | Termination/resignation existem; motivos legais incompletos. |
| RF079 | Ajustar legal | Calculo nao existe; regra recebida deve ser ajustada a Lei n.o 13/2023. |
| RF080 | Falta | Sem motor de indemnizacoes. |
| RF081 | Ajustar legal | Regra de 45 dias deve ser condicionada ao caso legal aplicavel. |
| RF082 | Falta | Sem acerto final automatico. |
| RF083 | Falta | Sem checklist de offboarding. |
| RF084 | Parcial | Roles/permissoes existem; matriz RH especializada incompleta. |
| RF085 | Parcial | Permissoes por modulo/operacao; faltam filial/departamento/campo. |
| RF086 | Falta | Sem protecao field-level de dados sensiveis. |
| RF087 | Parcial fraco | Audit trail cobre Payroll, nao todos os actos criticos RH. |
| RF088 | Falta | Sem anulacao controlada/historico inalteravel. |
| RF089 | Parcial | Dados para quadro existem; falta relatorio legal completo. |
| RF090 | Parcial | Payroll CSV existe; mapas legais incompletos. |
| RF091 | Parcial fraco | Leave balance existe; ferias vencidas/vendidas/plano faltam. |
| RF092 | Parcial | Attendance existe; faltam anomalias/nocturno/descanso semanal. |
| RF093 | Parcial fraco | Relatorio disciplinar formal falta. |
| RF094 | Falta | Sem relatorio de expatriados. |
| RF095 | Falta | Sem motor central de alertas legais. |
| RF096 | Falta | Sem dashboard de compliance laboral. |
| RF097 | Falta | Sem journals automaticos de payroll. |
| RF098 | Parcial | Departamento/filial existem; centros de custo/projectos no payroll incompletos. |
| RF099 | Parcial fraco | CSV existe; falta export contabilistico por diario/API/XML. |
| RF100 | Parcial | Multiempresa por `created_by`; requer hardening continuo. |
| RF101 | OK parcial | Filiais/branches existem. |
| RF102 | Parcial | Parametrizacao existe para parte fiscal/laboral; faltam quotas, probatorio, prazos e estrangeiros. |
| RF103 | Parcial fraco | Import/export HR abrangente ainda falta. |

## 6. Prioridades de implementacao

### Fase A - Correcao legal e dados mestres de RH

Implementar:
- `hr_legal_settings`: ferias, probatorio, overtime semanal/trimestral/anual, prazos disciplinares, quotas, licencas legais.
- `employee_dependents`: dependentes para IRPS/Modelo 11.
- Campos/entidades de INSS em trabalhador: numero, data inscricao, estado, documentos.
- Campos de estrangeiro: nacionalidade, residencia fiscal, passaporte, visto, autorizacao, regime, validade, provincia.
- Classificacao de empregador e quota de estrangeiros.
- Ajustar `employees.shift` vs `shift_id`.
- Corrigir validacao de assiduidade para usar a data submetida.

### Fase B - Contratos, probatorio e estrangeiros

Implementar:
- Tipos legais de contrato e mapeamento para regras.
- Justificacao obrigatoria de contratos a prazo.
- Periodo probatorio por categoria, alertas e avaliacao.
- Trabalhador estrangeiro com regimes do Decreto n.o 88/2024.
- Workflow de validade documental e comunicacao de cessacao em 5 dias.

### Fase C - Ferias, licencas, assiduidade e overtime

Implementar:
- Motor de ferias por antiguidade conforme lei vigente parametrizada.
- Plano anual de ferias.
- Licencas legais com regras proprias: maternidade, paternidade, adopcao, doenca, luto, casamento, acidente.
- Classificacao legal de faltas e gatilhos disciplinares.
- Overtime com fluxo de aprovacao e limites legais correctos.
- Trabalho nocturno e descanso semanal.

### Fase D - Payroll legal completo

Implementar:
- IRPS com Modelo 11/dependentes/residencia/nao residente.
- Guia Modelo 19 e historico fiscal anual.
- Guia/mapa INSS e dados de inscricao.
- Ficheiro bancario de pagamento.
- Integracao payroll-contabilidade: salarios, INSS, IRPS, indemnizacoes, provisoes.

### Fase E - Disciplina, assedio, offboarding e compliance

Implementar:
- Processo disciplinar formal com nota de culpa, defesa, testemunhas, prazos e decisao.
- Canal confidencial de denuncias.
- Codigo de conduta obrigatorio e tracking por trabalhador.
- Offboarding com pre-aviso, indemnizacao, acerto final e checklist.
- Motor de alertas e dashboard de compliance laboral.
- Auditoria imutavel para actos criticos de RH.

## 7. Proposta tecnica de garantia de implementacao

Para garantir que a implementacao fica completa e testavel:

1. Criar migrations dedicadas para `hr_legal_settings`, `employee_dependents`, `employee_social_security_profiles`, `employee_foreign_worker_profiles`, `employee_probation_periods`, `disciplinary_cases`, `offboarding_cases`, `hr_compliance_alerts`.
2. Criar services de dominio, nao apenas regras em controllers: `MozambiqueLeaveEntitlementService`, `MozambiqueOvertimeService`, `MozambiqueIrpsWithholdingService`, `MozambiqueForeignWorkerQuotaService`, `MozambiqueDisciplinaryProcedureService`, `MozambiqueTerminationSettlementService`.
3. Criar testes Feature/Unit por RF critico, sobretudo RF011, RF015-RF018, RF027-RF033, RF038-RF061, RF068-RF083, RF095-RF099.
4. Semear parametros legais iniciais, mas permitir alteracao sem deploy.
5. Validar as regras por consultor laboral/fiscal local antes de activar bloqueios em producao.

## 8. Recomendacao final

Nao tratar este trabalho como pequenos CRUDs. O modulo RH precisa de uma camada de regras legais mocambicanas com services, tabelas parametrizaveis, testes e alertas. A implementacao deve comecar pelas entidades legais de trabalhador/contrato/estrangeiro, porque payroll, ferias, disciplina e cessacao dependem desses dados.
