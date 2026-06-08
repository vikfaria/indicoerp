# Manual de Utilizador

Sistema: ERP integrado  
Versao do documento: 1.0  
Data: 05/06/2026  
Publico-alvo: clientes finais, administradores de empresa e equipa de validacao

## 1. Objetivo do documento

Este manual foi preparado para dois fins:

- servir como manual de utilizador para clientes finais;
- servir como roteiro de validacao funcional dos principais modulos do sistema.

O documento foi organizado com prioridade operacional nos seguintes modulos:

1. Facturacao
2. Contabilidade
3. Recursos Humanos
4. Tesouraria

Como o sistema e integrado, cada secao explica tambem o impacto das operacoes nos restantes modulos.

## 2. Visao geral do sistema

O sistema e um ERP modular. Isso significa que uma empresa pode ter acesso apenas aos modulos incluidos no plano activo e pagos, ou aos modulos adicionais activados como add-on.

As areas mais importantes para operacao diaria sao:

- Faturacao e vendas
- Compras e devolucoes
- Contabilidade geral e fiscal
- Recursos humanos e folha salarial
- Tesouraria e bancos
- Produto, stock, armazens e POS

## 3. Como funcionam planos, modulos e permissoes

### 3.1 Plano de subscricao

O acesso ao sistema depende de um plano activo. Quando o plano expira ou fica inactivo:

- o utilizador da empresa pode ser redireccionado para a area de planos;
- subutilizadores podem perder o acesso ate o plano ser renovado;
- funcoes pagas ou modulos add-on deixam de ficar disponiveis.

[Inserir captura: menu Plan > Setup Subscription Plan]

### 3.2 Modulos activos

Cada modulo pode estar:

- activo no plano;
- desactivado por falta de subscricao;
- instalado mas sem permissao para o utilizador actual.

Se um modulo nao estiver activo:

- o menu pode nao aparecer;
- o utilizador pode receber "Permission denied";
- determinadas paginas podem redireccionar para o dashboard.

Exemplos de modulos que condicionam este manual:

- `Product & Service`
- `Account`
- `Double Entry`
- `Hrm`
- `Pos`

### 3.3 Permissoes por perfil

Mesmo com o modulo activo, o utilizador so consegue operar se tiver permissao. Na pratica:

- o administrador da empresa costuma ver todos os menus;
- operadores veem apenas os ecras autorizados;
- botoes como `Create`, `Post`, `Approve`, `Print`, `Run Payroll` ou `Reports` podem desaparecer se faltar permissao.

[Inserir captura: User Management > Roles]

### 3.4 O que validar antes de iniciar testes

Antes de testar qualquer modulo, confirmar:

- plano activo;
- modulos necessarios activos;
- utilizador com permissao adequada;
- empresa, moeda, impostos e dados fiscais configurados;
- pelo menos um banco, um armazem e um utilizador operacional quando aplicavel.

## 4. Ordem recomendada de configuracao inicial

Para evitar erros de dependencia entre modulos, recomenda-se esta ordem:

1. Configurar empresa, moeda, idioma e branding.
2. Criar perfis, roles e utilizadores.
3. Activar modulos necessarios no plano.
4. Configurar armazens e transferencias.
5. Configurar `Product & Service`: categorias, unidades, impostos e itens.
6. Configurar clientes e fornecedores.
7. Configurar contas bancarias.
8. Configurar fiscalidade SCE: perfil fiscal, calendario, series documentais e PGC.
9. Configurar RH: filiais, departamentos, cargos, feriados, tipos de salario e parametros legais.
10. Iniciar operacao por modulo.

## 5. Matriz de integracao entre modulos

| Acao | Impacto directo | Impacto indirecto |
|---|---|---|
| Criar item de produto | Disponivel em vendas, compras e POS | Afecta stock, custo e relatorios |
| Criar cliente | Permite fatura de venda e recebimento | Afecta saldo de cliente e ageing |
| Criar fornecedor | Permite compra e pagamento | Afecta saldo de fornecedor e ageing |
| Postar fatura de venda | Fixa documento fiscal e valor a receber | Afecta cliente, relatorios fiscais e contabilisticos |
| Registar recebimento | Reduz saldo do cliente | Afecta tesouraria, bancos e reconciliacao |
| Postar fatura de compra | Fixa documento de fornecedor | Afecta saldo de fornecedor e obrigacoes fiscais |
| Registar pagamento a fornecedor | Reduz saldo do fornecedor | Afecta tesouraria, banco e relatorios cambiais quando aplicavel |
| Marcar presencas | Alimenta folha salarial | Afecta horas, trabalho nocturno e custo salarial |
| Correr payroll | Gera valores de salario, IRPS e INSS | Afecta tesouraria, journals e mapas de RH |
| Pagar salario | Liquida payslip | Afecta banco, contabilidade e tesouraria |
| Venda POS | Regista venda imediata | Afecta stock, caixa, fiscalidade e fecho de caixa |

