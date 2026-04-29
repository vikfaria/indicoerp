# Análise Aprofundada de Adequação ao Mercado Moçambicano

Data da análise: 24 de Abril de 2026  
Projecto analisado: `sysgest`  
Objectivo: avaliar a solução nas dimensões técnica, funcional, legal, fiscal, operacional e comercial, com foco na sua adaptação e comercialização em Moçambique.

## 1. Conclusão Executiva

O sistema tem **boa amplitude funcional** e uma base tecnológica moderna para um ERP SaaS de PMEs. A solução cobre contabilidade, vendas, compras, armazéns, POS, CRM, RH, projectos, helpdesk e facturação SaaS. A arquitectura modular também favorece uma localização faseada.

Apesar disso, **o sistema não está pronto para comercialização imediata em Moçambique no estado actual**. O principal problema não é a falta de módulos, mas sim a ausência de **localização fiscal e laboral**, aliada a **lacunas de segurança multiempresa**, **baixa robustez de testes/build** e **documentos comerciais ainda genéricos demais para exigências fiscais moçambicanas**.

Classificação resumida:

- Potencial comercial: **alto**
- Maturidade funcional base: **média/alta**
- Prontidão técnica para escala: **média**
- Prontidão legal/fiscal para Moçambique: **baixa**
- Prontidão para lançamento comercial local: **baixa**

Bloqueadores principais antes do go-live:

- Localização fiscal de documentos, séries, NUIT, IVA, notas de crédito/débito e obrigações declarativas
- Localização de payroll para IRPS, INSS e regras laborais moçambicanas
- Correcção de falhas de isolamento/autorização entre empresas
- Estabilização técnica mínima: testes, build, dependências e trilho de auditoria
- Integrações locais: pagamentos, onboarding e operação orientada ao contexto moçambicano

## 2. Metodologia e Evidência

Esta análise combinou:

- Leitura estrutural do código e das migrations
- Inspecção dos módulos funcionais existentes
- Verificação técnica local de build, testes e dependências
- Cruzamento com fontes oficiais moçambicanas consultadas em 24 de Abril de 2026

Principais evidências de código:

- Stack e dependências: [composer.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/composer.json:7>) e [package.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/package.json:1>)
- Carregamento modular dinâmico: [PackageServiceProvider.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Providers/PackageServiceProvider.php:9>)
- Middleware de instalação global: [bootstrap/app.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/bootstrap/app.php:14>) e [CheckInstallation.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Middleware/CheckInstallation.php:11>)
- Validação/autorização de vendas: [StoreSalesInvoiceRequest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Requests/StoreSalesInvoiceRequest.php:14>) e [SalesInvoiceController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Controllers/SalesInvoiceController.php:255>)
- Validação/autorização do POS: [StorePosRequest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Http/Requests/StorePosRequest.php:15>) e [PosController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Http/Controllers/PosController.php:167>)
- Estrutura actual de documentos: [create_sales_invoices_table.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/database/migrations/2025_09_26_102340_create_sales_invoices_table.php:16>) e [create_pos_table.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Database/Migrations/2025_09_30_000001_create_pos_table.php:12>)
- Impressão de documentos: [Sales/Print.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/resources/js/pages/Sales/Print.tsx:73>) e [PosOrder/Print.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Resources/js/Pages/PosOrder/Print.tsx:97>)
- Configuração de empresa: [company-settings.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/resources/js/pages/settings/components/company-settings.tsx:12>)
- Lançamentos contabilísticos automáticos: [JournalService.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Account/src/Services/JournalService.php:17>)
- Idioma e moeda: [language.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/resources/lang/language.json:59>) e [default_currency.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/config/default_currency.php:113>)
- Deprecações observadas em PHP: [database.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/config/database.php:57>)
- Exemplo de teste afectado pelo redireccionamento para instalação: [AuthenticationTest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/Auth/AuthenticationTest.php:13>)

## 3. Levantamento Técnico

### 3.1 Arquitectura e Stack

O sistema corre sobre Laravel 12 e PHP 8.2, com frontend Inertia + React + TypeScript + Vite. Há um núcleo principal e um conjunto vasto de módulos em `packages/workdo/*`, carregados dinamicamente no arranque da aplicação. Isto é positivo para localização por domínio.

Pontos fortes técnicos:

