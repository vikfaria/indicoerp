# Classificacao de worktree para release - 2026-06-03

## Estado da auditoria

- `git diff --check` passou sem erros de formatação.
- O worktree atual esta concentrado em quatro blocos principais: fiscal/faturacao/tesouraria, RH, migrations/schema e readiness/operacao.
- Artefactos gerados estao a ser mantidos fora do release por `gitignore`, incluindo `.DS_Store`, `storage/app/private` e `storage/app/saft`.

## Contagem por tema

| Tema | Contagem aproximada de ficheiros |
| --- | --- |
| `packages/` | 120 |
| `tests/` | 41 |
| `app/` | 41 |
| `database/` | 17 |
| `docs/` | 8 |
| `resources/` | 6 |
| `deploy/` | 2 |
| `routes/` | 1 |
| `config/` | 1 |
| `other` | 1 |

## Blocos de release

### 1. Faturacao, tesouraria, contabilidade e SCE

Inclui alteracoes em:

- `packages/workdo/Account/src/Services/ReportService.php`
- `packages/workdo/Account/src/Http/Controllers/*`
- `packages/workdo/Account/src/Models/*`
- `app/Services/*` relacionadas com fiscalidade, SAF-T, retencoes e auditoria
- `app/Models/*` relacionadas com documentos fiscais, IVA, importacao e retencoes
- `resources/js/pages/Fiscal/*`
- `resources/js/pages/Tax/*`
- testes de fiscalidade, tesouraria, pagamentos, SAF-T e compliance

Objetivo do bloco:

- fechar o caminho venda/compra/pagamento/journal/IVA/SAF-T;
- garantir isolamento por tenant e perfis de acesso;
- manter rastreabilidade fiscal e contabilistica.

### 2. Recursos Humanos

Inclui alteracoes em:

- `packages/workdo/Hrm/src/Http/Controllers/*`
- `packages/workdo/Hrm/src/Models/*`
- `packages/workdo/Hrm/src/Resources/js/Pages/*`
- `packages/workdo/Hrm/src/Routes/*`
- `app/Services/MozambiqueHr*`
- testes de contratos, ferias, assiduidade, payroll, disciplina, offboarding e compliance

Objetivo do bloco:

- cumprir regras laborais, payroll, INSS, IRPS, quotas estrangeiras, ferias e cessacao;
- bloquear acessos indevidos a dados sensiveis de RH;
- garantir exportacao contabilistica do payroll.

### 3. Migrations e schema

Inclui alteracoes em:

- `database/migrations/*`
- `packages/workdo/Account/src/Database/Migrations/*`
- `packages/workdo/Hrm/src/Database/Migrations/*`

Objetivo do bloco:

- preparar schema para staging/produçao;
- validar campos novos sem quebrar dados existentes;
- suportar os fluxos fiscais, financeiros e laborais novos.

### 4. Readiness, operacao e release

Inclui alteracoes em:

- `deploy/scripts/*`
- `deploy/PRODUCTION_RUNBOOK_PT.md`
- `packages/workdo/Account/src/Resources/js/Pages/Reports/MozambiqueGoLiveReadiness.tsx`
- `packages/workdo/Account/src/Services/ReportService.php`
- `packages/workdo/Account/src/Http/Controllers/ReportsController.php`
- `tests/Feature/MozambiqueGoLiveReadinessTest.php`

Objetivo do bloco:

- formalizar atestações de go-live;
- exigir backup/restore e gates operacionais;
- dar evidencia auditavel do estado de prontidao.

### 5. Documentacao e evidencias

Inclui alteracoes em:

- `docs/backlog-tecnica-go-live-mocambique-2026-06-03.md`
- `docs/relatorio-tecnico-execucao-go-live-mocambique-2026-06-03.md`
- outros relatorios de apoio e roadmap

Objetivo do bloco:

- tornar a release auditavel;
- preservar decisao tecnica e criterios de go/no-go;
- servir de input para QA, comercial e validacao legal/fiscal.

## Exclusoes do release

Mantem fora do release:

- artefactos ignorados por `.gitignore`;
- ficheiros temporarios fora do repositório;
- dumps, backups e manifests gerados apenas para teste local;
- qualquer ficheiro que nao seja necessario para a release candidate.

## Recomendacao de separacao de commits

Para reduzir risco operacional, a release deve ser separada em pelo menos estes grupos:

1. `fiscal-accounting` para faturacao, tesouraria, SCE e compliance.
2. `hrm-compliance` para RH, payroll, contratos e assiduidade.
3. `migrations-release` para schema novo.
4. `readiness-ops` para backup/restore, healthcheck e gates de go-live.
5. `docs-evidence` para backlog, relatorios e runbook.

## Nota

Esta classificacao nao faz commit nem deploy. Serve para reduzir risco de release e preparar o worktree para o passo seguinte: `REL-002`.