## 6. Guia rapido para capturas de ecra

Em todo o manual ha marcadores para capturas. Recomenda-se inserir imagens:

- com o nome do menu visivel;
- com o botao principal destacado;
- sem dados sensiveis reais;
- com exemplos consistentes entre modulos.

Formato recomendado:

- `[Inserir captura: Menu > Submenu > Ecra]`

## 7. Facturacao

### 7.1 Ambito

Este bloco cobre:

- cadastro de produtos e servicos;
- cadastro de clientes;
- faturas de venda;
- faturas de compra;
- devolucoes de venda e compra;
- propostas comerciais;
- POS, quando activo;
- impressao e fiscalizacao de documentos.

### 7.2 Pre-requisitos

Confirmar:

- modulo `Product & Service` activo;
- modulo `Account` activo;
- utilizador com permissoes de vendas, compras, clientes e fornecedores;
- pelo menos um armazem activo para produtos;
- impostos, unidades e categorias configurados;
- series documentais e perfil fiscal configurados para operacao fiscal completa.

### 7.3 Menus principais

- `Product & Service` -> `Items`
- `Product & Service` -> `System Setup`
- `Sales Invoice` -> `Sales Invoice`
- `Sales Invoice` -> `Sales Invoice Returns`
- `Purchase` -> `Purchase Invoice`
- `Purchase` -> `Purchase Returns`
- `Proposal`
- `POS` -> `Add POS` e `POS Orders` quando o modulo estiver activo

[Inserir captura: Product & Service > Items]  
[Inserir captura: Sales Invoice > Sales Invoice]  
[Inserir captura: Purchase > Purchase Invoice]

### 7.4 Configuracao base de facturacao

### 7.4.1 Criar categorias, unidades e impostos

1. Aceda a `Product & Service` -> `System Setup`.
2. Configure:
   - categorias;
   - unidades;
   - impostos.
3. Grave cada registo.

Observacoes:

- sem imposto configurado, os documentos podem ficar sem calculo fiscal correcto;
- sem unidade, alguns itens podem ficar incompletos.

### 7.4.2 Criar item de produto

1. Aceda a `Product & Service` -> `Items`.
2. Clique em `Create`.
3. Em `Details`, escolha `Product`.
4. Preencha nome, SKU, categoria e imposto.
5. Em `Pricing`, informe preco de venda, preco de compra, unidade e quantidade.
6. Em `Warehouse`, seleccione o armazem.
7. Grave.

Resultado esperado:

- o produto fica disponivel para faturas e POS;
- o stock passa a ser relevante para venda e devolucao.

[Inserir captura: Product & Service > Create Item > Product]

### 7.4.3 Criar item de servico

1. Aceda a `Product & Service` -> `Items`.
2. Clique em `Create`.
3. Em `Details`, escolha `Service`.
4. Preencha nome, SKU, categoria e imposto.
5. Em `Pricing`, informe o preco.
6. Grave.

Observacao:

- servico nao depende de stock nem de armazem.

### 7.4.4 Criar cliente

1. Aceda a `Contabilidade Geral` -> `Customers`.
2. Clique em `Create Customer`.
3. Preencha empresa, contacto, email, telefone e morada.
4. Preencha `Tax Number` quando aplicavel.
5. Grave.

### 7.4.5 Criar fornecedor

1. Aceda a `Contabilidade Geral` -> `Vendors`.
2. Clique em `Create Vendor`.
3. Preencha dados fiscais, de contacto e morada.
4. Grave.

### 7.5 Emitir fatura de venda

1. Aceda a `Sales Invoice` -> `Sales Invoice`.
2. Clique em `Create Sales Invoice`.
3. Escolha o tipo:
   - `Product Wise` para produtos;
   - `Service Wise` para servicos.
4. Preencha:
   - data da fatura;
   - data de vencimento;
   - cliente;
   - armazem, quando for produto.
5. Adicione os itens.
6. Revise subtotal, imposto, desconto e total.
7. Grave em `Create`.
8. No detalhe ou listagem, faca `Post` para finalizar.
9. Use `Print` ou `Download PDF` quando necessario.

Observacoes importantes:

- uma fatura `draft` pode ser revista;
- uma fatura `posted` passa a ser operacional e fiscalmente sensivel;
- depois de fiscalizada ou cancelada, a alteracao directa fica limitada.

[Inserir captura: Sales Invoice > Create Sales Invoice]  
[Inserir captura: Sales Invoice > Acao Post]

### 7.6 Emitir fatura de compra

