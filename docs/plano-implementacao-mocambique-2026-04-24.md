# Plano de Implementacao para o Mercado Mocambicano

Data: 24 de Abril de 2026  
Base de trabalho: [analise-adaptacao-mocambique-2026-04-24.md](analise-adaptacao-mocambique-2026-04-24.md)

## 1. Objectivo

Transformar a solucao actual num produto comercializavel em Mocambique, com prioridade para:

- conformidade fiscal e documental
- conformidade laboral e de payroll
- seguranca multiempresa
- estabilidade tecnica e operacao SaaS
- adaptacao comercial ao contexto local

## 2. Premissas De Esforco

- As estimativas estao em `pessoa-dias` e assumem uma equipa pequena e experiente
- `1 pessoa-dia` equivale a `1 profissional` a trabalhar `1 dia util`
- As estimativas incluem desenvolvimento, testes basicos e revisao tecnica
- Validacao legal/fiscal local e um trabalho paralelo, nao substituivel por dev

## 3. Estrategia Recomendada

Nao recomendo tentar "localizar tudo ao mesmo tempo". O caminho mais seguro e:

1. Fechar riscos estruturais de seguranca e operacao
2. Implementar a camada fiscal minima viavel
3. Localizar payroll e RH
4. Adicionar integracoes locais e polimento comercial
5. Validar com piloto real antes de lancamento

## 4. Roadmap Faseado

| Fase | Foco | Entregaveis principais | Esforco estimado | Dependencias |
|---|---|---|---:|---|
| 0 | Estabilizacao tecnica | build a funcionar, testes basicos, dependencias alinhadas, instalacao isolada | 18-25 PD | nenhum |
| 1 | Seguranca e multiempresa | policies/guards por empresa, validacao de ownership, audit log minimo | 20-30 PD | Fase 0 |
| 2 | Fiscalizacao documental | NUIT, series, tipos de documento, snapshots fiscais, impressao local | 35-55 PD | Fases 0-1 |
| 3 | Accounting/Tax | lancamentos fiscais, mapas, exportacoes, tax accounts, fecho mensal | 15-25 PD | Fase 2 |
| 4 | Payroll e RH | IRPS, INSS, salarios minimos, recibos, ferias, overtime, licencas | 30-50 PD | Fases 0-2 |
| 5 | Integracoes locais | mobile money, importacao bancaria, eDeclaracao/exportacoes operacionais | 15-25 PD | Fases 2-4 |
| 6 | QA, piloto e go-live | testes E2E, piloto, correcao final, checklist legal/comercial | 12-18 PD | Fases 0-5 |

Total estimado:

- **145-228 pessoa-dias** para uma versao robusta e comercializavel
- **90-130 pessoa-dias** para um MVP local muito restrito, mas ainda com risco operacional

## 5. Backlog Por Modulo

### 5.1 Core Platform

Prioridade: `muito alta`

Escopo:

- corrigir o redireccionamento global da instalacao para nao interferir com testes e ambientes ja instalados
- criar um modelo de tenant/empresa mais forte do que simples `created_by`
- aplicar policies/guards consistentes em controllers e requests
- implementar audit trail transversal para accoes criticas
- padronizar logs, erros, eventos e notificacoes

Esforco:

- `12-20 PD`

Critico para lancamento:

- sim

### 5.2 Cadastro Fiscal E Documental

Prioridade: `muito alta`

Escopo:

- adicionar `NUIT` em empresa, cliente e fornecedor
- validar formato e obrigatoriedade por contexto
- adicionar serie documental por empresa, armazem ou estabelecimento
- distinguir factura, recibo, nota de credito, nota de debito, proforma e documento interno
- guardar snapshot historico de dados fiscais no momento da emissao
- incluir estado de submissao/validacao e referencia fiscal local
- criar regras de sequencia numerica por serie e por tipo de documento

Esforco:

- `20-35 PD`

Critico para lancamento:

- sim

### 5.3 Vendas E POS

Prioridade: `muito alta`

Escopo:

