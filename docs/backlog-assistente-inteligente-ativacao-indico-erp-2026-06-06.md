# Backlog - Assistente Inteligente de Ativação do Índico ERP

Data: 2026-06-06

## Objetivo

Criar um assistente inteligente de ativação para novas empresas no Índico ERP, capaz de orientar configuração inicial, validar prontidão operacional, respeitar planos de subscrição, permissões RBAC, limites contratados e sugerir upgrades quando necessário.

Este assistente não deve ser apenas um wizard visual. Deve funcionar como uma camada central de decisão para plano, funcionalidades, limites, progresso, readiness score e bloqueios contextuais.

## Diagnóstico do Sistema Atual

O sistema já tem uma base relevante para esta feature:

| Área | Estado atual |
| --- | --- |
| Planos | A tabela `plans` guarda módulos ativos em JSON, número de utilizadores, storage, trial, preços e estado. |
| Subscrição | O utilizador empresa tem `active_plan`, `plan_expire_date`, `trial_expire_date`, `total_user` e `storage_limit`. |
| Módulos | O acesso é controlado por `PlanModuleCheck` e por módulos ativos do plano ou módulos adicionais em `user_active_modules`. |
| Permissões | O sistema usa Spatie RBAC com roles e permissões. |
| Menus | O frontend já filtra menus por permissões, roles e módulos ativos. |
| Auditoria | Já existe `AuditTrailService`, `AuditTrail` e observer para alterações críticas. |
| Onboarding existente | Há onboarding de recrutamento, mas é específico de RH e não deve ser usado como onboarding da empresa. |

Lacuna principal:

O sistema ainda não tem uma camada fina para decidir se uma funcionalidade está `active`, `locked`, `hidden` ou `addon`. Hoje a decisão está concentrada em módulo ativo e permissões, não em feature keys e limites granulares.

## Princípios de Implementação

1. A regra de plano não deve ficar espalhada em controllers, views ou menus.
2. O backend deve ser a fonte final de verdade para bloqueios e limites.
3. O frontend deve apenas renderizar o estado calculado pelo backend.
4. O onboarding deve ser configurável por step registry, não hardcoded em páginas isoladas.
5. A feature deve respeitar módulos, permissões, subscrição ativa, limites, addons e configuração incompleta.
6. Auditoria e métricas devem reutilizar a infraestrutura existente sempre que possível.
7. A implementação deve começar read-only, depois bloquear ações sensíveis.

## Estados de Funcionalidade

| Estado | Significado |
| --- | --- |
| `active` | A empresa pode usar a funcionalidade. |
| `locked` | A funcionalidade existe, mas está bloqueada por plano, permissão, subscrição expirada ou configuração incompleta. |
| `hidden` | A funcionalidade não deve aparecer para o utilizador neste contexto. |
| `addon` | A funcionalidade depende de compra/ativação adicional. |

## Roadmap Recomendado

| Fase | Resultado esperado |
| --- | --- |
| Fase 0 | Levantamento final de planos, módulos, permissões e limites reais do produto. |
| Fase 1 | Motor técnico de plano, funcionalidades e limites. |
| Fase 2 | Modelo de onboarding, steps, progresso e readiness score. |
| Fase 3 | UI do assistente e integração com menus/dashboard. |
| Fase 4 | Integração real com módulos críticos: faturação, contabilidade, RH e tesouraria. |
| Fase 5 | Bloqueios contextuais, sugestões de upgrade e página "Meu Plano". |
| Fase 6 | Modo consultor/admin, auditoria, métricas, testes e refinamento UX. |

## Backlog Detalhado

### Fase 0 - Descoberta e Contrato Funcional

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-001 | P0 | Mapear planos reais do Índico ERP para famílias comerciais: Starter, Professional, Enterprise/Elite Pro. | Existe uma matriz aprovada com plano, módulos, features e limites. |
| AIA-002 | P0 | Levantar módulos reais e chaves técnicas existentes. | Cada módulo tem `module_key`, nome comercial, rotas principais e menus afetados. |
| AIA-003 | P0 | Levantar permissões RBAC por área funcional. | Cada feature crítica tem permissões mínimas associadas. |
| AIA-004 | P0 | Definir limites por plano. | Existe lista de limites como utilizadores, storage, documentos, empresas, armazéns, POS, colaboradores ou outros aplicáveis. |
| AIA-005 | P0 | Definir steps obrigatórios por módulo. | Cada módulo prioritário tem checklist mínima para operação. |
| AIA-006 | P0 | Definir regra de readiness score. | Fórmula aprovada com peso por módulo e por configuração crítica. |