1. Aceda a `Purchase` -> `Purchase Invoice`.
2. Clique em `Create Purchase Invoice`.
3. Seleccione o fornecedor.
4. Informe datas, referencia e itens.
5. Grave.
6. Faça `Post` para confirmar a compra.

Impactos:

- aumenta a base de obrigacoes com fornecedores;
- pode afectar stock, custo e mapas fiscais;
- prepara o pagamento em tesouraria.

[Inserir captura: Purchase > Create Purchase Invoice]

### 7.7 Registar devolucao de venda

1. Aceda a `Sales Invoice` -> `Sales Invoice Returns`.
2. Clique em `Create`.
3. Associe a devolucao a uma venda ou preencha os dados necessarios.
4. Grave.
5. Faça `Approve`.
6. Quando aplicavel, faça `Complete`.

Observacao:

- a devolucao pode ter reflexo em nota de credito, stock e fiscalidade.

### 7.8 Registar devolucao de compra

1. Aceda a `Purchase` -> `Purchase Returns`.
2. Clique em `Create`.
3. Informe fornecedor, itens e motivo.
4. Grave.
5. Faça `Approve`.
6. Quando aplicavel, faça `Complete`.

Observacao:

- a devolucao pode ter reflexo em nota de debito, stock e saldo de fornecedor.

### 7.9 Proposta comercial

1. Aceda a `Proposal`.
2. Clique em `Create Sales Proposal`.
3. Preencha cliente, datas e itens.
4. Grave.
5. Use as accoes:
   - `Sent`
   - `Accept`
   - `Reject`
   - `Convert to Invoice`

Impacto:

- quando convertida, a proposta gera fluxo de facturacao com menor retrabalho.

[Inserir captura: Proposal > Create Sales Proposal]

### 7.10 POS

Esta secao aplica-se apenas se o modulo `Pos` estiver activo.

### Fluxo

1. Aceda a `POS` -> `Add POS`.
2. Seleccione produtos.
3. Confirme quantidades, cliente e total.
4. Grave a venda.
5. Consulte em `POS Orders`.
6. Use `Print` ou `Barcode` quando necessario.

Impacto:

- baixa stock;
- regista venda imediata;
- alimenta tesouraria, caixa, fiscalidade e relatorios POS.

[Inserir captura: POS > Add POS]

### 7.11 Exemplo pratico de facturacao

### Exemplo A: venda a prazo

1. Criar cliente.
2. Criar produto.
3. Emitir fatura de venda.
4. Fazer `Post`.
5. Registar recebimento parcial em `Customer Payments`.
6. Registar recebimento final.

Validacao esperada:

- a fatura passa de `draft` para `posted`;
- o saldo do cliente reduz a cada recebimento;
- a venda aparece em relatorios fiscais e de cliente.

### Exemplo B: compra com devolucao parcial

1. Criar fornecedor.
2. Registar fatura de compra.
3. Fazer `Post`.
4. Criar devolucao de compra para um item.
5. Aprovar e completar a devolucao.

Validacao esperada:

- o saldo do fornecedor reflecte a operacao;
- a devolucao fica rastreavel;
- as areas fiscal e contabilistica recebem o reflexo correcto.

### 7.12 Erros comuns em facturacao e como resolver

| Erro | Causa comum | Solucao |
|---|---|---|
| Item nao aparece na venda | sem stock, item inactivo ou tipo errado | validar stock, item activo e tipo `Product` ou `Service` |
| Botao `Create` ou `Post` nao aparece | falta permissao | rever role do utilizador |
| Cliente ou fornecedor nao aparece | cadastro inexistente ou sem permissao | criar cadastro e confirmar permissao |
| Documento nao pode ser editado | ja foi postado ou fiscalizado | usar devolucao, nota ou fluxo de rectificacao |
| Erro fiscal ou serie indisponivel | perfil fiscal ou serie documental nao configurados | rever `Contabilidade > Fiscal` |

### 7.13 Recomendacoes de teste para facturacao

Executar pelo menos:

1. Venda de produto com stock.
2. Venda de servico sem stock.
3. Venda a prazo com recebimento parcial e total.
4. Compra a fornecedor com pagamento posterior.
5. Devolucao de venda.
6. Devolucao de compra.
7. Conversao de proposta em fatura.
8. Venda POS, se o modulo existir.

[Inserir captura: evidencias finais do bloco Facturacao]

## 8. Contabilidade

### 8.1 Ambito

Este bloco cobre:

- clientes, fornecedores e bancos;
- plano de contas;
- receitas, despesas e pagamentos;
- relatorios contabilisticos;
- fiscalidade SCE;
- impostos, fechos e exportacoes;
- modulo `Double Entry`;
- activos fixos.

### 8.2 Menus principais

- `Contabilidade`
- `Contabilidade > Contabilidade SCE`
- `Contabilidade > Fiscal`
- `Contabilidade > Impostos`
- `Contabilidade > Activos`
- `Contabilidade > Relatorios`
- `Contabilidade Geral`
- `Double Entry`