- fechar as brechas de ownership em `store`, `post`, `print` e `destroy`
- validar customer, warehouse e items por pertença a empresa
- adaptar o fluxo de POS para documentos fiscais locais
- garantir impressao com dados legais completos
- suportar anulacao, rectificacao e notas de credito/debito
- preservar dados historicos do cliente e da empresa no documento emitido

Esforco:

- `18-30 PD`

Critico para lancamento:

- sim

### 5.4 Accounting E Fiscal

Prioridade: `alta`

Escopo:

- mapear contas contabilisticas para IVA, IRPC, retencoes e impostos retidos
- criar relatorios e exportacoes fiscais utiles para contabilistas moçambicanos
- definir fecho mensal e anual com rastreabilidade
- reforcar a logica de journals para documentos fiscais locais
- suportar cenarios de isencao, nao liquidacao e ajustamentos

Esforco:

- `15-25 PD`

Critico para lancamento:

- sim, para clientes com exigencia contabilistica real

### 5.5 HR E Payroll

Prioridade: `muito alta`

Escopo:

- implementar tabelas IRPS parametrizadas por escalão e vigencia
- implementar INSS trabalhador/empregador
- suportar salarios minimos sectoriais por data de vigencia
- tratar horas extra com regras locais
- formalizar licencas, ausencias e feriados
- adaptar recibo de salario e mapas mensais
- prever contrato de estrangeiro e controlos basicos associados

Esforco:

- `30-50 PD`

Critico para lancamento:

- sim, se o produto for vendido com modulo RH

### 5.6 Integracoes Locais

Prioridade: `media/alta`

Escopo:

- integrar meios de pagamento locais prioritarios
- suportar importacao de extractos bancarios
- exportar dados para contabilistas e declaracoes
- preparar webhooks e reconciliaçao

Esforco:

- `15-25 PD`

Critico para lancamento:

- nao para o MVP, sim para competitividade comercial

### 5.7 QA, Conformidade E Go-Live

Prioridade: `muito alta`

Escopo:

- aumentar cobertura de testes de autorizacao e documentos
- criar cenarios de regressao para instalacao, login, vendas, POS e payroll
- validar build frontend e pipeline de deploy
- fazer revisao legal/fiscal local antes do piloto
- executar piloto com empresas reais e ajustar a configuracao

Esforco:

- `12-18 PD`

Critico para lancamento:

- sim

## 6. Sequencia Recomendada De Implementacao

Ordem pratica:

1. Fase 0
2. Fase 1
3. Fase 2
4. Fase 4
5. Fase 3
6. Fase 5
7. Fase 6

Motivo:

- sem seguranca multiempresa e sem documento fiscal consistente, o resto perde valor comercial
- payroll deve vir antes do lancamento em sectores com RH forte
- integracoes locais aumentam adopcao, mas nao devem bloquear o nucleo regulatorio

## 7. Equipa Minima Recomendada

Para executar este plano com alguma previsibilidade:

- `1 backend senior`
- `1 frontend senior`
- `1 QA/automation` a tempo parcial ou part-time forte
- `1 analista fiscal/legal local` para validacao continua
- `1 product owner` para prioridade e decisao de escopo

Sem este apoio fiscal/legal, o risco de retrabalho continua alto.

## 8. Criticidade Por Risco

### Bloqueadores de Go-Live

- autorizacao e ownership entre empresas
- NUIT e snapshots fiscais
- series e modelos de documentos
- payroll IRPS/INSS
- audit trail minimo
- estabilidade de build e testes

### Riscos de Pos-Lancamento

- falta de pagamentos locais
- cobertura funcional insuficiente em cenarios raros
- divergencia entre regra legal e configuracao do sistema
- suporte operacional sem parceiro contabilistico local

## 9. Definicao De Pronto

Uma fase so deve ser considerada pronta quando houver:

- implementacao concluida
- testes minimos a passar
- revisao por code review
- validacao funcional com caso de uso real
- verificacao legal/fiscal quando aplicavel

## 10. Resultado Esperado

Se este plano for seguido de forma disciplinada, o sistema pode evoluir para:

- ERP SaaS comercialmente viavel em Mocambique
- base operacional util para PMEs
- produto suficientemente local para competir no mercado
- plataforma com margem para futuras certificacoes e integracoes
