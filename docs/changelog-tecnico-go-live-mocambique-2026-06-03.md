# Changelog Tecnico do Release Candidate - 2026-06-03

## Identificacao

| Campo | Valor |
|---|---|
| Release tag | `v2026.06.03-01` |
| Commit base | `c36b8da8a` |
| Mensagem do commit | `feat(mocambique): consolidate fiscal, hrm and go-live hardening` |
| Artefacto gerado | `/tmp/hrm-release-v2026.06.03-01.tar.gz` |
| SHA-256 do artefacto | `b60e24e08e6bf49cf60dbf62c7c90643f6297be10ec411219d8a5fd93784b2b3` |

## Objectivo

Registar, de forma auditavel, as alteracoes consolidadas no corte de release para Moambique, cobrindo fiscalidade, faturacao, tesouraria, contabilidade/SCE, RH, seguranca, readiness e operacao.

Este changelog documenta o estado tecnico do release candidate. Nao substitui validacao legal/fiscal/contabilistica externa nem a execucao em staging/producao.

## Principais Alteracoes

### 1. Faturacao, IVA e documentos fiscais

- Parametrizacao da taxa de IVA de importacao em vez de valor hardcoded.
- Reforco da validacao fiscal em facturas e itens.
- Expansao de regras de series fiscais, sequencia e inalterabilidade documental.
- Apoio a historico de exportacao fiscal e rastreabilidade de documentos.
- Melhorias em bloqueio de alteracoes criticas apos emissao.

### 2. Tesouraria, FX, GIFiM e moeda eletronica

- Campos e validacoes para pagamentos em moeda estrangeira.
- Dossier cambial para operacoes internacionais.
- Campos de conformidade GIFiM e moeda eletronica nas contas bancarias/pagamentos.
- Controlos para fecho de caixa e mapas operacionais de tesouraria.

### 3. Contabilidade e SCE

- Comandos de sincronizacao de calendario fiscal, perfis financeiros e alertas fiscais.
- Reforco das bases para journals, relatórios financeiros e fechos.
- Estrutura de suporte a planos de contas e conformidade contabilistica local.

### 4. Recursos Humanos

- Fluxos de assiduidade biometricamente ingerida.
- Exportacao/importacao de payroll e submissao compliance.
- Regras de cessacao, cancelacao auditavel e workflow disciplinar.
- Controlo reforcado de acessos a dados sensiveis.

### 5. Readiness, seguranca e operacao

- Gates de go-live para backup/restore, evidencias e readiness.
- Script operacional de backup/restore e runbook de producao.
- Reforco de auditoria para acoes criticas e permissões.

### 6. Testes automatizados

- Cobertura para IVA especial, importacao, faturacao fiscal, tesouraria, RH, permissões e readiness.
- Suites de feature tests para comandos operacionais e servicos de compliance.

## Evidencias De Validacao

- `git diff --check` sem erros antes do commit.
- Commit de release fechado e tag criado.
- Empacotamento do release candidate a partir da tag.
- Validacao de migrations em MySQL temporario com `migrate:fresh`.
- Verificacao real de backup e restore em base temporaria.
- Testes funcionais e de integracao executados nos blocos criticos.

## Riscos Residuales Antes De Producao

- Falta a validacao legal/fiscal/contabilistica externa formal em ambiente real.
- Falta a execucao de `php artisan migrate --force` no ambiente final de staging/producao.
- Falta o preenchimento completo do readiness com evidencias reais de piloto/cliente.
- Falta o smoke test final em ambiente de producao apos deploy.
- Se este changelog for considerado parte do artefacto final, o release package deve ser regenerado a partir de um novo corte/tag.

## Proximos Passos Recomendados

1. Executar migracao real em staging/producao.
2. Recolher evidencias no painel de readiness.
3. Confirmar validacao externa legal/fiscal/contabilistica.
4. Executar smoke test final e monitorizacao de logs.
5. Se o changelog precisar entrar no artefacto distribuido, refazer corte de release e novo pacote.