[Inserir captura: menu Contabilidade]

### 8.2.1 Primeiro arranque de uma empresa nova

Se a empresa foi criada agora, siga esta ordem antes de emitir a primeira factura:

1. Abra `Contabilidade` -> `Contabilidade SCE` -> `Fiscal`.
2. Preencha e guarde o `Perfil Fiscal` com pelo menos:
   - `NUIT`;
   - `Regime Fiscal`;
   - `Referencial Contabilístico`;
   - `Classificação da Entidade`;
   - `Tipo de Contribuinte`;
   - `Estado de Certificação`;
   - `Número do Certificado de Software`.
3. No mesmo ecrã, clique em `Gerar` em `Períodos Contabilísticos`.
4. Confirme que existe um período aberto para o mês da factura.
5. Abra `Contabilidade` -> `Contabilidade SCE` -> `Séries Documentais`.
6. Crie pelo menos uma série activa para cada tipo de documento que vai usar:
   - fatura de venda;
   - fatura de compra;
   - devolução de venda;
   - devolução de compra;
   - POS, se aplicável.
7. Verifique que a data do documento está dentro de um período aberto.

Observação importante:

- o erro `Não existe período contabilístico definido para a data 2026-06-05.` significa que a data escolhida para a factura não está coberta por nenhum período contabilístico aberto;
- se estiver em fase de configuração inicial, gere os períodos antes de emitir a primeira factura.

### 8.3 Configuracao contabilistica minima

### 8.3.1 Contas bancarias

1. Aceda a `Contabilidade Geral` -> `Banking` -> `Bank Accounts`.
2. Clique em `Create`.
3. Preencha nome do banco, conta, moeda, filial e dados necessarios.
4. Grave.

Observacao:

- contas bancarias sao usadas em recebimentos, pagamentos, transferencias, payroll e tesouraria.

### 8.3.2 Plano de contas

1. Aceda a `Contabilidade Geral` -> `Chart Of Accounts`.
2. Crie ou valide as contas principais.
3. Se aplicavel, valide o mapeamento PGC em `Contabilidade > Fiscal > Plano de Contas PGC`.

Observacao:

- o ambiente moçambicano pode exigir conciliacao com PGC e mapas fiscais especificos.

### 8.3.3 Diarios e perfil fiscal

1. Aceda a `Contabilidade > Contabilidade SCE` -> `Diarios`.
2. Aceda a `Contabilidade > Fiscal` -> `Perfil Fiscal`.
3. Configure dados fiscais da empresa.
4. Gere periodos fiscais e valide o calendario.

[Inserir captura: Contabilidade > Fiscal > Perfil Fiscal]  
[Inserir captura: Contabilidade > Fiscal > Series Documentais]

### 8.4 Receitas e despesas

### Receita

1. Aceda a `Contabilidade Geral` -> `Revenue`.
2. Clique em `Create`.
3. Informe categoria, conta, valor, banco e referencia.
4. Grave.
5. Use `Approve` e `Post` quando aplicavel.

### Despesa

1. Aceda a `Contabilidade Geral` -> `Expense`.
2. Clique em `Create`.
3. Informe categoria, conta, valor, banco e referencia.
4. Grave.
5. Use `Approve` e `Post` quando aplicavel.

Observacao:

- se a empresa trabalha apenas por faturacao e pagamentos, este menu pode ser usado para registos adicionais nao originados por faturas.

### 8.5 Recebimentos e pagamentos

### Recebimentos de cliente

1. Aceda a `Contabilidade Geral` -> `Customer Payments`.
2. Clique em `Create Customer Payment`.
3. Escolha cliente, data, conta bancaria e metodo.
4. Seleccione a fatura pendente.
5. Grave.
6. Aprove ou actualize o estado, quando exigido pelo fluxo.

### Pagamentos a fornecedor

1. Aceda a `Contabilidade Geral` -> `Vendor Payments`.
2. Clique em `Create Vendor Payment`.
3. Escolha fornecedor, conta bancaria, data e referencia.
4. Seleccione a factura em aberto.
5. Grave.
6. Aprove ou actualize o estado, quando aplicavel.

Impactos:

- reduzem saldos de cliente e fornecedor;
- afectam bancos, tesouraria e relatorios de aging;
- podem alimentar conformidade cambial e fiscal.

### 8.6 Relatorios contabilisticos e fiscais

### Relatorios gerais

Em `Contabilidade Geral` -> `Reports`, validar:

- Invoice Aging
- Bill Aging
- Tax Summary
- Customer Balance
- Vendor Balance

### Conformidade fiscal e tesouraria

No mesmo ecra de relatorios, validar quando o plano/permissao permitir:

