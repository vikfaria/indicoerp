<?php

namespace App\Services\AssistantActivation;

class ContextualCtaResolverService
{
    private const DEFAULT_TONE = 'secondary';

    /**
     * @var array<string, array<string, string|null>>
     */
    private const CONFIG_TARGETS = [
        'company_profile' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar empresa',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Abra as definições da empresa para completar os dados base.',
            'tone' => 'default',
        ],
        'fiscal_profile' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar perfil fiscal',
            'route' => 'sce.fiscal.index',
            'anchor' => null,
            'message' => 'Complete o perfil fiscal antes de emitir documentos.',
            'tone' => 'default',
        ],
        'document_series' => [
            'action' => 'complete_configuration',
            'label' => 'Criar séries documentais',
            'route' => 'sce.fiscal.series',
            'anchor' => null,
            'message' => 'Crie e active as séries documentais necessárias.',
            'tone' => 'default',
        ],
        'accounting_period' => [
            'action' => 'complete_configuration',
            'label' => 'Abrir período contabilístico',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Abra o período contabilístico para lançar documentos.',
            'tone' => 'default',
        ],
        'tax_profile' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar mapeamento fiscal',
            'route' => 'account.mozambique-tax-account-mappings.index',
            'anchor' => null,
            'message' => 'Abra Sistema > Configurações > Mapeamento Fiscal e associe as contas fiscais em falta.',
            'tone' => 'default',
        ],
        'customer_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar clientes',
            'route' => 'account.customers.index',
            'anchor' => null,
            'message' => 'Crie os clientes necessários para facturação.',
            'tone' => 'default',
        ],
        'product_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar produtos',
            'route' => 'product-service.items.create',
            'anchor' => null,
            'message' => 'Registe os produtos ou serviços que vai vender.',
            'tone' => 'default',
        ],
        'chart_of_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Rever plano de contas',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Ajuste o plano de contas antes de contabilizar movimentos.',
            'tone' => 'default',
        ],
        'opening_balances' => [
            'action' => 'complete_configuration',
            'label' => 'Introduzir saldos iniciais',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Registe os saldos iniciais no arranque da empresa.',
            'tone' => 'default',
        ],
        'closing_rules' => [
            'action' => 'complete_configuration',
            'label' => 'Rever regras de fecho',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Ajuste as regras de fecho antes de validar o mês.',
            'tone' => 'default',
        ],
        'hr_company_profile' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar empresa para RH',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Complete os dados da empresa usados pelo módulo de RH.',
            'tone' => 'default',
        ],
        'employee_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar colaboradores',
            'route' => 'hrm.employees.create',
            'anchor' => null,
            'message' => 'Registe os colaboradores que vão processar salários e ausências.',
            'tone' => 'default',
        ],
        'contract_templates' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar modelos de contrato',
            'route' => 'contract-types.index',
            'anchor' => null,
            'message' => 'Prepare os modelos de contrato usados no recrutamento e RH.',
            'tone' => 'default',
        ],
        'payroll_calendar' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar calendário salarial',
            'route' => 'hrm.payrolls.index',
            'anchor' => null,
            'message' => 'Defina o calendário salarial antes de correr a folha.',
            'tone' => 'default',
        ],
        'payroll_contributions' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar contribuições da folha',
            'route' => 'hrm.mozambique-payroll-compliance.index',
            'anchor' => null,
            'message' => 'Configure INSS e IRPS antes de executar a folha salarial.',
            'tone' => 'default',
        ],
        'leave_policy' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar política de faltas',
            'route' => 'hrm.leave-types.index',
            'anchor' => null,
            'message' => 'Revise as políticas de férias, faltas e ausências.',
            'tone' => 'default',
        ],
        'bank_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Criar contas bancárias',
            'route' => 'account.bank-accounts.index',
            'anchor' => null,
            'message' => 'Registe as contas bancárias e de caixa usadas na tesouraria.',
            'tone' => 'default',
        ],
        'cash_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar caixa',
            'route' => 'account.bank-accounts.index',
            'anchor' => null,
            'message' => 'Configure a conta de caixa usada nos movimentos de tesouraria.',
            'tone' => 'default',
        ],
        'payment_methods' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar métodos de pagamento',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Defina os métodos de pagamento aceites pela empresa.',
            'tone' => 'default',
        ],
        'reconciliation_rules' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Prepare as regras de reconciliação bancária e de caixa.',
            'tone' => 'default',
        ],
        'warehouses' => [
            'action' => 'complete_configuration',
            'label' => 'Criar armazém',
            'route' => 'warehouses.index',
            'anchor' => null,
            'message' => 'Crie o primeiro armazém para suportar stock e POS.',
            'tone' => 'default',
        ],
        'initial_stock' => [
            'action' => 'complete_configuration',
            'label' => 'Carregar stock inicial',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Registe o stock inicial para gerar as layers FIFO.',
            'tone' => 'default',
        ],
        'fifo_layers' => [
            'action' => 'complete_configuration',
            'label' => 'Validar FIFO',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Confirme as layers FIFO do stock carregado.',
            'tone' => 'default',
        ],
        'pos_registers' => [
            'action' => 'complete_configuration',
            'label' => 'Validar POS',
            'route' => 'pos.create',
            'anchor' => null,
            'message' => 'Abra o POS e emita uma venda de teste para validar o registo.',
            'tone' => 'default',
        ],
        'initial_payments' => [
            'action' => 'complete_configuration',
            'label' => 'Registar pagamentos iniciais',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Registe os primeiros pagamentos para validar a tesouraria.',
            'tone' => 'default',
        ],
        'first_reconciliation' => [
            'action' => 'complete_configuration',
            'label' => 'Executar primeira reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Execute a primeira reconciliação bancária.',
            'tone' => 'default',
        ],
        'payment_test' => [
            'action' => 'complete_configuration',
            'label' => 'Validar fluxo de pagamento',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Valide o fluxo de pagamentos de ponta a ponta.',
            'tone' => 'default',
        ],
        'create_warehouse' => [
            'action' => 'complete_configuration',
            'label' => 'Criar armazém',
            'route' => 'warehouses.index',
            'anchor' => null,
            'message' => 'Crie o primeiro armazém para suportar stock e POS.',
            'tone' => 'default',
        ],
        'load_initial_stock' => [
            'action' => 'complete_configuration',
            'label' => 'Carregar stock inicial',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Registe o stock inicial para gerar as layers FIFO.',
            'tone' => 'default',
        ],
        'verify_fifo_layers' => [
            'action' => 'complete_configuration',
            'label' => 'Validar FIFO',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Confirme as layers FIFO do stock carregado.',
            'tone' => 'default',
        ],
        'run_pos_test' => [
            'action' => 'complete_configuration',
            'label' => 'Emitir venda POS de teste',
            'route' => 'pos.create',
            'anchor' => null,
            'message' => 'Abra o POS e emita uma venda de validação.',
            'tone' => 'default',
        ],
    ];

    /**
     * @var array<string, array<string, string|null>>
     */
    private const STEP_TARGETS = [
        'billing.configure_fiscal_profile' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar perfil fiscal',
            'route' => 'sce.fiscal.index',
            'anchor' => null,
            'message' => 'Complete o perfil fiscal antes de emitir documentos.',
            'tone' => 'default',
        ],
        'billing.configure_document_series' => [
            'action' => 'complete_configuration',
            'label' => 'Criar séries documentais',
            'route' => 'sce.fiscal.series',
            'anchor' => null,
            'message' => 'Crie e active as séries documentais necessárias.',
            'tone' => 'default',
        ],
        'billing.open_accounting_period' => [
            'action' => 'complete_configuration',
            'label' => 'Abrir período contabilístico',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Abra o período contabilístico para lançar documentos.',
            'tone' => 'default',
        ],
        'billing.create_customer_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar clientes',
            'route' => 'account.customers.index',
            'anchor' => null,
            'message' => 'Crie os clientes necessários para facturação.',
            'tone' => 'default',
        ],
        'billing.create_product_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar produtos',
            'route' => 'product-service.items.create',
            'anchor' => null,
            'message' => 'Registe os produtos ou serviços que vai vender.',
            'tone' => 'default',
        ],
        'billing.issue_test_invoice' => [
            'action' => 'complete_configuration',
            'label' => 'Emitir factura de teste',
            'route' => 'sales-invoices.create',
            'anchor' => null,
            'message' => 'Emita uma factura de teste para validar o fluxo fiscal.',
            'tone' => 'default',
        ],
        'accounting.configure_chart_of_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar plano de contas',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Ajuste o plano de contas antes de contabilizar movimentos.',
            'tone' => 'default',
        ],
        'accounting.record_opening_balances' => [
            'action' => 'complete_configuration',
            'label' => 'Introduzir saldos iniciais',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Registe os saldos iniciais no arranque da empresa.',
            'tone' => 'default',
        ],
        'accounting.open_period' => [
            'action' => 'complete_configuration',
            'label' => 'Abrir período contabilístico',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Abra o período contabilístico para lançar documentos.',
            'tone' => 'default',
        ],
        'accounting.configure_journal_templates' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar modelos de diário',
            'route' => 'sce.journals.index',
            'anchor' => null,
            'message' => 'Prepare os lançamentos recorrentes do diário.',
            'tone' => 'default',
        ],
        'accounting.review_trial_balance' => [
            'action' => 'complete_configuration',
            'label' => 'Rever balancete',
            'route' => 'account.reports.index',
            'anchor' => null,
            'message' => 'Reveja o balancete antes do fecho mensal.',
            'tone' => 'default',
        ],
        'accounting.validate_month_end_close' => [
            'action' => 'complete_configuration',
            'label' => 'Validar fecho mensal',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Valide o fecho mensal antes de o finalizar.',
            'tone' => 'default',
        ],
        'hr.create_employee_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar colaborador',
            'route' => 'hrm.employees.create',
            'anchor' => null,
            'message' => 'Registe o colaborador necessário para o processo salarial.',
            'tone' => 'default',
        ],
        'hr.configure_contract_templates' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar modelos de contrato',
            'route' => 'contract-types.index',
            'anchor' => null,
            'message' => 'Prepare os modelos de contrato usados no RH.',
            'tone' => 'default',
        ],
        'hr.configure_attendance_policy' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar calendário de trabalho',
            'route' => 'hrm.working-days.index',
            'anchor' => null,
            'message' => 'Defina os dias úteis, turnos e feriados aplicáveis.',
            'tone' => 'default',
        ],
        'hr.configure_leave_policy' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar política de faltas',
            'route' => 'hrm.leave-types.index',
            'anchor' => null,
            'message' => 'Revise as políticas de férias, faltas e ausências.',
            'tone' => 'default',
        ],
        'hr.configure_payroll_calendar' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar calendário salarial',
            'route' => 'hrm.payrolls.index',
            'anchor' => null,
            'message' => 'Defina o calendário salarial antes de correr a folha.',
            'tone' => 'default',
        ],
        'hr.run_payroll_test' => [
            'action' => 'complete_configuration',
            'label' => 'Testar processamento salarial',
            'route' => 'hrm.payrolls.index',
            'anchor' => null,
            'message' => 'Execute um teste de processamento salarial com contribuições configuradas.',
            'tone' => 'default',
        ],
        'treasury.create_bank_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Criar contas bancárias',
            'route' => 'account.bank-accounts.index',
            'anchor' => null,
            'message' => 'Registe as contas bancárias e de caixa usadas na tesouraria.',
            'tone' => 'default',
        ],
        'treasury.configure_payment_methods' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar métodos de pagamento',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Defina os métodos de pagamento aceites pela empresa.',
            'tone' => 'default',
        ],
        'treasury.configure_reconciliation_rules' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Prepare as regras de reconciliação bancária e de caixa.',
            'tone' => 'default',
        ],
        'treasury.record_initial_payments' => [
            'action' => 'complete_configuration',
            'label' => 'Registar pagamentos iniciais',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Registe os primeiros pagamentos para validar a tesouraria.',
            'tone' => 'default',
        ],
        'treasury.perform_first_reconciliation' => [
            'action' => 'complete_configuration',
            'label' => 'Executar primeira reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Execute a primeira reconciliação bancária.',
            'tone' => 'default',
        ],
        'treasury.validate_payment_flow' => [
            'action' => 'complete_configuration',
            'label' => 'Validar fluxo de pagamento',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Valide o fluxo de pagamentos de ponta a ponta.',
            'tone' => 'default',
        ],
        'inventory.create_warehouse' => [
            'action' => 'complete_configuration',
            'label' => 'Criar armazém',
            'route' => 'warehouses.index',
            'anchor' => null,
            'message' => 'Crie o primeiro armazém para suportar stock e POS.',
            'tone' => 'default',
        ],
        'inventory.load_initial_stock' => [
            'action' => 'complete_configuration',
            'label' => 'Carregar stock inicial',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Registe o stock inicial para gerar as layers FIFO.',
            'tone' => 'default',
        ],
        'inventory.verify_fifo_layers' => [
            'action' => 'complete_configuration',
            'label' => 'Validar FIFO',
            'route' => 'product-service.stock.index',
            'anchor' => null,
            'message' => 'Confirme as layers FIFO do stock carregado.',
            'tone' => 'default',
        ],
        'pos.run_test_sale' => [
            'action' => 'complete_configuration',
            'label' => 'Emitir venda POS de teste',
            'route' => 'pos.create',
            'anchor' => null,
            'message' => 'Abra o POS e emita uma venda de validação.',
            'tone' => 'default',
        ],
    ];

    /**
     * @var array<string, array<string, string|null>>
     */
    private const CHECKLIST_TARGETS = [
        'configure_tax' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar perfil fiscal',
            'route' => 'sce.fiscal.index',
            'anchor' => null,
            'message' => 'Complete o perfil fiscal antes de emitir documentos.',
            'tone' => 'default',
        ],
        'configure_series' => [
            'action' => 'complete_configuration',
            'label' => 'Criar séries documentais',
            'route' => 'sce.fiscal.series',
            'anchor' => null,
            'message' => 'Crie e active as séries documentais necessárias.',
            'tone' => 'default',
        ],
        'open_period' => [
            'action' => 'complete_configuration',
            'label' => 'Abrir período contabilístico',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Abra o período contabilístico para lançar documentos.',
            'tone' => 'default',
        ],
        'create_customer' => [
            'action' => 'complete_configuration',
            'label' => 'Criar clientes',
            'route' => 'account.customers.index',
            'anchor' => null,
            'message' => 'Crie os clientes necessários para facturação.',
            'tone' => 'default',
        ],
        'create_product' => [
            'action' => 'complete_configuration',
            'label' => 'Criar produtos',
            'route' => 'product-service.items.create',
            'anchor' => null,
            'message' => 'Registe os produtos ou serviços que vai vender.',
            'tone' => 'default',
        ],
        'chart_of_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Rever plano de contas',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Ajuste o plano de contas antes de contabilizar movimentos.',
            'tone' => 'default',
        ],
        'opening_balances' => [
            'action' => 'complete_configuration',
            'label' => 'Introduzir saldos iniciais',
            'route' => 'account.chart-of-accounts.index',
            'anchor' => null,
            'message' => 'Registe os saldos iniciais no arranque da empresa.',
            'tone' => 'default',
        ],
        'period_open' => [
            'action' => 'complete_configuration',
            'label' => 'Abrir período contabilístico',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Abra o período contabilístico para lançar documentos.',
            'tone' => 'default',
        ],
        'journal_templates' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar modelos de diário',
            'route' => 'sce.journals.index',
            'anchor' => null,
            'message' => 'Prepare os lançamentos recorrentes do diário.',
            'tone' => 'default',
        ],
        'trial_balance_review' => [
            'action' => 'complete_configuration',
            'label' => 'Rever balancete',
            'route' => 'account.reports.index',
            'anchor' => null,
            'message' => 'Reveja o balancete antes do fecho mensal.',
            'tone' => 'default',
        ],
        'month_end_close_rehearsal' => [
            'action' => 'complete_configuration',
            'label' => 'Rever fecho mensal',
            'route' => 'sce.monthly-closing.index',
            'anchor' => null,
            'message' => 'Valide o fecho mensal antes de o finalizar.',
            'tone' => 'default',
        ],
        'employee_masterdata' => [
            'action' => 'complete_configuration',
            'label' => 'Criar colaborador',
            'route' => 'hrm.employees.create',
            'anchor' => null,
            'message' => 'Registe o colaborador necessário para o processo salarial.',
            'tone' => 'default',
        ],
        'contract_defaults' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar modelos de contrato',
            'route' => 'contract-types.index',
            'anchor' => null,
            'message' => 'Prepare os modelos de contrato usados no RH.',
            'tone' => 'default',
        ],
        'attendance_policy' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar calendário de trabalho',
            'route' => 'hrm.working-days.index',
            'anchor' => null,
            'message' => 'Defina os dias úteis, turnos e feriados aplicáveis.',
            'tone' => 'default',
        ],
        'leave_policy' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar política de faltas',
            'route' => 'hrm.leave-types.index',
            'anchor' => null,
            'message' => 'Revise as políticas de férias, faltas e ausências.',
            'tone' => 'default',
        ],
        'payroll_calendar' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar calendário salarial',
            'route' => 'hrm.payrolls.index',
            'anchor' => null,
            'message' => 'Defina o calendário salarial antes de correr a folha.',
            'tone' => 'default',
        ],
        'payroll_test' => [
            'action' => 'complete_configuration',
            'label' => 'Testar processamento salarial',
            'route' => 'hrm.payrolls.index',
            'anchor' => null,
            'message' => 'Execute um teste de processamento salarial.',
            'tone' => 'default',
        ],
        'bank_accounts' => [
            'action' => 'complete_configuration',
            'label' => 'Criar contas bancárias',
            'route' => 'account.bank-accounts.index',
            'anchor' => null,
            'message' => 'Registe as contas bancárias e de caixa usadas na tesouraria.',
            'tone' => 'default',
        ],
        'payment_methods' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar métodos de pagamento',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Defina os métodos de pagamento aceites pela empresa.',
            'tone' => 'default',
        ],
        'reconciliation_rules' => [
            'action' => 'complete_configuration',
            'label' => 'Configurar reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Prepare as regras de reconciliação bancária e de caixa.',
            'tone' => 'default',
        ],
        'initial_payments' => [
            'action' => 'complete_configuration',
            'label' => 'Registar pagamentos iniciais',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Registe os primeiros pagamentos para validar a tesouraria.',
            'tone' => 'default',
        ],
        'first_reconciliation' => [
            'action' => 'complete_configuration',
            'label' => 'Executar primeira reconciliação',
            'route' => 'account.bank-transactions.index',
            'anchor' => null,
            'message' => 'Execute a primeira reconciliação bancária.',
            'tone' => 'default',
        ],
        'payment_test' => [
            'action' => 'complete_configuration',
            'label' => 'Validar fluxo de pagamento',
            'route' => 'account.customer-payments.index',
            'anchor' => null,
            'message' => 'Valide o fluxo de pagamentos de ponta a ponta.',
            'tone' => 'default',
        ],
    ];

    /**
     * @var array<string, array<string, string|null>>
     */
    private const ACTION_TARGETS = [
        'activate_addon' => [
            'label' => 'Rever add-ons',
            'route' => 'add-ons.index',
            'anchor' => null,
            'message' => 'Abra os add-ons da empresa e confirme se o módulo necessário está activo.',
            'tone' => 'secondary',
        ],
        'install_module' => [
            'label' => 'Rever módulos',
            'route' => 'add-ons.index',
            'anchor' => null,
            'message' => 'Abra os módulos/add-ons da empresa e confirme se este módulo está incluído no plano ou activo.',
            'tone' => 'secondary',
        ],
        'complete_configuration' => [
            'label' => 'Completar configuração',
            'route' => 'settings.index',
            'anchor' => 'company-settings',
            'message' => 'Complete a configuração operacional em falta.',
            'tone' => 'default',
        ],
        'grant_permission' => [
            'label' => 'Gerir permissões',
            'route' => 'roles.index',
            'anchor' => null,
            'message' => 'Abra os perfis de acesso para atribuir as permissões em falta.',
            'tone' => 'outline',
        ],
        'upgrade_plan' => [
            'label' => 'Actualizar plano',
            'route' => 'plans.index',
            'anchor' => null,
            'message' => 'Escolha um plano com cobertura suficiente para esta operação.',
            'tone' => 'default',
        ],
        'renew_subscription' => [
            'label' => 'Renovar subscrição',
            'route' => 'plans.index',
            'anchor' => null,
            'message' => 'Renove a subscrição para restaurar o acesso.',
            'tone' => 'default',
        ],
        'contact_support' => [
            'label' => 'Contactar suporte',
            'route' => 'helpdesk-tickets.create',
            'anchor' => null,
            'message' => 'Abra um pedido de suporte para rever o bloqueio.',
            'tone' => 'ghost',
        ],
        'select_company' => [
            'label' => 'Abrir painel',
            'route' => 'dashboard',
            'anchor' => null,
            'message' => 'Abra o painel e confirme a empresa activa.',
            'tone' => 'secondary',
        ],
        'reduce_usage' => [
            'label' => 'Rever utilização',
            'route' => 'dashboard',
            'anchor' => null,
            'message' => 'Revise a utilização actual para voltar a ficar dentro do limite.',
            'tone' => 'outline',
        ],
    ];

    public function forBlock(array $block): ?array
    {
        $type = (string) data_get($block, 'type', '');

        return match ($type) {
            'config_missing' => $this->resolveConfigTarget((string) data_get($block, 'key', ''), $block),
            'step_incomplete' => $this->resolveStepTarget((string) data_get($block, 'key', ''), $block),
            'permission_missing' => $this->resolvePermissionTarget((string) data_get($block, 'key', ''), $block),
            default => $this->forRecommendation((array) data_get($block, 'recommendation', []), $block),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function forBlocks(array $blocks): ?array
    {
        foreach ($blocks as $block) {
            $cta = $this->forBlock((array) $block);

            if ($cta !== null) {
                return $cta;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $recommendation
     * @param array<string, mixed> $resolution
     */
    public function forRecommendation(array $recommendation, array $resolution = []): ?array
    {
        $action = (string) ($recommendation['action'] ?? 'no_action');

        if ($action === 'no_action') {
            return null;
        }

        $target = match ($action) {
            'activate_addon', 'install_module', 'grant_permission', 'upgrade_plan', 'renew_subscription', 'contact_support', 'select_company', 'reduce_usage' => self::ACTION_TARGETS[$action] ?? null,
            'complete_configuration' => $this->resolveCompleteConfigurationTarget($resolution, $recommendation),
            default => null,
        };

        return $this->buildCta(
            action: $action,
            label: (string) ($target['label'] ?? ($recommendation['label'] ?? 'Abrir')),
            routeName: (string) ($target['route'] ?? 'onboarding.index'),
            anchor: $target['anchor'] ?? null,
            message: (string) ($target['message'] ?? ($recommendation['message'] ?? '')),
            tone: (string) ($target['tone'] ?? self::DEFAULT_TONE),
            source: [
                'type' => (string) ($resolution['type'] ?? $recommendation['type'] ?? 'feature'),
                'key' => (string) ($resolution['key'] ?? $recommendation['key'] ?? ''),
            ]
        );
    }

    public function resolveStepRoute(string $stepKey): ?string
    {
        $target = self::STEP_TARGETS[$stepKey] ?? null;

        return $this->resolveRouteFromTarget($target);
    }

    public function resolveChecklistRoute(string $checklistKey): ?string
    {
        $target = self::CHECKLIST_TARGETS[$checklistKey] ?? null;

        return $this->resolveRouteFromTarget($target);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function resolveConfigTarget(string $configKey, array $block): ?array
    {
        if ($configKey === 'mozambique_fiscal_compliance') {
            return $this->resolveMozambiqueFiscalComplianceTarget($block);
        }

        $target = self::CONFIG_TARGETS[$configKey] ?? null;

        if ($target === null) {
            return null;
        }

        return $this->buildCta(
            action: (string) ($target['action'] ?? 'complete_configuration'),
            label: (string) $target['label'],
            routeName: (string) $target['route'],
            anchor: $target['anchor'] ?? null,
            message: (string) $target['message'],
            tone: (string) ($target['tone'] ?? 'default'),
            source: [
                'type' => (string) data_get($block, 'type', 'config_missing'),
                'key' => $configKey,
            ]
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function resolveMozambiqueFiscalComplianceTarget(array $block): ?array
    {
        $missingItem = (string) data_get($block, 'details.missing_items.0', '');
        $reason = (string) data_get($block, 'reason', '');

        $target = match ($missingItem !== '' ? $missingItem : $reason) {
            'company_profile' => [
                'label' => 'Configurar empresa',
                'route' => 'settings.index',
                'anchor' => 'company-settings',
                'message' => 'Complete os dados base da empresa, incluindo NUIT e tipo fiscal.',
            ],
            'fiscal_profile', 'missing_profile', 'missing_fields', 'invalid_nuit', 'invalid_tax_number', 'license_expired' => [
                'label' => 'Configurar perfil fiscal',
                'route' => 'sce.fiscal.index',
                'anchor' => null,
                'message' => 'Complete o perfil fiscal, regime de IVA e restantes dados SCE.',
            ],
            'document_series' => [
                'label' => 'Criar séries documentais',
                'route' => 'sce.fiscal.series',
                'anchor' => null,
                'message' => 'Crie e valide as séries documentais para emissão fiscal.',
            ],
            'accounting_period', 'period_not_open' => [
                'label' => 'Abrir período contabilístico',
                'route' => 'sce.monthly-closing.index',
                'anchor' => null,
                'message' => 'Abra o período contabilístico para permitir a emissão fiscal.',
            ],
            'tax_profile', 'missing_mapping' => [
                'label' => 'Configurar mapeamento fiscal',
                'route' => 'account.mozambique-tax-account-mappings.index',
                'anchor' => null,
                'message' => 'Complete o mapeamento fiscal na configuração do sistema e associe as contas em falta.',
            ],
            'calendar_routes', 'missing_calendar_routes' => [
                'label' => 'Abrir calendário fiscal',
                'route' => 'sce.fiscal.calendar',
                'anchor' => null,
                'message' => 'Aceda ao calendário fiscal e valide os eventos SCE.',
            ],
            'saft_xsd', 'missing_saft_xsd' => [
                'label' => 'Rever SAF-T',
                'route' => 'sce.fiscal.saft-export',
                'anchor' => null,
                'message' => 'Verifique a validação SAF-T e a configuração do XSD oficial.',
            ],
            default => [
                'label' => 'Configurar conformidade fiscal',
                'route' => 'sce.fiscal.index',
                'anchor' => null,
                'message' => 'Complete a configuração fiscal de Moçambique antes de avançar.',
            ],
        };

        return $this->buildCta(
            action: 'complete_configuration',
            label: $target['label'],
            routeName: $target['route'],
            anchor: $target['anchor'] ?? null,
            message: $target['message'],
            tone: 'default',
            source: [
                'type' => (string) data_get($block, 'type', 'config_missing'),
                'key' => 'mozambique_fiscal_compliance',
            ]
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function resolveStepTarget(string $stepKey, array $block): ?array
    {
        $target = self::STEP_TARGETS[$stepKey] ?? null;

        if ($target === null) {
            return null;
        }

        return $this->buildCta(
            action: (string) ($target['action'] ?? 'complete_configuration'),
            label: (string) $target['label'],
            routeName: (string) $target['route'],
            anchor: $target['anchor'] ?? null,
            message: (string) $target['message'],
            tone: (string) ($target['tone'] ?? 'default'),
            source: [
                'type' => (string) data_get($block, 'type', 'step_incomplete'),
                'key' => $stepKey,
            ]
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function resolvePermissionTarget(string $stepKey, array $block): ?array
    {
        $target = self::ACTION_TARGETS['grant_permission'] ?? null;

        if ($target === null) {
            return null;
        }

        $missingPermissions = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) data_get($block, 'details.missing_permissions', [])
        )));
        $message = $missingPermissions === []
            ? (string) ($target['message'] ?? 'Abra os perfis de acesso para atribuir as permissões em falta.')
            : sprintf(
                'Abra os perfis de acesso para atribuir as permissões em falta: %s.',
                implode(', ', array_slice($missingPermissions, 0, 3))
            );

        return $this->buildCta(
            action: 'grant_permission',
            label: (string) ($target['label'] ?? 'Gerir permissões'),
            routeName: (string) ($target['route'] ?? 'roles.index'),
            anchor: $target['anchor'] ?? null,
            message: $message,
            tone: (string) ($target['tone'] ?? 'outline'),
            source: [
                'type' => (string) data_get($block, 'type', 'permission_missing'),
                'key' => $stepKey,
            ]
        );
    }

    /**
     * @param array<string, mixed> $resolution
     * @param array<string, mixed> $recommendation
     * @return array<string, string|null>|null
     */
    private function resolveCompleteConfigurationTarget(array $resolution, array $recommendation): ?array
    {
        $configKeys = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            array_merge(
                (array) data_get($resolution, 'missing_config_keys', []),
                (array) data_get($recommendation, 'recommended_config_keys', [])
            )
        )));

        foreach ($configKeys as $configKey) {
            if (isset(self::CONFIG_TARGETS[$configKey])) {
                return self::CONFIG_TARGETS[$configKey];
            }
        }

        $firstConfigKey = $configKeys[0] ?? null;

        if ($firstConfigKey !== null && in_array($firstConfigKey, array_keys(self::CHECKLIST_TARGETS), true)) {
            return self::CHECKLIST_TARGETS[$firstConfigKey];
        }

        return self::ACTION_TARGETS['complete_configuration'];
    }

    /**
     * @param array<string, mixed>|null $target
     */
    private function resolveRouteFromTarget(?array $target): ?string
    {
        if ($target === null) {
            return null;
        }

        return $this->buildHref(
            (string) ($target['route'] ?? ''),
            [],
            isset($target['anchor']) ? (string) $target['anchor'] : null
        );
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function buildCta(
        string $action,
        string $label,
        string $routeName,
        ?string $anchor,
        string $message,
        string $tone,
        array $source
    ): array {
        return [
            'action' => $action,
            'label' => $label,
            'href' => $this->buildHref($routeName, [], $anchor),
            'message' => $message,
            'tone' => $tone,
            'source' => $source,
        ];
    }

    private function buildHref(string $routeName, array $parameters = [], ?string $anchor = null): string
    {
        $href = route($routeName, $parameters);

        if ($anchor !== null && $anchor !== '') {
            return $href . '#' . ltrim($anchor, '#');
        }

        return $href;
    }
}