- Arquitectura modular, favorável a customizações por pacote
- Cobertura funcional ampla num único produto
- Existência de português e português do Brasil
- Suporte nativo para moeda `MZN`
- Motor contabilístico com lançamentos automáticos de débito/crédito

Pontos de atenção:

- O isolamento entre empresas parece depender sobretudo de `created_by` e filtros aplicacionais, e não de uma camada multi-tenant forte
- A solução é extensa, mas sem sinais equivalentes de cobertura de testes e controlos de segurança ao mesmo nível da complexidade funcional

### 3.2 Prontidão Técnica Actual

Na verificação local, a solução mostrou fragilidades objectivas:

- `php artisan test`: 21 testes falhados, 1 passado e 3 deprecated
- `npm run build`: falhou por incompatibilidade entre a versão do `esbuild` no host e o binário instalado
- `npm audit --omit=dev --audit-level=high`: 23 vulnerabilidades reportadas
- `composer audit`: não foi executado porque `composer` não estava disponível no ambiente
- O repositório contém apenas 10 ficheiros de teste para uma base com centenas de controllers, models e migrations

Dependências que merecem revisão imediata:

- `axios`: [package.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/package.json:17>)
- `html2pdf.js`: [package.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/package.json:69>)
- `x-data-spreadsheet`: [package.json](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/package.json:91>)

Risco complementar:

- A configuração DB usa `PDO::MYSQL_ATTR_SSL_CA`, o que gerou deprecações com PHP mais recente: [database.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/config/database.php:57>)

### 3.3 Riscos Técnicos Críticos

#### A. Falhas de autorização e isolamento entre empresas

Em vendas:

- A validação aceita `customer_id` e `warehouse_id` apenas com `exists`, sem confirmar pertença à empresa do utilizador: [StoreSalesInvoiceRequest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Requests/StoreSalesInvoiceRequest.php:19>)
- Os métodos `destroy`, `post` e `print` não validam explicitamente `created_by == creatorId()`: [SalesInvoiceController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Controllers/SalesInvoiceController.php:255>), [SalesInvoiceController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Controllers/SalesInvoiceController.php:324>), [SalesInvoiceController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Controllers/SalesInvoiceController.php:423>)

No POS:

- O pedido valida apenas `exists` para cliente, armazém e produto: [StorePosRequest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Http/Requests/StorePosRequest.php:18>)
- O `store` e o `print` não reforçam a posse do registo pela empresa corrente: [PosController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Http/Controllers/PosController.php:167>), [PosController.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Http/Controllers/PosController.php:305>)

Impacto:

- Risco real de acesso cruzado entre empresas
- Possibilidade de associação indevida de clientes, armazéns ou documentos
- Exposição jurídica e reputacional séria para um ERP SaaS

#### B. Bootstrap de instalação interfere com operação e testes

O middleware de instalação é injectado globalmente no stack web: [bootstrap/app.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/bootstrap/app.php:15>).  
Ele redirecciona toda a aplicação para `/install` quando `storage/installed` não existe: [CheckInstallation.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/app/Http/Middleware/CheckInstallation.php:13>).

Isto ajuda a explicar parte das falhas de testes, incluindo o teste de login que espera `200` em `/login`: [AuthenticationTest.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/tests/Feature/Auth/AuthenticationTest.php:15>)

#### C. Ausência de trilho de auditoria transversal

Há sinais de logs em módulos específicos, mas não foi encontrada uma camada transversal, imutável e consistente para:

- emissão e anulação de documentos fiscais
- alterações salariais e payroll
- alterações de dados mestres fiscais
- publicação/cancelamento de documentos contabilísticos

Para conformidade e defesa probatória, isto é insuficiente.

## 4. Levantamento Funcional

### 4.1 Cobertura Funcional Existente

A solução já oferece uma base comercial relevante:

- Contabilidade e plano de contas
- Clientes, fornecedores, pagamentos, receitas e despesas
- Vendas, compras, devoluções e propostas
- Armazéns e transferências
- POS
- CRM
- RH com colaboradores, assiduidade, licenças, turnos, descontos, adiantamentos, horas extra e payroll
- Projectos e tarefas
- Helpdesk, calendário, media e mensageria
- Gestão SaaS de planos, ordens, cupões e gateways

Isto coloca o produto numa posição forte para PMEs moçambicanas de comércio, serviços e distribuição, desde que a camada regulatória seja corrigida.

### 4.2 Lacunas Funcionais Face a Moçambique

#### A. Modelo fiscal de documentos ainda genérico