- Mapa Fiscal de Mocambique
- Declaracao de IVA
- Registo de Submissoes Fiscais
- Alertas de Conformidade Fiscal
- Relatorio Cambial
- Relatorio GIFiM
- Relatorio de Moeda Electronica
- Cost Center Analysis
- Fiscal Export History
- Exportacao SAF-T
- Fiscal Closing
- Cash Closing
- Go-Live Readiness

[Inserir captura: Contabilidade Geral > Reports]

### 8.7 Contabilidade SCE e relatorios financeiros

Em `Contabilidade > Relatorios`:

- Balanço
- Demonstracao de Resultados
- Alteracoes no Capital Proprio
- Fluxos de Caixa

Em `Double Entry`:

- Ledger Summary
- Trial Balance
- Balance Sheets
- Profit & Loss
- General Ledger
- Journal Entry
- Account Balance
- Cash Flow

Observacao:

- `Contabilidade` e `Double Entry` podem coexistir no mesmo cliente, dependendo do plano e do desenho operacional.

### 8.8 Fechos contabilisticos e fiscais

### Fecho mensal

1. Aceda a `Contabilidade > Contabilidade SCE` -> `Fecho Mensal`.
2. Inicie o fecho.
3. Complete os checks exigidos.
4. Finalize quando todos os pontos estiverem consistentes.

### Fecho fiscal

1. Aceda a `Contabilidade Geral` -> `Reports` -> `Fiscal Closing`.
2. Reveja periodos.
3. Feche o periodo.
4. Reabra apenas quando for necessario e autorizado.

### 8.9 Activos fixos

1. Aceda a `Contabilidade > Activos` -> `Activos Fixos`.
2. Clique em `Novo Activo`.
3. Registe codigo, nome, valor, data e metodo suportado.
4. Grave.
5. Corra depreciacao quando necessario.
6. Use `Dispose` quando houver baixa.

Observacao:

- o sistema cobre registo, depreciacao e baixa contabilistica;
- reavaliacao e tratamentos especiais devem ser validados pela contabilidade da empresa.

### 8.10 Exemplo pratico de contabilidade

### Exemplo A: venda e recebimento

1. Emitir e postar fatura de venda.
2. Registar recebimento.
3. Validar saldo de cliente.
4. Validar `Invoice Aging`.
5. Validar mapa fiscal.
6. Validar balancete ou razao, se o modulo estiver activo.

### Exemplo B: payroll e reflexo contabilistico

1. Correr payroll.
2. Pagar um recibo salarial.
3. Confirmar movimento bancario.
4. Confirmar relatorio de custo salarial ou journal correspondente.

### 8.11 Erros comuns em contabilidade e como resolver

| Erro | Causa comum | Solucao |
|---|---|---|
| Relatorio vazio | sem permissao, sem dados ou periodo errado | rever permissao e filtros |
| Conta bancaria nao aparece | conta nao cadastrada ou sem permissao | validar cadastro e role |
| Saldo de cliente/fornecedor nao bate | documento sem pagamento ou pagamento nao aprovado | rever estado dos documentos |
| SAF-T ou exportacao fiscal falha | perfil fiscal, series ou configuracao legal incompletos | validar `Perfil Fiscal`, `Series` e dados da empresa |
| Fecho nao conclui | checks anteriores em falta | completar pendencias antes de finalizar |

### 8.12 Recomendacoes de teste para contabilidade

Executar pelo menos:

1. Validacao de um cliente com venda e recebimento.
2. Validacao de um fornecedor com compra e pagamento.
3. Consulta de `Tax Summary`.
4. Consulta de `Customer Balance` e `Vendor Balance`.
5. Exportacao SAF-T.
6. Validacao de `Trial Balance` e `General Ledger`, se o modulo estiver activo.
7. Fecho fiscal ou mensal num periodo de teste.

[Inserir captura: evidencias finais do bloco Contabilidade]

## 9. Recursos Humanos

### 9.1 Ambito

Este bloco cobre:

- estrutura organizacional;
- cadastro de colaboradores;
- perfis legais e documentos;
- presencas e turnos;
- faltas, ferias e saldo;
- salario base, subsidios, descontos e emprestimos;
- payroll;
- pagamentos salariais;
- movimentacoes disciplinares e desligamentos;
- relatorios de conformidade de RH.

### 9.2 Menus principais

- `HRM` -> `Employees`
- `HRM` -> `Payslip` -> `Set Salary`
- `HRM` -> `Payslip` -> `Payroll`
- `HRM` -> `Attedance`
- `HRM` -> `Leave Management`
- `HRM` -> `Documents`
- `HRM` -> `Acknowledgments`
- `HRM` -> `Resignations`
- `HRM` -> `Terminations`
- `HRM` -> `System Setup`

[Inserir captura: menu HRM]

