# Manual de Administracao do Assistente de Ativacao

Sistema: INDICO ERP  
Versao do documento: 1.0  
Data: 08/06/2026  
Publico-alvo: superadmin, consultores internos, equipa de suporte e operacoes

## 1. Objetivo do documento

Este manual descreve como administrar a feature de Assistente de Ativacao por dentro do sistema.

O foco nao e a utilizacao final do cliente, mas sim a operacao interna para:

- configurar planos;
- analisar steps de onboarding;
- gerir limites;
- aplicar overrides por empresa;
- validar estados de bloqueio;
- diagnosticar problemas de acesso, permissao e subscricao.

Este documento complementa o manual do cliente. Se o objetivo for treinar utilizadores finais, use o manual funcional e nao este guia interno.

## 2. Onde esta cada area

### 2.1 Company Progress

Menu: `Company Progress`  
Perfil normal de acesso: superadmin ou utilizador com permissao `view-company-onboarding-progress`

Esta area mostra o estado agregado de ativacao por empresa, incluindo:

- score geral;
- pendencias;
- modulos em risco;
- steps bloqueados;
- metricas consolidadas.

[Inserir captura: Superadmin menu > Company Progress]

### 2.2 Meu Plano

Menu: `Meu Plano`  
Perfil normal de acesso: administracao da empresa

Esta pagina resume o plano actual da empresa:

- nome do plano;
- estado da subscricao;
- ciclo de faturacao;
- data de expiracao;
- modulos incluidos;
- add-ons;
- limites;
- sugestoes de upgrade.

[Inserir captura: Company menu > Meu Plano]

### 2.3 Subscription / Plans

Menu: `Subscription` -> `Plans`

Esta area e usada para:

- criar ou editar planos;
- rever familias de planos;
- ajustar valores base;
- validar cobertura de modulos e limites;
- preparar alteracoes de catalogo.

[Inserir captura: Subscription > Plans]

### 2.4 Company Overrides

Menu: `Settings` -> `Company Overrides`  
Permissao para editar: `manage-company-overrides`

Esta area permite conceder excecoes por empresa sem alterar o plano global:

- liberar uma feature;
- elevar um limite;
- manter um registo com notas;
- remover um override quando ja nao for necessario.

[Inserir captura: Settings > Company Overrides]

### 2.5 Add-ons Manager

Menu: `Add-ons Manager`

Esta area e usada para instalar e activar modulos adicionais que nao fazem parte do plano base.

[Inserir captura: Superadmin menu > Add-ons Manager]

### 2.6 Onboarding

Menu: `Onboarding`

Esta pagina mostra o fluxo de ativacao de uma empresa, com:

- steps concluidos;
- steps em progresso;
- steps bloqueados;
- causas de bloqueio;
- CTAs para correcao.

[Inserir captura: Onboarding page]

### 2.7 Auditoria tecnica do contrato

Comando:

```bash
php artisan assistant:plan-contract
php artisan assistant:plan-contract --json
```

O comando gera um relatorio tecnico com:

- versao do contrato;
- requisitos globais;
- planos observados;
- catalogo de modulos;
- matriz de features;
- matriz de permissoes;
- limites de plano;
- registry de onboarding.

## 3. Conceitos que a equipa tem de dominar

### 3.1 Plano

O plano define o que uma empresa pode usar.

Na pratica, o plano controla:

- modulos disponiveis;
- limites numericos;
- add-ons incluidos;
- estado da subscricao;
- sugeroes de upgrade.

Importante:

- alterar um plano afecta todas as empresas que o utilizam;
- nao use o plano para resolver excecoes isoladas de uma so empresa.

### 3.2 Feature

Uma feature representa uma capacidade funcional.

Exemplos:

- emitir documentos;
- executar payroll;
- trabalhar com stock;
- usar tesouraria;
- aceder a relatorios protegidos.

Se uma feature estiver bloqueada, o sistema pode:

- ocultar o menu;
- mostrar um banner de bloqueio;
- redireccionar para a pagina de plano;
- devolver erro de permissao ou de subscricao.

### 3.3 Limit

Um limit e uma restricao numerica.

Exemplos:

- numero maximo de utilizadores;
- numero de armazens;
- numero de contas bancarias;
- tamanho de armazenamento;
- volume de documentos;
- outros limites operacionais definidos no catalogo.

### 3.4 Step de onboarding

Um step e uma tarefa necessaria para deixar a empresa pronta.

Pode depender de:

- dados fiscais;
- configuracao contabilistica;
- permissao de utilizador;
- activacao de modulo;
- stock inicial;
- contas bancarias;
- series documentais.

### 3.5 Override por empresa

Um override e uma excecao local.

Use quando:

- o contrato comercial autoriza uma excecao;
- uma empresa precisa de acesso temporario;
- uma configuracao de transicao e necessaria.

Nao use quando:

- o plano base esta mal desenhado;
- a empresa deveria fazer upgrade;
- a mesma correcao pode ser feita no catalogo do plano.

### 3.6 Add-on

Um add-on e um modulo extra, instalado para ampliar o plano base.