### Fase 1 - Motor de Plano, Features e Limites

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-101 | P0 | Criar `PlanFeatureResolver`. | Serviço retorna `active`, `locked`, `hidden` ou `addon` para uma feature e empresa. |
| AIA-102 | P0 | Criar `PlanLimitResolver`. | Serviço retorna limite contratado, consumo atual e estado `within_limit`, `near_limit` ou `exceeded`. |
| AIA-103 | P0 | Criar `TenantUsageService`. | Serviço calcula uso por empresa sem depender de controller. |
| AIA-104 | P0 | Criar matriz de features inicial. | Features são configuradas por DB/seeder ou config versionada, sem hardcode em menus. |
| AIA-105 | P0 | Integrar módulos existentes com feature keys. | Módulos do plano continuam a funcionar, mas passam a alimentar o resolver. |
| AIA-106 | P0 | Criar `UpgradeSuggestionService`. | Serviço devolve motivo do bloqueio e plano/addon recomendado. |
| AIA-107 | P1 | Criar DTO/array padrão para estado de feature. | Frontend recebe formato consistente para menus, dashboard e onboarding. |
| AIA-108 | P1 | Adicionar cache segura aos resolvers. | Resultados por empresa/plano são cacheados e invalidados em alteração de plano, addon ou permissão. |

### Fase 2 - Modelo de Onboarding

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-201 | P0 | Criar tabela `onboarding_sessions`. | Cada empresa tem sessão ativa, estado e data de conclusão. |
| AIA-202 | P0 | Criar tabela `onboarding_steps`. | Steps têm chave, módulo, ordem, obrigatoriedade, estado e metadados. |
| AIA-203 | P0 | Criar tabela `onboarding_checklist_items`. | Cada step pode ter itens verificáveis. |
| AIA-204 | P0 | Criar `OnboardingStepRegistry`. | Steps disponíveis são registados centralmente por módulo e plano. |
| AIA-205 | P0 | Criar `OnboardingProgressService`. | Serviço calcula progresso global e por módulo. |
| AIA-206 | P0 | Criar `OnboardingReadinessService`. | Serviço calcula readiness score e bloqueios críticos. |
| AIA-207 | P1 | Criar `OnboardingCompletionService`. | Serviço valida se empresa pode concluir onboarding. |
| AIA-208 | P1 | Permitir steps ignoráveis com motivo. | Steps não críticos podem ser ignorados e auditados. |

### Fase 3 - Interface do Assistente

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-301 | P0 | Criar rota e página `/onboarding`. | Empresa vê assistente com progresso, steps e próximos passos. |
| AIA-302 | P0 | Criar UI de step com estado, ação e bloqueio. | Cada step mostra se está completo, pendente, bloqueado ou depende de upgrade. |
| AIA-303 | P0 | Criar resumo de readiness no dashboard. | Dashboard mostra score e principais pendências. |
| AIA-304 | P1 | Mostrar bloqueios nos menus sem quebrar navegação. | Menus podem exibir item bloqueado com tooltip/CTA quando aplicável. |
| AIA-305 | P1 | Criar CTAs contextuais. | Utilizador recebe ações como configurar perfil fiscal, criar série, ativar módulo, comprar addon. |
| AIA-306 | P1 | Criar estado vazio profissional para nova empresa. | Empresas novas são guiadas sem ficarem perdidas em menus vazios. |

### Fase 4 - Integração com Módulos Prioritários

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-401 | P0 | Integrar Faturação. | Readiness valida cliente, produto, imposto, série documental, perfil fiscal, período contabilístico e emissão de teste. |
| AIA-402 | P0 | Integrar Contabilidade. | Readiness valida plano de contas, período contabilístico, impostos, lançamentos automáticos e relatórios críticos. |
| AIA-403 | P0 | Integrar Tesouraria. | Readiness valida contas bancárias/caixa, recebimentos, pagamentos e conciliação mínima. |
| AIA-404 | P0 | Integrar Recursos Humanos. | Readiness valida colaboradores, contratos, folha, contribuições e permissões RH. |
| AIA-405 | P1 | Integrar Inventário/POS. | Readiness valida armazém, stock inicial, FIFO/layers e POS se o plano permitir. |
| AIA-406 | P1 | Integrar permissões por função. | Assistente indica quando o utilizador não tem permissão para concluir um step. |
| AIA-407 | P1 | Integrar configuração fiscal Moçambique. | O assistente valida NUÍT, IVA, séries, documentos fiscais e requisitos SCE relevantes. |