### 9.3 Configuracao base de RH

### 9.3.1 Estrutura organizacional

1. Aceda a `HRM` -> `System Setup`.
2. Configure:
   - filiais;
   - departamentos;
   - cargos;
   - tipos documentais;
   - dias de trabalho;
   - feriados;
   - tipos de subsidio, desconto e emprestimo.

### 9.3.2 Parametros de conformidade

Quando o cliente utilizar a localizacao de Mocambique:

1. Aceda a `hrm/mozambique-payroll-compliance` pela area de RH correspondente.
2. Valide:
   - politica laboral;
   - parametros legais;
   - tabelas IRPS;
   - taxas INSS;
   - salarios minimos;
   - mapeamentos de centro de custo e presenca.

Observacao:

- esta parte e critica para payroll correcto.

### 9.4 Criar colaborador

1. Aceda a `HRM` -> `Employees`.
2. Clique em `Create`.
3. Preencha dados pessoais, laborais e de contacto.
4. Associe filial, departamento e cargo.
5. Grave.
6. No detalhe do colaborador, actualize quando aplicavel:
   - perfil de seguranca social;
   - perfil de trabalhador estrangeiro;
   - perfil probatorio;
   - dependentes;
   - documentos anexos.

[Inserir captura: HRM > Employees > Create]

### 9.5 Turnos e presencas

### Criar turno

1. Aceda a `HRM` -> `Attedance` -> `Shifts`.
2. Clique em `Create`.
3. Defina horario e regras.
4. Grave.

### Registar presenca

1. Aceda a `HRM` -> `Attedance` -> `Attendances`.
2. Clique em `Create` ou use `clock-in`/`clock-out` quando o fluxo estiver activo.
3. Seleccione colaborador, data, turno e horas.
4. Grave.

Observacoes:

- as presencas alimentam horas trabalhadas, faltas e payroll;
- trabalho nocturno e horas extra devem ser validados antes de correr a folha.

[Inserir captura: HRM > Attendances]

### 9.6 Ferias e faltas

### Tipos de ferias

1. Aceda a `HRM` -> `Leave Management` -> `Leave Types`.
2. Crie ou reveja os tipos.

### Pedido de ferias

1. Aceda a `Leave Applications`.
2. Clique em `Create`.
3. Seleccione colaborador, tipo e periodo.
4. Grave.
5. Actualize o estado conforme o fluxo interno.

### Saldo de ferias

1. Aceda a `Leave Balance`.
2. Consulte:
   - total atribuido;
   - dias aprovados;
   - dias pendentes;
   - dias usados;
   - saldo disponivel.

### 9.7 Definir salario

1. Aceda a `HRM` -> `Payslip` -> `Set Salary`.
2. Escolha o colaborador.
3. Defina:
   - salario base;
   - subsidios;
   - descontos;
   - emprestimos;
   - horas extra, se aplicavel.
4. Grave.

[Inserir captura: HRM > Set Salary > View Employee Salary]

### 9.8 Correr payroll

1. Aceda a `HRM` -> `Payslip` -> `Payroll`.
2. Clique em `Create`.
3. Defina periodo, mes ou colaborador conforme o fluxo do cliente.
4. Grave o payroll.
5. Clique em `Run` para processar.
6. Abra o payroll e reveja os recibos.
7. Imprima payslips quando necessario.

Observacoes importantes:

- payroll pode falhar se as tabelas legais estiverem incoerentes;
- salarios abaixo do minimo legal devem ser resolvidos antes do processamento;
- as presencas devem estar completas.

[Inserir captura: HRM > Payroll > Run]

### 9.9 Pagar salario

1. No payroll processado, abra a linha do colaborador.
2. Use a accao de pagamento.
3. Confirme conta bancaria e referencia, quando exigido.
4. Grave.

Impactos:

- a payslip passa a paga;
- tesouraria e conta bancaria sao afectadas;
- contabilidade pode receber journal automatico.

### 9.10 Documentos, acknowledgments e compliance interna

### Documentos

1. Aceda a `HRM` -> `Documents`.
2. Crie o documento.
3. Actualize o estado quando exigido.

### Acknowledgments

1. Aceda a `HRM` -> `Acknowledgments`.
2. Registe a ciencia do colaborador.
3. Evite duplicacao do mesmo acknowledgment para o mesmo documento.

### 9.11 Promocoes, advertencias, reclamacoes e desligamentos

O sistema suporta:

- `Promotions`
- `Warnings`
- `Complaints`
- `Transfers`
- `Resignations`
- `Terminations`

Recomendacao operacional:

- usar sempre dados completos;
- actualizar o estado do processo;
- validar impacto em perfil probatorio e acerto final quando aplicavel.

### 9.12 Exemplo pratico de RH

### Exemplo A: onboarding completo

