# Fixed Assets Scope Decision

Data: 2026-06-04

## Escopo automatizado

O módulo de activos fixos fica fechado para os seguintes fluxos:

- registo do activo;
- depreciação mensal em linha recta;
- baixa contabilística com apuramento de ganho/perda;
- trilha de auditoria para a baixa;
- bloqueio de métodos de depreciação avançados que o motor não executa.

## Fora do escopo automático

Os seguintes temas ficam deliberadamente fora da automatização nesta fase e exigem validação contabilística manual antes de uso operacional:

- reavaliação de activos;
- impairment / imparidade;
- métodos de depreciação avançados além de linha recta;
- fair value adjustments;
- avaliações especiais por perito ou por parecer fiscal/contabilístico.

## Justificação

O núcleo técnico já cobre de forma segura o ciclo operacional mais comum. Os itens acima dependem de regras legais, parecer contabilístico e política interna da empresa, por isso devem ser parametrizados ou tratados manualmente até haver aprovação formal.