As tabelas de vendas e POS guardam apenas dados base de operação, sem campos tipicamente necessários para conformidade fiscal local:

- tipo documental
- série
- NUIT do emitente e do cliente como snapshot
- motivo de isenção/não liquidação
- referência do documento rectificado
- método de pagamento
- câmbio
- retenções
- estado de submissão/integração fiscal
- controlo/hash/assinatura documental

Evidência:

- [create_sales_invoices_table.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/database/migrations/2025_09_26_102340_create_sales_invoices_table.php:18>)
- [create_pos_table.php](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Database/Migrations/2025_09_30_000001_create_pos_table.php:14>)

#### B. Impressão documental ainda não está localizada

Os layouts actuais mostram nome, morada, contacto e registo, mas não impõem um padrão documental moçambicano claro, nem exibem de forma robusta os identificadores fiscais esperados.

Evidência:

- Factura de venda: [Sales/Print.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/resources/js/pages/Sales/Print.tsx:73>)
- Talão/POS: [PosOrder/Print.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/packages/workdo/Pos/src/Resources/js/Pages/PosOrder/Print.tsx:97>)

#### C. Configuração de empresa ainda não é moçambicana

O formulário de empresa é genérico, com `registration_number`, `tax_type` e `vat_number`, mas sem uma modelação explícita de `NUIT`, província, distrito, posto administrativo, validações locais e perfis tributários adequados ao país.

Evidência:

- [company-settings.tsx](</Users/victorfaria/Antigravity Local Agent/codecanyon-NEL7inl4-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/sysgest/resources/js/pages/settings/components/company-settings.tsx:21>)

#### D. Snapshots históricos insuficientes

Os documentos dependem fortemente de relações vivas com cliente e settings actuais. Isso cria risco de alteração retroactiva do conteúdo impresso caso o cadastro seja alterado depois da emissão.

Em contexto fiscal, o documento precisa preservar o estado histórico do emitente, adquirente e regras tributárias vigentes no momento da emissão.

#### E. Payroll ainda não está localizado para Moçambique

O módulo de RH/payroll parece estruturalmente competente, mas não foram encontrados mecanismos nativos para:

- tabelas de retenção IRPS localizadas
- cálculo patronal e laboral do INSS
- salários mínimos sectoriais de Moçambique
- parametrização de regime fiscal/laboral por sector
- obrigações anuais e mensais de reporte associadas
- controlos específicos de contratação de estrangeiros

#### F. Pagamentos locais ausentes

Na pesquisa do código não foram encontradas referências a `M-Pesa`, `e-Mola`, `mKesh` ou integrações equivalentes.  
Para o mercado moçambicano, isto reduz fortemente a competitividade comercial, sobretudo em PMEs e operações de cobrança rápida.

## 5. Enquadramento Legal, Fiscal e Operacional de Moçambique

### 5.1 IVA

O enquadramento do IVA exige atenção especial porque as fontes oficiais online não estão totalmente uniformes:

- O **MEF**, em documento oficial consultado em Abril de 2026, reafirma a medida do PAE de **redução da taxa do IVA de 17% para 16%**, no âmbito da **Lei 22/2022, de 28 de Dezembro**
- Algumas páginas legadas da **AT** ainda exibem conteúdo histórico a referir **17%**

Conclusão prudente:

- Para parametrização de produto em 24 de Abril de 2026, a taxa geral deve ser tratada como **16%**, mas com **validação jurídica/fiscal final antes do lançamento**
- O motor fiscal não deve hardcodar uma única taxa histórica; deve suportar vigência por data e alteração legal

Outras exigências relevantes:

- A factura/documento equivalente deve ser emitida **até ao quinto dia útil** seguinte ao momento em que o imposto é devido
- IVA e ISPC são submetidos via **Portal do Contribuinte / eDeclaração**
- Os sujeitos passivos do IVA devem conservar livros, registos e documentos de suporte por **10 anos**

### 5.2 NUIT

O NUIT é identificador fiscal estruturante. A sua presença não deve ser apenas um campo opcional de cadastro; deve integrar:

- entidade emitente
- clientes
- fornecedores
- documentos emitidos
- formulários de onboarding e validação

### 5.3 ISPC

Moçambique mantém um enquadramento simplificado para pequenos contribuintes. Isso é comercialmente relevante porque uma parte significativa das PMEs locais não opera com a mesma complexidade fiscal de empresas maiores.