### Fase 5 - Bloqueios, Upgrades e Meu Plano

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-501 | P0 | Criar middleware `feature:feature_key`. | Rotas sensíveis são bloqueadas por feature e devolvem motivo claro. |
| AIA-502 | P0 | Criar middleware `plan.limit:limit_key`. | Criação de recursos respeita limites do plano. |
| AIA-503 | P0 | Melhorar `PlanModuleCheck` sem quebrar compatibilidade. | Módulos continuam protegidos e passam a usar resolvers quando aplicável. |
| AIA-504 | P1 | Criar página "Meu Plano". | Empresa vê plano atual, módulos, limites, consumo e upgrades sugeridos. |
| AIA-505 | P1 | Criar sugestões de upgrade por contexto. | Bloqueios mostram plano/addon recomendado e razão objetiva. |
| AIA-506 | P1 | Implementar estado de subscrição expirada. | Sistema diferencia plano expirado, feature bloqueada e permissão insuficiente. |
| AIA-507 | P2 | Criar overrides por empresa. | Admin pode liberar feature/limite específico sem mudar plano inteiro. |

### Fase 6 - Administração, Auditoria, Métricas e Testes

| ID | Prioridade | Item | Critério de aceitação |
| --- | --- | --- | --- |
| AIA-601 | P1 | Criar modo consultor/admin. | Superadmin ou consultor autorizado vê progresso das empresas e pendências. |
| AIA-602 | P1 | Reutilizar `AuditTrailService` para eventos críticos. | Alterações de plano, override, skip de step e conclusão são auditáveis. |
| AIA-603 | P1 | Criar métricas de ativação. | Sistema mede tempo até prontidão, steps bloqueados e módulos mais problemáticos. |
| AIA-604 | P0 | Criar testes de resolvers. | Testes cobrem plano ativo, expirado, addon, limite excedido e permissão ausente. |
| AIA-605 | P0 | Criar testes de onboarding. | Testes cobrem sessão, progresso, conclusão, skip e readiness score. |
| AIA-606 | P0 | Criar testes de middleware. | Rotas protegidas bloqueiam corretamente sem depender de frontend. |
| AIA-607 | P1 | Criar testes E2E dos fluxos principais. | Empresa nova consegue ser ativada até faturação/contabilidade básica. |
| AIA-608 | P1 | Documentar manual de administração da feature. | Equipa interna sabe configurar planos, steps, limites e overrides. |

## Modelo de Dados Recomendado

| Tabela | Finalidade |
| --- | --- |
| `plan_features` | Define feature keys por plano e o estado base. |
| `plan_limits` | Define limites quantitativos por plano. |
| `tenant_usage` | Guarda ou cacheia consumo por empresa quando cálculo direto for caro. |
| `tenant_feature_overrides` | Permite exceções por empresa aprovadas por admin. |
| `addon_subscriptions` | Regista addons ativos por empresa quando não forem apenas módulos em `user_active_modules`. |
| `onboarding_sessions` | Sessão principal de onboarding por empresa. |
| `onboarding_steps` | Estado dos steps por empresa. |
| `onboarding_checklist_items` | Itens individuais de cada step. |
| `onboarding_audit_logs` | Opcional. Só criar se `AuditTrailService` não cobrir todos os eventos. |

## Serviços Recomendados

| Serviço | Responsabilidade |
| --- | --- |
| `PlanFeatureResolver` | Resolver acesso e estado de features. |
| `PlanLimitResolver` | Resolver limites e consumo. |
| `TenantUsageService` | Calcular uso real por empresa. |
| `UpgradeSuggestionService` | Sugerir upgrade/addon com base no bloqueio. |
| `OnboardingStepRegistry` | Registar steps disponíveis por módulo/plano. |
| `OnboardingProgressService` | Calcular progresso por empresa. |
| `OnboardingReadinessService` | Calcular prontidão operacional. |
| `OnboardingCompletionService` | Validar conclusão. |
| `OnboardingActionService` | Executar ações automáticas seguras, como criar dados mínimos sugeridos. |

## Rotas Recomendadas

| Método | Rota | Uso |
| --- | --- | --- |
| `GET` | `/onboarding` | Página principal do assistente. |
| `POST` | `/onboarding/start` | Iniciar sessão de onboarding. |
| `GET` | `/onboarding/steps` | Listar steps e estados. |
| `GET` | `/onboarding/steps/{step}` | Ver detalhe do step. |
| `POST` | `/onboarding/steps/{step}/complete` | Marcar step como concluído após validação. |
| `POST` | `/onboarding/steps/{step}/skip` | Ignorar step com motivo. |
| `GET` | `/onboarding/progress` | Obter progresso. |
| `GET` | `/onboarding/readiness-score` | Obter readiness score. |
| `POST` | `/onboarding/complete` | Concluir onboarding. |
| `POST` | `/onboarding/reset` | Reiniciar onboarding, apenas admin/consultor. |
| `GET` | `/billing/my-plan` | Plano, limites e consumo. |
| `GET` | `/billing/upgrade-suggestions` | Sugestões contextuais. |

## Middleware Recomendado

