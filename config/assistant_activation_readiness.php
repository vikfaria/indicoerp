<?php

return [
    'catalog_version' => '2026-06-06',

    'formula' => [
        'description' => 'Overall readiness = module component + critical configuration component. Module readiness is a weighted average of the priority onboarding modules in scope. Configuration readiness is a weighted coverage score over critical setup keys.',
        'module_component_weight' => 70,
        'critical_config_component_weight' => 30,
        'ready_threshold' => 80,
        'warning_threshold' => 60,
        'blocked_threshold' => 40,
    ],

    'module_weights' => [
        [
            'key' => 'billing',
            'label' => 'Facturação',
            'weight' => 35,
            'description' => 'Core invoice readiness and fiscal issuance.',
        ],
        [
            'key' => 'accounting',
            'label' => 'Contabilidade',
            'weight' => 30,
            'description' => 'Chart of accounts, postings and month-end control.',
        ],
        [
            'key' => 'hr',
            'label' => 'Recursos Humanos',
            'weight' => 20,
            'description' => 'Employee masterdata, policies and payroll readiness.',
        ],
        [
            'key' => 'treasury',
            'label' => 'Tesouraria',
            'weight' => 15,
            'description' => 'Bank, cash and reconciliation readiness.',
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventário',
            'weight' => 10,
            'description' => 'Warehouse, stock, FIFO and POS readiness.',
        ],
        [
            'key' => 'pos',
            'label' => 'POS',
            'weight' => 5,
            'description' => 'Point-of-sale validation and execution readiness.',
        ],
    ],

    'critical_config_keys' => [
        [
            'key' => 'company_profile',
            'label' => 'Company profile',
            'weight' => 5,
            'description' => 'Legal identity, address and company master data.',
        ],
        [
            'key' => 'fiscal_profile',
            'label' => 'Fiscal profile',
            'weight' => 8,
            'description' => 'Taxpayer profile, VAT regime and fiscal metadata.',
        ],
        [
            'key' => 'document_series',
            'label' => 'Document series',
            'weight' => 7,
            'description' => 'Invoice and supporting document series configuration.',
        ],
        [
            'key' => 'accounting_period',
            'label' => 'Accounting period',
            'weight' => 10,
            'description' => 'Open fiscal/accounting period for valid postings.',
        ],
        [
            'key' => 'tax_profile',
            'label' => 'Tax profile',
            'weight' => 5,
            'description' => 'IVA and tax mapping readiness.',
        ],
        [
            'key' => 'mozambique_fiscal_compliance',
            'label' => 'Mozambique fiscal compliance',
            'weight' => 0,
            'description' => 'NUIT, IVA, document series, accounting period and SCE readiness for Mozambique.',
        ],
        [
            'key' => 'customer_masterdata',
            'label' => 'Customer masterdata',
            'weight' => 3,
            'description' => 'Customers available for invoice and payment flow.',
        ],
        [
            'key' => 'product_masterdata',
            'label' => 'Product masterdata',
            'weight' => 2,
            'description' => 'Products, services and stock-ready masterdata.',
        ],
        [
            'key' => 'chart_of_accounts',
            'label' => 'Chart of accounts',
            'weight' => 10,
            'description' => 'Accounting structure and account codes.',
        ],
        [
            'key' => 'opening_balances',
            'label' => 'Opening balances',
            'weight' => 8,
            'description' => 'Initial balances and opening entries.',
        ],
        [
            'key' => 'closing_rules',
            'label' => 'Closing rules',
            'weight' => 4,
            'description' => 'Monthly and yearly closing control rules.',
        ],
        [
            'key' => 'hr_company_profile',
            'label' => 'HR company profile',
            'weight' => 4,
            'description' => 'HR organizational and labour settings.',
        ],
        [
            'key' => 'employee_masterdata',
            'label' => 'Employee masterdata',
            'weight' => 6,
            'description' => 'Employees, contracts and core HR records.',
        ],
        [
            'key' => 'contract_templates',
            'label' => 'Contract templates',
            'weight' => 3,
            'description' => 'Standard contract templates for admissions.',
        ],
        [
            'key' => 'payroll_calendar',
            'label' => 'Payroll calendar',
            'weight' => 5,
            'description' => 'Payroll processing dates and cycles.',
        ],
        [
            'key' => 'leave_policy',
            'label' => 'Leave policy',
            'weight' => 4,
            'description' => 'Absence rules and approval policy.',
        ],
        [
            'key' => 'payroll_contributions',
            'label' => 'Payroll contributions',
            'weight' => 4,
            'description' => 'INSS and IRPS tables and rates for payroll processing.',
        ],
        [
            'key' => 'bank_accounts',
            'label' => 'Bank accounts',
            'weight' => 8,
            'description' => 'Bank and cash accounts for treasury use.',
        ],
        [
            'key' => 'cash_accounts',
            'label' => 'Cash accounts',
            'weight' => 4,
            'description' => 'Cash control accounts and drawers.',
        ],
        [
            'key' => 'payment_methods',
            'label' => 'Payment methods',
            'weight' => 5,
            'description' => 'Accepted payment methods for receipts and disbursements.',
        ],
        [
            'key' => 'reconciliation_rules',
            'label' => 'Reconciliation rules',
            'weight' => 5,
            'description' => 'Matching and reconciliation policy.',
        ],
        [
            'key' => 'warehouses',
            'label' => 'Warehouses',
            'weight' => 5,
            'description' => 'At least one active warehouse for stock operations.',
        ],
        [
            'key' => 'initial_stock',
            'label' => 'Initial stock',
            'weight' => 5,
            'description' => 'Opening or manual stock movements for the inventory baseline.',
        ],
        [
            'key' => 'fifo_layers',
            'label' => 'FIFO layers',
            'weight' => 5,
            'description' => 'Available FIFO layers backing the current stock balance.',
        ],
        [
            'key' => 'pos_registers',
            'label' => 'POS registers',
            'weight' => 5,
            'description' => 'Validated POS sale or register state for the point-of-sale flow.',
        ],
    ],
];