Implicação de produto:

- O ERP deve suportar pelo menos dois perfis fiscais: regime normal e regime simplificado

### 5.4 IRPC

Pontos relevantes para adaptação:

- taxa geral de **32%**, salvo regimes e incentivos específicos
- declaração periódica anual até ao **último dia útil de Maio**
- documentação fiscal conservada por **10 anos**

### 5.5 IRPS

O payroll deve suportar, no mínimo:

- retenção na fonte mensal
- entrega do imposto até ao dia **20 do mês seguinte**
- comunicação anual de rendimentos ao trabalhador até **20 de Janeiro**
- suporte para declarações e mapas anuais exigidos pela AT

### 5.6 INSS

Com base em páginas oficiais do INSS consultadas na mesma data:

- contribuição patronal: **4%**
- desconto do trabalhador: **3%**
- total canalizado ao INSS: **7%**

Além disso, o contexto operacional já pressupõe uso de plataformas electrónicas como **M-Contribuição**, pelo que payroll e administração de pessoal devem prever reconciliação e reporte digital.

### 5.7 Lei do Trabalho

Da Lei do Trabalho em vigor, destacam-se para o sistema:

- período normal de trabalho de **48 horas por semana** e **8 horas por dia**
- necessidade de registo/controlo de trabalho extraordinário
- licença por maternidade de **90 dias**
- licença por paternidade de **7 dias**
- regras específicas para contratação de trabalhador estrangeiro

Implicação de produto:

- o módulo de RH não pode limitar-se a cadastro, assiduidade e recibo; precisa de regras parametrizadas e provas documentais

### 5.8 Transacções electrónicas e protecção de dados

Para venda SaaS e operação digital em Moçambique, o produto deve alinhar-se com:

- Lei n.º **3/2017**, Lei de Transacções Electrónicas
- enquadramento constitucional de privacidade e tratamento de dados
- evolução do regime específico de protecção de dados pessoais

Ponto crítico de actualidade:

- Em **5 de Março de 2026**, o INTIC publicou que a **Proposta de Lei de Protecção de Dados Pessoais** foi aprovada em Conselho de Ministros e seguiria para a Assembleia da República

Logo:

- Não assumi, com segurança suficiente, que já exista uma lei geral promulgada e em vigor à data desta análise
- A solução deve ser preparada para conformidade progressiva, com capacidade de adaptação rápida assim que o diploma final entrar em vigor

## 6. Avaliação Comercial

### 6.1 Onde o produto tem boa viabilidade

Depois de adaptado, o produto tem potencial para:

- PMEs de comércio grossista e retalhista
- empresas de serviços com facturação recorrente
- distribuidores com armazém
- negócios com necessidade combinada de vendas, stock, CRM e RH
- operações multi-filial de pequena e média dimensão

### 6.2 Onde o produto ainda não deve ser vendido

Sem adaptação prévia, não recomendaria comercialização para:

- empresas com forte exigência fiscal/auditável
- clientes que precisem de payroll totalmente conforme desde o primeiro dia
- operações com grande volume de POS fiscalmente sensível
- empresas que exijam integração imediata com fluxos locais de pagamento e reporte

### 6.3 Posicionamento comercial recomendado

Posicionamento mais realista após localização:

- ERP modular para PMEs moçambicanas
- foco inicial em comércio, serviços e distribuição
- venda acompanhada de implementação e parametrização, não apenas licença self-service
- apoio de contabilista/parceiro local nas primeiras implantações

## 7. Recomendação de Roadmap

### Fase 0. Correcções bloqueadoras

Prazo estimado: 4 a 8 semanas

- Fechar todas as lacunas de autorização/tenant isolation em vendas, POS e áreas equivalentes
- Separar o fluxo de instalação do ambiente de testes
- Corrigir o build frontend
- Actualizar dependências críticas e rever vulnerabilidades
- Criar um audit log mínimo para finanças, RH e documentos

### Fase 1. Localização fiscal núcleo

Prazo estimado: 8 a 12 semanas

- Introduzir NUIT explícito e validado em empresas, clientes e fornecedores
- Criar séries documentais por empresa/estabelecimento
- Rever modelo de factura, recibo, nota de crédito e nota de débito
- Implementar snapshots fiscais históricos no documento
- Parametrizar IVA por vigência legal
- Adaptar templates impressos ao padrão moçambicano
- Produzir exportações e relatórios adequados a obrigações locais