Ele pode:

- activar funcionalidades adicionais;
- desbloquear menus;
- complementar limites ou capacidades;
- mudar a leitura do estado da empresa.

## 4. Fluxo recomendado de administracao

Quando entrar um pedido de ativacao, upgrade ou desbloqueio, siga sempre esta ordem:

1. Confirmar o estado actual da empresa em `Company Progress`.
2. Verificar o plano actual em `Meu Plano`.
3. Validar o contrato do catalogo com `php artisan assistant:plan-contract`.
4. Confirmar se a necessidade deve ser resolvida por:
   - mudanca de plano;
   - activacao de add-on;
   - override por empresa;
   - correcao de permissao;
   - correcao de configuracao.
5. Aplicar a menor mudanca possivel.
6. Revalidar o estado no `Company Progress`.
7. Testar o acesso pela conta da empresa.
8. Registar a justificacao da alteracao.

## 5. Como usar Company Progress

Esta pagina e a visao de controlo operacional.

### 5.1 O que procurar

Verifique sempre:

- score de prontidao;
- pendencias por modulo;
- steps bloqueados;
- motivos de bloqueio;
- sugestoes de correcao;
- metricas agregadas de activacao.

### 5.2 Como interpretar os estados

- `complete`: requisito satisfeito.
- `in_progress`: requisito em curso.
- `blocked`: ha dependencia em falta.
- `permission_missing`: falta permissao ao utilizador.
- `subscription_expired`: subscricao expirada.
- `feature_missing`: a capacidade nao existe no plano.
- `limit_reached`: o limite foi atingido.

### 5.3 Quando usar

Use esta pagina quando precisar de responder a perguntas como:

- porque e que esta empresa nao consegue emitir faturas?
- porque e que o payroll nao aparece?
- porque e que o stock nao baixa?
- porque e que um modulo ficou bloqueado depois de mudar o plano?

[Inserir captura: Company Progress com pendencias e score]

## 6. Como usar Meu Plano

Esta pagina e a referencia rapida do plano da empresa.

### 6.1 O que confirmar

Antes de aceitar um pedido, confirme:

- o plano esta activo;
- a empresa nao esta expirado/inactiva;
- os modulos necessarios estao incluidos;
- os add-ons esperados estao activos;
- os limites cobrem a operacao prevista;
- as sugestoes de upgrade fazem sentido.

### 6.2 Quando consultar

Consulte esta pagina:

- antes de abrir um ticket de suporte;
- antes de modificar um override;
- antes de dizer ao cliente que precisa de upgrade;
- depois de qualquer mudanca de plano.

[Inserir captura: Meu Plano com modulos, limites e sugestoes]

## 7. Como gerir planos

### 7.1 Principio operativo

O plano e a base contratual do sistema.

Sempre que mexer num plano, pense no impacto em:

- todas as empresas ligadas a esse plano;
- limites numericos;
- features activas;
- experiencia do menu;
- estados de onboarding.

### 7.2 O que deve ser revisto num plano

Ao criar ou editar um plano, verifique:

- nome e familia do plano;
- estado activo/inactivo;
- modulos incluidos;
- features vinculadas;
- limites por dimensao;
- add-ons associados;
- preco mensal e anual, quando aplicavel;
- regras de trial e expiracao.

### 7.3 Regra de seguranca

Antes e depois de alterar um plano:

1. correr `php artisan assistant:plan-contract`;
2. confirmar se o catalogo esta consistente;
3. testar uma empresa de exemplo;
4. validar o `Company Progress`.

## 8. Como gerir steps de onboarding

Os steps representam o caminho minimo para a empresa ficar pronta.

### 8.1 O que e importante

Um step pode depender de:

- dados da empresa;
- configuracao fiscal;
- configuracao contabilistica;
- utilizadores com permissao;
- stock inicial;
- contas bancarias;
- calendarios;
- recursos humanos.

### 8.2 Regras praticas

- nao remova um step obrigatorio sem actualizar o contrato e a documentacao;
- nao marque como concluido algo que ainda depende de outra area;
- se um step ficar bloqueado, identifique a dependencia real antes de alterar o plano.

### 8.3 Exemplo de analise

Se a empresa nao consegue emitir documentos fiscais:

1. confirme perfil fiscal;
2. confirme series documentais;
3. confirme periodo contabilistico;
4. confirme permissao do utilizador;
5. confirme a feature e o modulo do plano.

## 9. Como gerir limites

### 9.1 Quando um limite deve ser revisto

Revise limites quando:

- a empresa cresceu;
- o cliente contratou um plano superior;
- um uso temporario precisa de excecao;
- o limite esta a bloquear operacao real.

### 9.2 Regra de decisao

Se o limite for recorrente, prefira:

- upgrade de plano;
- add-on apropriado;
- reestrutura da configuracao.

Se o caso for pontual e contratualmente autorizado, use override.

### 9.3 Exemplos de limites comuns

- utilizadores;
- armazenamento;
- armazens;
- contas bancarias;
- documentos;
- entidades operacionais.

## 10. Como gerir overrides por empresa

