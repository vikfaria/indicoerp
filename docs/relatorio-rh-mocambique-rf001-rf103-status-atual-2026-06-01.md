# Relatorio RH Mocambique - Status Atual RF001-RF103

Data: 01 de Junho de 2026

Escopo: consolidar o estado real dos RF do modulo de RH com base no codigo e testes atuais, como atualizacao do relatorio de 27 de Maio de 2026.

## 1. Nota importante

- O plano/relatorio de RH vai ate `RF103` (nao existe `RF104` neste escopo).
- O relatorio anterior de `2026-05-27` ficou desatualizado em varios pontos apos os lotes implementados.

## 2. Evidencias principais de evolucao

Evidencias de implementacao ja disponiveis:

- Recrutamento legal: [RecruitmentMozambiqueComplianceTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/RecruitmentMozambiqueComplianceTest.php)
- Contratos laborais e justificacao a prazo: [ContractLabourComplianceTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/ContractLabourComplianceTest.php)
- Perfis legais do trabalhador (INSS, dependentes, estrangeiro, probatorio): [HrmEmployeeLegalProfilesTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmEmployeeLegalProfilesTest.php)
- Regras laborais (ferias/licencas/overtime/descanso semanal): [MozambiqueLabourRulesTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/MozambiqueLabourRulesTest.php)
- Biometria de assiduidade: [HrmBiometricAttendanceIngestApiTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmBiometricAttendanceIngestApiTest.php)
- Disciplina/assedio/offboarding: [HrmDisciplinaryHarassmentOffboardingCrudTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmDisciplinaryHarassmentOffboardingCrudTest.php), [HrmHarassmentDisciplinaryWorkflowTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmHarassmentDisciplinaryWorkflowTest.php)
- Submissoes de payroll (Modelo 19/INSS/banco/API): [HrmPayrollSubmissionExportsTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmPayrollSubmissionExportsTest.php), [HrmPayrollSubmissionFormatsAndApiTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmPayrollSubmissionFormatsAndApiTest.php)
- Integracao contabilistica do payroll: [HrmPayrollAccountingJournalTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/HrmPayrollAccountingJournalTest.php)
- Dashboard de compliance legal: [MozambiqueHrComplianceDashboardService.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Services/MozambiqueHrComplianceDashboardService.php), [MozambiqueHrComplianceDashboardServiceTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/MozambiqueHrComplianceDashboardServiceTest.php)
- Auditoria/cancelamento controlado RH: [AuditTrailServiceProvider.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Providers/AuditTrailServiceProvider.php), [AuditTrailTest.php](/Users/victorfaria/Antigravity%20Local%20Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/AuditTrailTest.php)

## 3. RF ainda pendentes para fecho 100%

Legenda desta secao:

- `Pendente`: falta implementacao funcional relevante.
- `Parcial`: existe implementacao operacional, mas falta acabamento legal/funcional.
- `Ajustar legal`: implementar/alinhar regra final da lei e parametrizacao default.

| RF | Estado atual | Gap para fechar |
| --- | --- | --- |
| RF003 | Parcial | Politicas internas ainda estao mais documentais; falta governanca/versionamento mais formal por politica. |
| RF010 | Parcial | Geracao contratual ainda sem cobertura total de clausulas legais parametrizadas por regime/caso. |
| RF013 | Parcial | Anexacao existe; falta classificacao/validade legal mais forte por tipo documental. |
| RF014 | Parcial | Historico contratual ainda disperso; falta timeline unica e consolidada. |
| RF018 | Pendente | Cessacao especifica no periodo probatorio ainda sem fluxo dedicado de ponta a ponta. |
| RF021 | Parcial | Dossier digital existe, mas ainda sem consolidacao completa orientada a compliance documental. |
| RF023 | Parcial | Assiduidade cobre base legal; faltam alguns cenarios operacionais avancados (ex.: remoto/escalas complexas). |
| RF028 | Parcial | Regimes de horario estao operacionais, mas ainda sem cobertura total de todos os cenarios organizacionais. |
| RF029 | Ajustar legal | Defaults e parametrizacao final de overtime devem ser alinhados integralmente com a Lei n.o 13/2023. |
| RF031 | Parcial | Trabalho nocturno e riscos existem; falta consolidar adicional remuneratorio de forma completa no fluxo salarial. |
| RF043 | Parcial | Componentes salariais existem; falta fechar matriz tributavel/nao tributavel em todos os componentes. |
| RF044 | Parcial | Payroll robusto, mas ainda faltam ajustes finais de cobertura legal em alguns cenarios. |
| RF045 | Parcial | Mapas existem, mas falta fechamento de todos os eixos legais/gerenciais esperados no escopo. |
| RF046 | Parcial | Recibo existe; falta robustez final de confirmacao/assinatura digital conforme processo interno definido. |
| RF064 | Parcial | Avaliacao existe; plano formal de melhoria ainda precisa maior estruturacao e rastreabilidade. |
| RF065 | Parcial | Formacao existe; plano anual orientado por obrigacao legal/departamento ainda precisa fechar. |
| RF066 | Parcial | Historico de formacoes existe; falta acabamento de alguns controles de validade/certificacao. |
| RF078 | Parcial | Tipos de cessacao existem; taxonomia e regras finais por motivo legal ainda requerem uniformizacao. |
| RF079 | Ajustar legal | Pre-aviso ja calculado, mas regra final de negocio deve ser fechada juridicamente por tipo contratual/caso. |
| RF084 | Parcial | Perfis existem; falta fechar matriz RH especializada de forma mais granular por papel. |
| RF085 | Parcial | Permissoes existem; falta granularidade total por filial/departamento/nivel hierarquico em todos os fluxos. |
| RF089 | Parcial | Relatorios de quadro estao proximos, mas falta fechamento legal completo de apresentacao e filtros finais. |
| RF090 | Parcial | Relatorios de payroll estao fortes; falta padronizacao final de todos os mapas legais exigidos. |
| RF091 | Parcial | Compliance de ferias evoluiu; falta acabamento final de alguns indicadores/visoes de relatorio. |
| RF092 | Parcial | Relatorios de assiduidade existem; falta consolidacao final de algumas anomalias/visoes operacionais. |
| RF100 | Parcial | Multiempresa funciona; hardening continuo de isolamento e governanca ainda necessario em alguns fluxos RH. |
| RF102 | Parcial | Parametrizacao legal avancou; falta consolidar totalmente alguns parametros de negocio para fechamento final. |
| RF103 | Parcial | Import/export evoluiu bastante; falta cobertura completa e uniforme para todos os pacotes RH previstos. |

## 4. Conclusao objetiva

- Nao terminamos 100% dos RF001-RF103.
- O gap atual ja nao e de base estrutural; o que falta e principalmente fechamento legal, uniformizacao e acabamento funcional em pontos especificos.
- Os pendentes prioritarios para fechar o modulo sao os desta lista da secao 3.