### Fase 2. Localização RH/payroll

Prazo estimado: 6 a 10 semanas

- Implementar tabelas IRPS e motor de retenção
- Implementar INSS 4%/3%
- Suportar salários mínimos sectoriais e actualização anual
- Formalizar eventos laborais relevantes: maternidade, paternidade, horas extra, férias e regimes especiais
- Preparar mapas e saídas operacionais para AT/INSS

### Fase 3. Integrações e preparação comercial

Prazo estimado: 4 a 8 semanas

- Integrar pagamentos locais prioritários
- Rever onboarding, contratos, termos e política de privacidade
- Definir backup, retenção documental e controlo de acessos
- Criar catálogo comercial, SLA e pacote de suporte

### Fase 4. Validação local e piloto

Prazo estimado: 2 a 4 semanas

- Revisão formal por contabilista moçambicano
- Revisão por advogado local
- Piloto com 2 a 5 empresas reais
- Ajustes finais antes de lançamento comercial

## 8. Decisão Recomendada

A decisão recomendada é:

- **avançar com o projecto**
- **não lançar comercialmente ainda**
- **tratar a localização moçambicana como uma frente de produto formal**

Em linguagem directa: a solução é **comercialmente promissora**, mas **ainda não é legalmente e operacionalmente suficiente para o mercado moçambicano** sem um ciclo sério de adaptação.

## 9. Fontes Oficiais Consultadas

Fontes governamentais e institucionais usadas nesta análise:

- MEF, Cenário Fiscal de Médio Prazo 2025-2027: https://mef.gov.mz/index.php/publicacoes/politicas/cenario-fiscal-de-medio-prazo-cfmp/2182-cenario-fiscal-de-medio-prazo-2025-2027/file
- AT, eDeclaração / Portal do Contribuinte: https://edeclaracao.at.gov.mz/
- AT, FAQ NUIT: https://www.at.gov.mz/por/Comercio-Internacional/FAQ-s/NUIT
- AT, IRPS FAQ: https://www.at.gov.mz/por/Perguntas-Frequentes2/IRPS
- AT, IRPC: https://www.at.gov.mz/por/Processos-Fiscais/Imposto-sobre-o-Rendimento-de-Pessoas-colectivas-IRPC
- AT, Formulários fiscais: https://www.at.gov.mz/por/Declaracoes-Fiscais/Formularios
- INSS, taxa contributiva do contribuinte: https://www.inss.gov.mz/taxa-contributiva-contribuinte/
- INSS, taxa contributiva do trabalhador por conta de outrem: https://www.inss.gov.mz/taxa-contributiva-tco/
- INSS, M-Contribuição: https://www.inss.gov.mz/m-contribuicao/
- INSS, Lei do Trabalho (Lei n.º 13/2023): https://www.inss.gov.mz/?download_id=20320&sdm_process_download=1
- INSS, Tabela de salários mínimos em vigor desde 1 de Julho de 2025: https://www.inss.gov.mz/wp-content/uploads/2025/10/Tabela-de-salarios-minimos-em-vigor-desde-1-de-Julho-de-2025.pdf
- INTIC, Lei de Transacções Electrónicas: https://intic.gov.mz/lei-de-transaccoes-electronicas/
- Portal do Governo, Lei n.º 03/2017: https://www.portaldogoverno.gov.mz/por/content/download/7051/51882/version/2/file/LEI_DE_TRANSACCOES_ELECTRONICAS.pdf
- INTIC, consulta pública da proposta de lei de protecção de dados: https://intic.gov.mz/governo-pretende-garantir-a-proteccao-de-dados-pessoais-em-ambientes-digitais/
- INTIC, proposta segue para debate na Assembleia da República: https://intic.gov.mz/proposta-de-lei-de-proteccao-de-dados-pessoais-segue-para-debate-na-assembleia-da-republica/

## 10. Nota Final de Prudência

Os pontos legais e fiscais acima foram validados com fontes oficiais públicas consultadas em **24 de Abril de 2026**.  
Ainda assim, antes de parametrização definitiva e entrada no mercado, recomendo uma **validação formal com contabilista e advogado moçambicanos**, sobretudo em:

- taxa e isenções efectivamente vigentes de IVA por data
- formato documental admissível no sector/segmento-alvo
- obrigações declarativas mensais e anuais que o sistema deverá emitir
- evolução do diploma de protecção de dados pessoais
