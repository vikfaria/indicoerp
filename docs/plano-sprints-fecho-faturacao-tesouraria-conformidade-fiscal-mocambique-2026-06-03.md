# Plano de Sprints - Fecho de Faturacao, Tesouraria e Conformidade Fiscal Moçambique

Data: 03 de Junho de 2026  
Base: [Roadmap de Fecho - Faturacao, Tesouraria e Conformidade Fiscal Moçambique](./roadmap-fecho-faturacao-tesouraria-conformidade-fiscal-mocambique-2026-06-03.md)

## 1. Objectivo

Converter o roadmap em lotes executaveis, com ordem de entrega, dependencias, estimativa de esforco e criterio de aceite, para permitir ida a producao com risco controlado.

## 2. Premissas

- O codigo actual ja tem base funcional relevante em fiscalidade, faturacao, tesouraria, SAF-T e relatórios.
- O objectivo imediato nao e "fechar tudo", mas sim fechar o necessario para uma versao comercial segura.
- Migracoes, testes de regressao e build frontend entram em cada sprint como criterio minimo de aceite.
- O que nao bloqueia o go-live entra como backlog de pos-prod.

## 3. Ordem recomendada

1. Sprint 0 - estabilizacao e corte de producao
2. Sprint 1 - fecho legal minimo
3. Sprint 2 - fiscalidade operacional avancada
4. Sprint 3 - tesouraria, reporting e controlo financeiro
5. Sprint 4 - pos-prod e optimizacao

## 4. Sprint 0 - Estabilizacao e corte de producao

Prioridade: muito alta  
Estimativa: 2-4 pessoa-dias

### Objectivo

Garantir que a base actual e segura para continuar evolucao sem introduzir regressao estrutural.

### Entregaveis

- validar schema, controllers, requests e UI dos fluxos ja abertos;
- consolidar permissões e acesso aos modulos fiscais e financeiros;
- confirmar que build frontend, testes alvo e healthcheck passam;
- preparar pacote de migracoes pendentes para execucao controlada;
- rever logs e pontos de falha conhecidos.

### Criterio de aceite

- `php artisan test` nos testes alvo passa;
- `npm run build` passa;
- healthcheck de aplicacao e servicos principais passa;
- nao ha bloqueios nos fluxos de faturacao, tesouraria e relatórios.

## 5. Sprint 1 - Fecho legal minimo

Prioridade: muito alta  
Estimativa: 4-6 pessoa-dias

### Objectivo

Fechar os pontos que mais impactam conformidade legal imediata.

### Entregaveis

- motor de prazos legais automaticos;
- alertas para emissao tardia, IVA, SAF-T, repatriacao, GIFiM e caixa;
- historico formal de exportacao/submissao fiscal;
- prova de submissao com referencia/registo auditavel;
- reforco de trilha de auditoria para accoes criticas;
- validacoes finais de motivos de isencao e referencias fiscais.

### Criterio de aceite

- documentos fiscais tardios sao sinalizados;
- exportacoes SAF-T ficam registadas com hash e metadados;
- eventos criticos ficam auditados;
- alertas de compliance aparecem no painel.

## 6. Sprint 2 - Fiscalidade operacional avancada

Prioridade: alta  
Estimativa: 5-7 pessoa-dias

### Objectivo

Fechar o miolo fiscal que afecta operacoes com terceiros, tributacao e enquadramento de documentos.

### Entregaveis

- ADT com certificado de residencia fiscal e comparacao de taxa;
- classificacao fina de operacoes digitais e reverse charge;
- dossier documental por fornecedor e por operacao internacional;
- refinamento do fecho de IVA para cenarios especiais;
- endurecimento de validacoes de clientes e fornecedores.

### Criterio de aceite

- fornecedores e clientes nao entram com classificacao fiscal incompleta;
- reverse charge e ADT ficam rastreaveis;
- o fecho de IVA cobre casos digitais, isentos e autoliquidacao;
- documentos de suporte ficam associados ao processo.

## 7. Sprint 3 - Tesouraria, reporting e controlo financeiro

Prioridade: media-alta  
Estimativa: 4-7 pessoa-dias

### Objectivo

Consolidar a camada operacional financeira para uso real em empresa.

### Entregaveis

- fecho diario de caixa e exportacao do historial;
- consolidacao de contas cashbox/petty cash;
- relatórios de compliance financeiro, exchange control, GIFiM e electronic money;
- melhor cobertura de pagamentos em numerario, moeda electronica e cambio;
- revisao final dos dashboards operacionais.

### Criterio de aceite

- caixa fecha diariamente com rastreio;
- pagamentos por conta errada sao bloqueados;
- relatórios operacionais e de compliance refletem o estado real;
- controlos por moeda e meio de pagamento funcionam.

## 8. Sprint 4 - Pos-prod e optimizacao

Prioridade: media  
Estimativa: 5-10 pessoa-dias

### Objectivo

Tratar o que melhora escala, operacao e manutencao, mas nao bloqueia producao.

### Entregaveis

- import/export em massa;
- mais formatos bancarios e integrações externas;
- refinamentos de UX e dashboards;
- automacoes adicionais para AT, se existir canal util;
- optimizacoes de relatorios e filtros;
- refinamentos de moeda electronica por nivel e regras empresariais.

### Criterio de aceite

- o sistema continua estavel sem estas funcoes;
- os lotes de pos-prod nao afectam a operacao principal;
- cada item entra por prioridade de negocio e nao por urgencia tecnica.

## 9. Itens que entram antes de produção

Nao recomendo adiar para depois do go-live:

- perfil fiscal base da empresa;
- cadastro fiscal minimo de clientes e fornecedores;
- inalterabilidade de documentos fiscais;
- historico de exportacao SAF-T;
- audit trail das accoes criticas;
- controlos de repatriacao, GIFiM e caixa;
- testes de regressao e migracoes.

## 10. Itens que podem ficar para depois

Podem ser adiados sem bloquear producao:

- integracao automatica com a AT, se nao houver canal estavel;
- bulk import/export completo;
- BI e analytics extra;
- mais formatos bancarios locais;
- refinamentos de UX nao criticos;
- regras avancadas de moeda electronica por nivel.

## 11. Estimativa total

### Para ir a producao com risco controlado

- `6-10` pessoa-dias, se o foco for apenas fechar Sprint 0 + Sprint 1.

### Para ficar comercialmente robusto

- `15-22` pessoa-dias, se o foco incluir Sprint 2 e parte de Sprint 3.

### Para fechar praticamente tudo do gap analysis

- `20-33` pessoa-dias, incluindo Sprint 4.

## 12. Recomendacao de execucao

1. Fechar Sprint 0.
2. Fechar Sprint 1.
3. Fazer migrate, build e deploy.
4. Rodar piloto com dados reais.
5. Usar o feedback do piloto para priorizar Sprint 2 e Sprint 3.
6. Deixar Sprint 4 para pos-prod, salvo necessidade comercial imediata.

