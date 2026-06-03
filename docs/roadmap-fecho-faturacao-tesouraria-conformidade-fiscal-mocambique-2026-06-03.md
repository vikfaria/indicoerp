# Roadmap de Fecho - Faturacao, Tesouraria e Conformidade Fiscal Moçambique

Data: 03 de Junho de 2026  
Base: estado actual do codigo no repositorio `sysgest` e analise funcional RF001-RF092.

## 1. Objectivo

Definir o caminho mais curto e seguro para levar o modulo a producao, sem bloquear o go-live por funcionalidades que podem ficar para uma fase posterior.

## 2. Leitura executiva

O modulo ja nao esta em estado "inicial". Neste momento existe base funcional em:

- perfil fiscal da empresa;
- cadastro fiscal de clientes e fornecedores;
- faturacao, series, hash fiscal e snapshots;
- SAF-T e historico de exportacao/submissao;
- contas a pagar/receber, reconciliaçao bancaria e tesouraria base;
- fecho de caixa;
- controlo cambial, GIFiM e moeda electronica a nivel operacional;
- relatórios de compliance, exchange control, GIFiM e electronic money na UI.

O que ainda impede uma assinatura "fechado sem ressalvas" e a harmonizacao final de:

- prazos legais automáticos e alertas de compliance;
- fecho fiscal mais fino de IVA e cenarios especiais;
- ADT e justificacao documental completa;
- formalizacao final de alguns workflows operacionais para producao;
- registo e auditoria de submissao/validacao fiscal em todos os fluxos.

## 3. Corte de producao

Para ir a producao, a regra deve ser:

1. fechar o que e impeditivo legal/operacional;
2. validar o que ja existe com testes e deploy real;
3. adiar o que aumenta qualidade, mas nao bloqueia a primeira versao comercial.

## 4. Roadmap recomendado

### Fase P0 - Go-live hardening

Prioridade: `muito alta`  
Estimativa: `4-7 pessoa-dias`

Entregaveis:

- validar a coerencia entre schema, controllers, requests e UI nos fluxos ja abertos;
- rodar migracoes, cache clear, queue/scheduler e healthcheck em ambiente equivalente a producao;
- fechar regressao dos relatórios novos de compliance;
- garantir que os endpoints de exportacao funcionam com dados reais de piloto;
- consolidar a lista de permissões e auditoria para as accoes de compliance ja introduzidas.

Critico para producao:

- sim.

### Fase P1 - Fecho legal minimo

Prioridade: `muito alta`  
Estimativa: `6-10 pessoa-dias`

Entregaveis:

- motor de prazos legais automáticos e alertas por evento:
  - emissao tardia de factura;
  - obrigacoes de IVA;
  - submissao SAF-T;
  - compliance GIFiM;
  - repatriacao e pendencias cambiais;
- historico formal e prova de submissao/exportacao fiscal;
- validacoes finais de motivo de isencao, submissao e referencia fiscal;
- reforco de trilha de auditoria para as accoes criticas de fiscalidade e tesouraria.

Critico para producao:

- sim, se o go-live for vendido como versao comercial de verdade e nao apenas demo.

### Fase P2 - Fecho de conformidade financeira avancada

Prioridade: `alta`  
Estimativa: `6-9 pessoa-dias`

Entregaveis:

- ADT com certificado de residencia fiscal e comparacao de taxa aplicada;
- classificacao mais fina de operacoes digitais e reverse charge;
- dossier documental por fornecedor e por operacao internacional;
- refinamento dos relatórios de tesouraria, cambial e compliance.

Critico para producao:

- nao, mas recomendado antes de escalar clientes com operacoes internacionais.

### Fase P3 - Pos-prod e optimizacao

Prioridade: `media`  
Estimativa: `6-12 pessoa-dias`

Entregaveis:

- import/export em massa de master data e documentos;
- integracao mais profunda com fluxos externos da Autoridade Tributaria, se existir canal oficial util;
- mais formatos bancarios locais;
- dashboards analiticos e UX adicional;
- refinamentos de moeda electronica por nivel/regra empresarial.

Critico para producao:

- nao.

## 5. O que pode ficar para depois

Se houver necessidade de entrar em producao mais cedo, pode ser adiado:

- integracao webservice oficial com a AT, se ainda nao existir canal estavel;
- bulk import/export completo de clientes, fornecedores, produtos e documentos;
- relatórios extra de BI/analitica;
- polimento visual dos novos ecras;
- regras avancadas de moeda electronica por nivel, se o cliente nao usar esse meio;
- automatismos extra de exportacao e submissao, desde que o registo manual fique auditado.

## 6. O que nao deve ser adiado

Nao recomendo adiar:

- validacao dos dados fiscais basicos da empresa;
- registo fiscal minimo de clientes e fornecedores;
- hash e inalterabilidade dos documentos fiscais;
- historico de exportacao SAF-T;
- controlos de repatriacao, GIFiM e caixa;
- registo de auditoria das accoes criticas;
- migracoes e testes de regressao antes do deploy.

## 7. Estimativa total

Se o objectivo for apenas colocar a versao em producao com risco controlado:

- **4-7 pessoa-dias** adicionais.

Se o objectivo for fechar a maior parte dos gaps ainda relevantes para compliance local:

- **16-26 pessoa-dias** adicionais.

Se o objectivo for fechar tudo o que aparece no gap analysis original, incluindo os itens que podem viver bem numa fase posterior:

- **22-38 pessoa-dias** adicionais.

## 8. Recomendacao pragmatica

Nao tentaria fechar tudo antes de vender.

Eu faria assim:

1. fechar P0 e P1;
2. fazer migrate/deploy;
3. executar piloto com cliente real;
4. usar o feedback do piloto para priorizar P2;
5. deixar P3 para depois da validacao comercial.

Isto reduz risco, evita over-engineering e permite colocar o produto em mercado sem perder o controlo tecnico.