1. Criar filial, departamento e cargo.
2. Criar colaborador.
3. Definir salario.
4. Criar turno.
5. Registar presencas.
6. Gerar payroll do mes.

### Exemplo B: desligamento

1. Registar resignation ou termination.
2. Actualizar estado.
3. Rever acerto final.
4. Confirmar impacto no perfil probatorio e historico.

### 9.13 Erros comuns em RH e como resolver

| Erro | Causa comum | Solucao |
|---|---|---|
| Payroll nao corre | parametros legais, salario ou presencas incoerentes | validar tabelas IRPS/INSS, salarios e attendance |
| Colaborador nao aparece no payroll | sem cadastro completo ou sem salario definido | concluir cadastro e `Set Salary` |
| Saldo de ferias errado | pedidos pendentes ou faltas nao tratadas | rever `Leave Applications` e politica laboral |
| Botao de pagamento nao aparece | falta permissao | rever role do utilizador |
| Documento ou acknowledgment duplicado | processo repetido | pesquisar antes de criar novo registo |

### 9.14 Recomendacoes de teste para RH

Executar pelo menos:

1. Criacao de colaborador com dados completos.
2. Definicao de salario.
3. Registo de presencas de uma semana.
4. Criacao e aprovacao de um pedido de ferias.
5. Processamento de payroll.
6. Pagamento de pelo menos um recibo.
7. Emissao de um mapa ou exportacao de conformidade.
8. Registo de uma resignation ou termination.

[Inserir captura: evidencias finais do bloco RH]

## 10. Tesouraria

### 10.1 Ambito

Este bloco cobre:

- contas bancarias;
- movimentos bancarios;
- importacao de extractos;
- reconciliacao;
- transferencias bancarias;
- recebimentos e pagamentos;
- fecho de caixa;
- relatorios de tesouraria, cambial, GIFiM e moeda electronica.

### 10.2 Menus principais

- `Contabilidade Geral` -> `Banking`
- `Customer Payments`
- `Vendor Payments`
- `Reports`

[Inserir captura: Contabilidade Geral > Banking]

### 10.3 Contas bancarias

1. Aceda a `Bank Accounts`.
2. Crie a conta com banco, numero, moeda e filial.
3. Grave.

Observacao:

- sem conta bancaria, pagamentos, recebimentos e transferencias ficam limitados.

### 10.4 Movimentos bancarios e importacao de extractos

### Importar CSV

1. Aceda a `Bank Transactions`.
2. Use `Template` se precisar do formato base.
3. Clique em `Import CSV`.
4. Associe a conta bancaria correcta.
5. Grave.

### Reconciliacao automatica

1. No mesmo ecra, use `Auto Reconcile`.
2. Reveja os movimentos encontrados.
3. Marque como reconciliado quando necessario.

Observacoes:

- a conta deve pertencer a empresa activa;
- extractos de outra empresa nao devem ser usados neste ambiente.

[Inserir captura: Banking > Bank Transactions]

### 10.5 Transferencias bancarias

1. Aceda a `Bank Transfers`.
2. Clique em `Create`.
3. Escolha conta origem e destino.
4. Informe valor, data e referencia.
5. Grave.
6. Use `Process` para concluir.

### 10.6 Recebimentos de clientes

1. Aceda a `Customer Payments`.
2. Crie o recebimento.
3. Associe cliente, banco e factura pendente.
4. Grave.
5. Aprove ou actualize o estado.

### 10.7 Pagamentos a fornecedores

1. Aceda a `Vendor Payments`.
2. Crie o pagamento.
3. Associe fornecedor, conta bancaria e factura pendente.
4. Grave.
5. Aprove ou actualize o estado.

Observacao:

- em pagamentos internacionais podem existir exigencias adicionais de referencia contratual, fatura, retencao e dossier cambial.

### 10.8 Fecho de caixa

1. Aceda a `Reports` -> `Cash Closing`.
2. Seleccione a data.
3. Revise os movimentos do dia.
4. Feche.
5. Reabra apenas com autorizacao.

Observacao:

- datas futuras nao devem ser usadas para fecho.

### 10.9 Relatorios de tesouraria e cambial

Validar quando o plano e as permissoes permitirem:

- Treasury Report
- Exchange Control Report
- GIFiM Compliance Report
- Electronic Money Compliance Report
- Currency Report

Aplicacoes praticas:

- controlo de recebimentos e pagamentos internacionais;
- repatriacao de receitas de exportacao;
- comunicacoes GIFiM;
- evidencias de moeda electronica e isencoes.

### 10.10 Exemplo pratico de tesouraria

### Exemplo A: recebimento de cliente e reconciliacao

1. Registar pagamento de cliente.
2. Importar extracto bancario.
3. Correr `Auto Reconcile`.
4. Confirmar movimento reconciliado.

