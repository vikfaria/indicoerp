<?php

return [
    'contract_version' => '2026-06-06',

    'cache' => [
        'ttl_minutes' => 15,
    ],

    'feature_states' => [
        'active',
        'locked',
        'hidden',
        'addon',
    ],

    'global_requirements' => [
        'company_profile',
        'fiscal_profile',
        'accounting_period',
        'document_series',
        'tax_profile',
    ],

    'plan_families' => [
        'free' => [
            'label' => 'Free Plan',
            'aliases' => ['free plan', 'free'],
            'description' => 'Baseline plan for evaluation and limited activation.',
        ],
        'starter' => [
            'label' => 'Starter Plan',
            'aliases' => ['starter plan', 'starter'],
            'description' => 'Entry plan for small teams.',
        ],
        'professional' => [
            'label' => 'Professional Plan',
            'aliases' => ['professional plan', 'professional', 'pro'],
            'description' => 'Operational plan for growing businesses.',
        ],
        'enterprise' => [
            'label' => 'Enterprise / Elite Pro',
            'aliases' => ['enterprise', 'elite pro', 'elite'],
            'description' => 'Highest-coverage plan family with custom limits.',
        ],
        'custom' => [
            'label' => 'Custom Plan',
            'aliases' => ['custom plan', 'custom'],
            'description' => 'Client-specific commercial contract.',
        ],
    ],

    'priority_domains' => [
        'billing' => [
            'label' => 'Facturação',
            'priority' => 1,
            'recommended_modules' => ['Account', 'ProductService'],
            'required_permissions' => [
                'manage-account',
                'manage-sales-invoices',
                'view-sales-invoices',
                'create-sales-invoices',
                'post-sales-invoices',
                'manage-sales-return-invoices',
                'view-sales-return-invoices',
            ],
            'required_config_keys' => [
                'company_profile',
                'fiscal_profile',
                'document_series',
                'accounting_period',
                'tax_profile',
                'customer_masterdata',
                'product_masterdata',
            ],
            'checklist' => [
                'create_customer',
                'create_product',
                'configure_tax',
                'configure_series',
                'open_period',
                'issue_invoice',
            ],
        ],
        'accounting' => [
            'label' => 'Contabilidade',
            'priority' => 2,
            'recommended_modules' => ['Account', 'DoubleEntry'],
            'required_permissions' => [
                'manage-account',
                'manage-chart-of-accounts',
                'manage-double-entry',
                'manage-trial-balance',
                'manage-balance-sheets',
                'view-general-ledger',
            ],
            'required_config_keys' => [
                'accounting_period',
                'chart_of_accounts',
                'opening_balances',
                'closing_rules',
            ],
            'checklist' => [
                'chart_of_accounts',
                'opening_balances',
                'period_open',
                'journal_templates',
                'trial_balance_review',
            ],
        ],
        'hr' => [
            'label' => 'Recursos Humanos',
            'priority' => 3,
            'recommended_modules' => ['Hrm'],
            'required_permissions' => [
                'manage-hrm',
                'manage-employees',
                'manage-attendances',
                'manage-payrolls',
                'manage-leave-applications',
            ],
            'required_config_keys' => [
                'hr_company_profile',
                'employee_masterdata',
                'contract_templates',
                'payroll_calendar',
                'payroll_contributions',
                'leave_policy',
            ],
            'checklist' => [
                'employee_masterdata',
                'contract_defaults',
                'attendance_policy',
                'leave_policy',
                'payroll_calendar',
                'payroll_contributions',
            ],
        ],
        'treasury' => [
            'label' => 'Tesouraria',
            'priority' => 4,
            'recommended_modules' => ['Account'],
            'required_permissions' => [
                'manage-bank-accounts',
                'manage-bank-transactions',
                'reconcile-bank-transactions',
                'manage-bank-transfers',
                'manage-customer-payments',
                'manage-vendor-payments',
            ],
            'required_config_keys' => [
                'bank_accounts',
                'cash_accounts',
                'payment_methods',
                'reconciliation_rules',
            ],
            'checklist' => [
                'bank_accounts',
                'cash_accounts',
                'payment_methods',
                'reconciliation_rules',
                'first_reconciliation',
            ],
        ],
        'inventory' => [
            'label' => 'Inventário',
            'priority' => 5,
            'recommended_modules' => ['warehouses', 'ProductService', 'Pos'],
            'required_permissions' => [
                'manage-warehouses',
                'create-warehouses',
                'manage-stock',
                'create-stock',
                'manage-transfers',
                'create-transfers',
                'manage-pos',
                'manage-pos-orders',
                'create-pos',
            ],
            'required_config_keys' => [
                'warehouses',
                'initial_stock',
                'fifo_layers',
                'pos_registers',
            ],
            'checklist' => [
                'create_warehouse',
                'load_initial_stock',
                'verify_fifo_layers',
                'run_pos_test',
            ],
        ],
    ],
];