| Middleware | Objetivo |
| --- | --- |
| `feature:feature_key` | Bloquear ação quando a feature não está ativa. |
| `plan.limit:limit_key` | Bloquear criação quando limite foi excedido. |
| `subscription.active` | Garantir subscrição ativa. |
| `onboarding.required` | Redirecionar empresa nova para onboarding quando necessário. |

## Steps Iniciais por Módulo Prioritário

### Faturação

| Step | Obrigatório | Validação |
| --- | --- | --- |
| Configurar perfil fiscal da empresa | Sim | NUÍT, regime de IVA, endereço fiscal e dados legais preenchidos. |
| Criar período contabilístico ativo | Sim | Data atual pertence a período aberto. |
| Criar série documental | Sim | Série ativa para fatura, nota de crédito e documentos aplicáveis. |
| Criar imposto IVA | Sim | Taxa fiscal ativa e associável a produtos. |
| Criar produto/serviço | Sim | Produto vendável com imposto e preço. |
| Criar cliente | Sim | Cliente com dados mínimos. |
| Emitir fatura de teste | Sim | Documento em draft/final conforme política definida. |

### Contabilidade

| Step | Obrigatório | Validação |
| --- | --- | --- |
| Confirmar plano de contas | Sim | Plano de contas existe e está ativo. |
| Confirmar contas fiscais | Sim | Contas de IVA e vendas/compras mapeadas. |
| Confirmar lançamentos automáticos | Sim | Faturas geram movimentos esperados. |
| Validar balancete inicial | Não | Balancete acessível e sem erros críticos. |

### Recursos Humanos

| Step | Obrigatório | Validação |
| --- | --- | --- |
| Configurar empresa para RH | Sim | Dados legais e parâmetros RH mínimos. |
| Criar colaborador | Sim | Colaborador ativo com dados contratuais mínimos. |
| Configurar contrato | Sim | Tipo de contrato e datas válidas. |
| Configurar folha/processamento | Não | Regras salariais mínimas se módulo estiver ativo. |

### Tesouraria

| Step | Obrigatório | Validação |
| --- | --- | --- |
| Criar conta bancária ou caixa | Sim | Conta ativa disponível para recebimentos/pagamentos. |
| Registar recebimento teste | Sim | Recebimento vinculado a documento ou cliente. |
| Registar pagamento teste | Não | Pagamento vinculado a fornecedor ou despesa. |
| Validar saldo/relatório | Não | Relatório acessível sem erro. |

## Riscos Técnicos

| Risco | Mitigação |
| --- | --- |
| Regras duplicadas em frontend e backend | Backend calcula estado; frontend só renderiza. |
| Quebra dos módulos existentes | Manter `PlanModuleCheck` compatível e introduzir resolvers gradualmente. |
| Planos atuais só têm módulos, não features | Criar feature matrix derivada dos módulos existentes e evoluir para granularidade. |
| Limites caros de calcular | Usar `TenantUsageService` com cache ou `tenant_usage` quando necessário. |
| Mistura de `created_by`, `creator_id` e `company_id` | Criar resolver central de empresa/tenant e reutilizar em todos os serviços. |
| Onboarding virar fluxo rígido | Usar step registry configurável e permitir steps opcionais. |
| Bloqueio comercial agressivo | Diferenciar `locked`, `addon`, `hidden` e permissão insuficiente com mensagens claras. |

## Ordem de Implementação Recomendada

1. Implementar `AIA-001` a `AIA-006` para fechar contrato funcional.
2. Implementar `AIA-101` a `AIA-107` sem alterar UX ainda.
3. Implementar testes `AIA-604` antes de aplicar bloqueios em rotas reais.
4. Implementar `AIA-201` a `AIA-207` para onboarding persistente.
5. Implementar UI mínima `AIA-301` a `AIA-303`.
6. Integrar Faturação, Contabilidade, Tesouraria e RH.
7. Só depois ativar middleware de bloqueio contextual em produção.

## MVP Recomendado

O MVP deve incluir:

| Área | Incluído no MVP |
| --- | --- |
| Plano | Resolver de features e limites. |
| Onboarding | Sessão, steps, progresso e readiness score. |
| Módulos | Faturação, Contabilidade, Tesouraria e RH. |
| UI | Página `/onboarding`, resumo no dashboard e estados bloqueados básicos. |
| Segurança | Backend enforcement em rotas críticas selecionadas. |
| Testes | Unitários dos resolvers, onboarding e middleware. |

Fora do MVP:

| Área | Motivo |
| --- | --- |
| Modo consultor completo | Pode entrar depois da operação básica estabilizar. |
| Métricas avançadas | Depende de uso real. |
| Overrides complexos | Devem ser introduzidos após regras comerciais estarem maduras. |
| Automação completa de configuração | Deve começar assistida, não automática, para evitar dados fiscais incorretos.