### 10.1 Acesso

Abra `Settings` -> `Company Overrides`.

O utilizador precisa de:

- ser superadmin, ou
- ter a permissao `manage-company-overrides`.

### 10.2 Campos do formulario

O formulario pede:

- empresa;
- tipo de override: `feature` ou `limit`;
- chave do override;
- valor do limite, quando aplicavel;
- notas.

### 10.3 Criar um override

1. Abrir `Settings` -> `Company Overrides`.
2. Seleccionar a empresa.
3. Escolher `feature` ou `limit`.
4. Escolher a chave correcta.
5. Preencher o valor, se for um limite.
6. Adicionar uma nota com a justificacao.
7. Guardar.

### 10.4 Editar um override

1. Localizar o override na lista.
2. Clicar em editar.
3. Ajustar a empresa, tipo, chave ou valor.
4. Confirmar a nota.
5. Guardar novamente.

### 10.5 Remover um override

1. Confirmar que o override ja nao e necessario.
2. Abrir a acao de remover.
3. Validar a confirmacao.
4. Guardar a operacao.

### 10.6 Regras de uso

Use overrides apenas quando:

- a excecao esta aprovada;
- o cliente tem justificação contratual;
- a correccao no catalogo global nao e desejada;
- a mudanca precisa de ficar isolada por empresa.

Boas praticas:

- escreva sempre uma nota;
- registe o autor da alteracao;
- reveja overrides antigos periodicamente;
- remova excecoes temporarias depois de cumprirem o proposito.

[Inserir captura: Company Overrides com formulario e lista]

## 11. Add-ons e extensoes

O gestor interno tambem deve validar os add-ons porque eles podem:

- ampliar o plano;
- desbloquear modulos;
- alterar limites percebidos;
- afectar a leitura do menu.

Sequencia recomendada:

1. instalar o add-on;
2. activar o add-on;
3. validar se a feature aparece no `Company Progress`;
4. testar o fluxo da empresa;
5. documentar a alteracao.

## 12. Diagnostico rapido

### 12.1 O modulo nao aparece

Possiveis causas:

- modulo nao esta incluido no plano;
- add-on nao foi activado;
- permissao do utilizador esta em falta;
- a subscricao esta expirada.

Acao:

- consultar `Meu Plano`;
- consultar `Company Progress`;
- validar permissao;
- rever override, se existir.

### 12.2 O limite foi atingido

Possiveis causas:

- plano abaixo do uso real;
- empresa cresceu;
- objecto operacional foi criado alem do maximo permitido.

Acao:

- confirmar o valor do limite;
- avaliar upgrade;
- usar override apenas se houver aprovacao.

### 12.3 A override nao produziu efeito

Possiveis causas:

- empresa errada seleccionada;
- chave errada;
- tipo de override incorrecto;
- nota guardada mas valor nao valido;
- a pagina ainda nao foi revalidada.

Acao:

- confirmar a empresa;
- confirmar a chave;
- confirmar o tipo;
- reabrir `Company Progress`;
- testar a pagina de origem.

### 12.4 O step continua bloqueado

Possiveis causas:

- falta configuracao fiscal;
- falta permissao;
- falta periodo contabilistico;
- falta serie documental;
- falta conta bancaria, armazem ou recurso base.

Acao:

- seguir a dependencia indicada no bloco;
- corrigir a configuracao raiz;
- testar de novo.

### 12.5 O usuario ve pagina em branco ou redirecionamento

Possiveis causas:

- plano expirado;
- feature bloqueada;
- rota protegida por permissao;
- contexto da empresa nao esta correcto.

Acao:

- confirmar o estado da subscricao;
- confirmar o menu visivel;
- confirmar o perfil do utilizador.

## 13. Checklist operacional antes de aprovar uma mudanca

### Antes

- confirmar pedido por escrito;
- identificar empresa e plano;
- ver o contrato actual;
- validar impacto noutras empresas;
- decidir entre plano, add-on ou override.

### Durante

- aplicar a mudanca minima;
- documentar a razao;
- evitar mexer em catalogos globais sem necessidade.

### Depois

- abrir `Company Progress`;
- abrir `Meu Plano`;
- testar o fluxo da empresa;
- validar permissao e menu;
- guardar a evidencia da validacao.

## 14. Capturas recomendadas para este manual

Inserir as seguintes capturas quando o documento for finalizado:

- `Superadmin > Company Progress`
- `Company menu > Meu Plano`
- `Subscription > Plans`
- `Settings > Company Overrides`
- `Add-ons Manager`
- `Onboarding`
- `assistant:plan-contract` no terminal

## 15. Resumo final

O Assistente de Ativacao deve ser administrado com uma regra simples:

- o plano define o que existe;
- o add-on expande o que existe;
- o override resolve excecoes pontuais;
- o onboarding confirma se a empresa esta pronta;
- o Company Progress mostra o estado real;
- o contrato tecnico valida se o catalogo continua coerente.

Se esta sequencia for seguida, a equipa reduz erros, evita bloqueios indevidos e entrega uma experiencia mais previsivel para o cliente final.