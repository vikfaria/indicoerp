# Manual de Utilizador — Faturação de Vendas

Data: 01/06/2026  
Âmbito: inserir produtos, inserir serviços e vender no sistema (fatura de venda).

## 1) Pré-requisitos

Antes de iniciar, confirme:

- Utilizador com permissões de faturação e cadastro (`create-sales-invoices`, `post-sales-invoices`, `manage-product-service-item`, `manage-customers`/`create-customers`).
- Módulos activos:
  - `Product & Service`
  - `Account` (para clientes/recebimentos)
  - `Sales Invoices`
- Pelo menos 1 armazém activo (para vendas de produto).

Atalhos úteis:

- Itens (produtos/serviços): `/product-service/items`
- Clientes: `/account/customers`
- Faturas de venda: `/sales-invoices`
- Recebimentos de cliente: `/account/customer-payments`

## 2) Como inserir um Produto

1. Aceda a `Product & Service` -> `Items`.
2. Clique em `Create`.
3. No separador **Details**:
   - `Item Type`: escolha `Product`.
   - Preencha `Name`, `SKU`, `Tax`, `Category`.
4. Clique em `Next` para **Pricing**:
   - Preencha `Sale Price`, `Purchase Price`, `Unit`, `Quantity`.
5. Clique em `Next` para **Media**:
   - (Opcional) carregue imagem principal e galeria.
6. Clique em `Next` para **Warehouse**:
   - Selecione `Warehouse` (armazém).
7. Clique em `Create`.

Resultado esperado:

- O produto fica disponível para venda em faturas do tipo `Product Wise`.
- Só aparece na venda quando existir stock no armazém selecionado.

## 3) Como inserir um Serviço

1. Aceda a `Product & Service` -> `Items`.
2. Clique em `Create`.
3. Em **Details**:
   - `Item Type`: escolha `Service`.
   - Preencha `Name`, `SKU`, `Tax`, `Category`.
4. Em **Pricing**:
   - Preencha `Sale Price`, `Purchase Price`, `Unit`.
5. Conclua com `Create`.

Notas:

- Serviço não precisa de stock nem armazém.
- Fica disponível para faturas do tipo `Service Wise`.

## 4) Como inserir Cliente (obrigatório para vender)

1. Aceda a `Contabilidade Geral` -> `Customers`.
2. Clique em `Create Customer`.
3. Preencha no mínimo:
   - `Company Name`
   - `Contact Person`
   - `Email`
   - `Mobile Number`
   - Morada de faturação (`Billing Name`, `Billing Address`, `City`, `State`, `Country`, `Zip Code`)
4. Preencha `Tax Number (NUIT)` sempre que aplicável.
5. Grave em `Create`.

## 5) Como vender Produto (emitir fatura de venda)

1. Aceda a `Sales Invoices`.
2. Clique em `Create Sales Invoice`.
3. Em `Sales Invoice Details`, escolha `Product Wise`.
4. Preencha:
   - `Invoice Date`
   - `Due Date`
   - `Customer`
   - `Warehouse` (obrigatório para produto)
   - `Payment Terms` (opcional)
5. Na secção `Sales Invoice Items`:
   - Clique `+ Add Item`.
   - Escolha o produto.
   - Informe `Qty`.
   - Revise `Unit Price`, `Discount %`, `Tax`.
6. Confirme os totais no resumo (`Subtotal`, `Tax`, `Total`).
7. Clique em `Create`.

Após criar:

1. A fatura entra em estado `draft`.
2. No ecrã de listagem, clique na ação de **post** para finalizar (publicar) a fatura.
3. Use `Download PDF` para imprimir/exportar.

## 6) Como vender Serviço (emitir fatura de serviço)

1. Aceda a `Sales Invoices` -> `Create Sales Invoice`.
2. Em `Sales Invoice Details`, escolha `Service Wise`.
3. Preencha `Invoice Date`, `Due Date`, `Customer`.
   - `Warehouse` não é exigido para serviço.
4. Em `Sales Invoice Items`:
   - `+ Add Item`
   - Selecione o serviço
   - Ajuste preço, imposto e desconto
5. Clique em `Create`.
6. Faça `post` da fatura para finalizar.

## 7) Como registrar recebimento da venda (opcional, pós-fatura)

1. Aceda a `Contabilidade Geral` -> `Customer Payments`.
2. Clique em `Create Customer Payment`.
3. Preencha:
   - `Payment Date`
   - `Customer`
   - `Bank Account`
   - `Payment Method`
   - `Reference Number` (opcional)
4. Na lista de faturas pendentes, clique `Add` na fatura a liquidar.
5. Grave o recebimento.

Resultado esperado:

- O saldo (`Balance`) da fatura reduz automaticamente.

## 8) Regras importantes do sistema (evita erro)

- `Due Date` não pode ser anterior a `Invoice Date`.
- Fatura `posted` não pode ser editada nem apagada.
- Apenas faturas `draft` podem ser editadas/apagadas.
- Em venda de produto, sem stock no armazém o item não aparece para seleção.
- Em operações isentas/não sujeitas, preencha motivo de isenção quando solicitado.

## 9) Erros comuns e solução rápida

- **Produto não aparece na fatura**: confirme stock > 0 no armazém selecionado e item activo.
- **Serviço não aparece**: confirme `Item Type = Service` e item activo.
- **Cliente não aparece**: confirme cadastro em `Customers`.
- **Sem botão de criar/postar**: falta permissão no perfil do utilizador.

## 10) Checklist de operação diária

1. Cadastrar/validar cliente.
2. Cadastrar item (produto ou serviço).
3. Emitir fatura (`Create Sales Invoice`).
4. Publicar fatura (`post`).
5. Exportar PDF.
6. Registrar recebimento (quando pago).