### Exemplo B: pagamento a fornecedor

1. Postar factura de compra.
2. Criar pagamento ao fornecedor.
3. Aprovar.
4. Validar saldo do fornecedor e banco.

### Exemplo C: operacao cambial

1. Criar pagamento ou recebimento em moeda estrangeira.
2. Rever o relatorio cambial.
3. Completar repatriacao, comunicacao ou dossier quando aplicavel.

### 10.11 Erros comuns em tesouraria e como resolver

| Erro | Causa comum | Solucao |
|---|---|---|
| Conta bancaria nao aparece | conta nao cadastrada, filial errada ou sem permissao | validar cadastro e permissoes |
| Reconciliacao falha | extracto com conta errada ou sem pagamentos correspondentes | rever conta e referencias |
| Pagamento internacional bloqueado | falta evidencia documental | anexar referencias exigidas |
| Fecho de caixa indisponivel | falta permissao ou data invalida | rever role e data |
| Relatorio cambial vazio | sem operacoes elegiveis ou filtros errados | rever periodo e moeda |

### 10.12 Recomendacoes de teste para tesouraria

Executar pelo menos:

1. Criacao de duas contas bancarias.
2. Transferencia entre contas.
3. Registo de recebimento de cliente.
4. Registo de pagamento a fornecedor.
5. Importacao de extracto CSV.
6. Reconciliacao automatica.
7. Fecho de caixa.
8. Consulta do relatorio de tesouraria e relatorio cambial.

[Inserir captura: evidencias finais do bloco Tesouraria]

## 11. Plano de validacao integrado

Recomenda-se testar o sistema nesta ordem:

### Cenário 1: venda completa

1. Criar item.
2. Criar cliente.
3. Emitir e postar fatura de venda.
4. Registar recebimento.
5. Validar saldo do cliente.
6. Validar relatorio fiscal.

### Cenário 2: compra completa

1. Criar fornecedor.
2. Emitir e postar fatura de compra.
3. Registar pagamento.
4. Validar saldo do fornecedor.
5. Validar ageing e mapa fiscal.

### Cenário 3: payroll completo

1. Criar colaborador.
2. Definir salario.
3. Registar attendance.
4. Correr payroll.
5. Pagar recibo.
6. Validar reflexo em tesouraria e contabilidade.

### Cenário 4: POS

1. Criar venda POS.
2. Validar stock.
3. Validar order POS.
4. Validar fecho de caixa.

### Cenário 5: fiscal e exportacoes

1. Validar series documentais.
2. Validar perfil fiscal.
3. Exportar SAF-T.
4. Rever historico de exportacoes.

## 12. Checklist final para entrega ao cliente

Antes da entrega formal, confirmar:

- plano activo e modulos contratados correctos;
- roles e utilizadores criados;
- empresa parametrizada;
- impostos, series e dados fiscais validados;
- bancos, armazens, clientes e fornecedores cadastrados;
- pelo menos um ciclo de venda testado;
- pelo menos um ciclo de compra testado;
- pelo menos um payroll testado, se RH estiver incluido;
- relatorios principais revistos;
- capturas de ecra finais inseridas neste manual;
- erros criticos resolvidos.

## 13. Observacoes importantes para o cliente

- Nem todas as funcoes aparecem para todos os utilizadores.
- A ausencia de um menu pode significar falta de plano, modulo ou permissao.
- Documentos postados ou fiscalizados devem ser rectificados por fluxo correcto, nao por edicao directa.
- RH e fiscalidade local exigem dados consistentes antes de processamento.
- Tesouraria depende de contas bancarias, referencias e conciliacao correcta.

## 14. Suporte e manutencao do manual

Este manual deve ser actualizado sempre que houver:

- activacao de novos modulos;
- alteracao de plano de subscricao;
- mudancas fiscais ou laborais relevantes;
- mudancas de nomenclatura nos menus;
- novos fluxos aprovados para clientes.

## 15. Anexo de evidencias

Preencher durante a validacao:

| Bloco | Teste | Resultado | Evidencia |
|---|---|---|---|
| Facturacao | Venda de produto | Pendente | [Inserir captura] |
| Facturacao | Venda de servico | Pendente | [Inserir captura] |
| Facturacao | Compra e devolucao | Pendente | [Inserir captura] |
| Contabilidade | Customer balance e vendor balance | Pendente | [Inserir captura] |
| Contabilidade | SAF-T e relatorios fiscais | Pendente | [Inserir captura] |
| RH | Criacao de colaborador e salario | Pendente | [Inserir captura] |
| RH | Payroll e pagamento | Pendente | [Inserir captura] |
| Tesouraria | Reconciliacao bancaria | Pendente | [Inserir captura] |
| Tesouraria | Fecho de caixa | Pendente | [Inserir captura] |
