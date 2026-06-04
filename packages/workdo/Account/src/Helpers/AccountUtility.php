<?php

namespace Workdo\Account\Helpers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\OpeningBalance;

class AccountUtility
{
    public static function defaultdata($company_id = null)
    {
        self::createAccountCategories($company_id);
        self::createAccountTypes($company_id);
        self::createChartOfAccounts($company_id);
    }

    public static function accountCategoryDefinitions(string $locale = 'pt'): array
    {
        if ($locale === 'en') {
            return [
                ['name' => 'Assets', 'code' => 'AST', 'type' => 'assets', 'description' => 'Resources owned by the company'],
                ['name' => 'Liabilities', 'code' => 'LIB', 'type' => 'liabilities', 'description' => 'Debts and obligations of the company'],
                ['name' => 'Equity', 'code' => 'EQT', 'type' => 'equity', 'description' => 'Owner\'s equity in the company'],
                ['name' => 'Revenue', 'code' => 'REV', 'type' => 'revenue', 'description' => 'Income generated from business operations'],
                ['name' => 'Expenses', 'code' => 'EXP', 'type' => 'expenses', 'description' => 'Costs incurred in business operations'],
            ];
        }

        return [
            ['name' => 'Ativos', 'code' => 'AST', 'type' => 'assets', 'description' => 'Recursos controlados pela empresa'],
            ['name' => 'Passivos', 'code' => 'LIB', 'type' => 'liabilities', 'description' => 'Obrigacoes e dividas da empresa'],
            ['name' => 'Capital Proprio', 'code' => 'EQT', 'type' => 'equity', 'description' => 'Investimento e resultados acumulados dos socios'],
            ['name' => 'Receitas', 'code' => 'REV', 'type' => 'revenue', 'description' => 'Rendimentos gerados pela atividade da empresa'],
            ['name' => 'Despesas', 'code' => 'EXP', 'type' => 'expenses', 'description' => 'Gastos incorridos na atividade da empresa'],
        ];
    }

    public static function accountTypeDefinitions(string $locale = 'pt'): array
    {
        if ($locale === 'en') {
            return [
                ['category_code' => 'AST', 'name' => 'Current Assets', 'code' => 'CA', 'normal_balance' => 'debit', 'description' => 'Assets expected to be converted to cash within one year'],
                ['category_code' => 'AST', 'name' => 'Fixed Assets', 'code' => 'FA', 'normal_balance' => 'debit', 'description' => 'Long-term tangible assets'],
                ['category_code' => 'AST', 'name' => 'Other Assets', 'code' => 'OA', 'normal_balance' => 'debit', 'description' => 'Other miscellaneous assets'],
                ['category_code' => 'LIB', 'name' => 'Current Liabilities', 'code' => 'CL', 'normal_balance' => 'credit', 'description' => 'Debts due within one year'],
                ['category_code' => 'LIB', 'name' => 'Long-term Liabilities', 'code' => 'LTL', 'normal_balance' => 'credit', 'description' => 'Debts due after one year'],
                ['category_code' => 'EQT', 'name' => 'Share Capital', 'code' => 'SC', 'normal_balance' => 'credit', 'description' => 'Owner\'s investment in the business'],
                ['category_code' => 'EQT', 'name' => 'Retained Earnings', 'code' => 'RE', 'normal_balance' => 'credit', 'description' => 'Accumulated profits retained in business'],
                ['category_code' => 'REV', 'name' => 'Sales Revenue', 'code' => 'SR', 'normal_balance' => 'credit', 'description' => 'Income from sales of goods or services'],
                ['category_code' => 'REV', 'name' => 'Other Income', 'code' => 'OI', 'normal_balance' => 'credit', 'description' => 'Miscellaneous income'],
                ['category_code' => 'EXP', 'name' => 'Cost of Goods Sold', 'code' => 'COGS', 'normal_balance' => 'debit', 'description' => 'Direct costs of producing goods sold'],
                ['category_code' => 'EXP', 'name' => 'Operating Expenses', 'code' => 'OE', 'normal_balance' => 'debit', 'description' => 'Expenses from normal business operations'],
                ['category_code' => 'EXP', 'name' => 'Administrative Expenses', 'code' => 'AE', 'normal_balance' => 'debit', 'description' => 'General administrative costs'],
                ['category_code' => 'EXP', 'name' => 'Financial Expenses', 'code' => 'FE', 'normal_balance' => 'debit', 'description' => 'Interest and financial costs'],
                ['category_code' => 'EXP', 'name' => 'Tax Expenses', 'code' => 'TE', 'normal_balance' => 'debit', 'description' => 'Tax-related expenses'],
                ['category_code' => 'EXP', 'name' => 'Other Expenses', 'code' => 'OX', 'normal_balance' => 'debit', 'description' => 'Miscellaneous expenses'],
            ];
        }

        return [
            ['category_code' => 'AST', 'name' => 'Ativos Correntes', 'code' => 'CA', 'normal_balance' => 'debit', 'description' => 'Ativos realizaveis em dinheiro ate um ano'],
            ['category_code' => 'AST', 'name' => 'Ativos Fixos', 'code' => 'FA', 'normal_balance' => 'debit', 'description' => 'Ativos tangiveis de longo prazo'],
            ['category_code' => 'AST', 'name' => 'Outros Ativos', 'code' => 'OA', 'normal_balance' => 'debit', 'description' => 'Outros ativos diversos'],
            ['category_code' => 'LIB', 'name' => 'Passivos Correntes', 'code' => 'CL', 'normal_balance' => 'credit', 'description' => 'Obrigacoes exigiveis ate um ano'],
            ['category_code' => 'LIB', 'name' => 'Passivos Nao Correntes', 'code' => 'LTL', 'normal_balance' => 'credit', 'description' => 'Obrigacoes exigiveis apos um ano'],
            ['category_code' => 'EQT', 'name' => 'Capital Social', 'code' => 'SC', 'normal_balance' => 'credit', 'description' => 'Entrada de capital dos socios'],
            ['category_code' => 'EQT', 'name' => 'Resultados Transitados', 'code' => 'RE', 'normal_balance' => 'credit', 'description' => 'Lucros acumulados retidos na empresa'],
            ['category_code' => 'REV', 'name' => 'Receitas de Vendas', 'code' => 'SR', 'normal_balance' => 'credit', 'description' => 'Rendimentos da venda de bens ou servicos'],
            ['category_code' => 'REV', 'name' => 'Outros Rendimentos', 'code' => 'OI', 'normal_balance' => 'credit', 'description' => 'Rendimentos diversos'],
            ['category_code' => 'EXP', 'name' => 'Custo das Mercadorias Vendidas', 'code' => 'COGS', 'normal_balance' => 'debit', 'description' => 'Custos diretos das mercadorias vendidas'],
            ['category_code' => 'EXP', 'name' => 'Despesas Operacionais', 'code' => 'OE', 'normal_balance' => 'debit', 'description' => 'Gastos normais da operacao'],
            ['category_code' => 'EXP', 'name' => 'Despesas Administrativas', 'code' => 'AE', 'normal_balance' => 'debit', 'description' => 'Gastos gerais administrativos'],
            ['category_code' => 'EXP', 'name' => 'Despesas Financeiras', 'code' => 'FE', 'normal_balance' => 'debit', 'description' => 'Juros e outros custos financeiros'],
            ['category_code' => 'EXP', 'name' => 'Despesas Fiscais', 'code' => 'TE', 'normal_balance' => 'debit', 'description' => 'Gastos relacionados com impostos'],
            ['category_code' => 'EXP', 'name' => 'Outras Despesas', 'code' => 'OX', 'normal_balance' => 'debit', 'description' => 'Outros gastos diversos'],
        ];
    }

    public static function chartOfAccountDefinitions(string $locale = 'pt'): array
    {
        if ($locale === 'en') {
            return [
                ['type_code' => 'CA', 'account_code' => '1000', 'account_name' => 'Cash', 'normal_balance' => 'debit', 'description' => 'Physical cash in office'],
                ['type_code' => 'CA', 'account_code' => '1005', 'account_name' => 'Petty Cash', 'normal_balance' => 'debit', 'description' => 'Small cash for minor expenses'],
                ['type_code' => 'CA', 'account_code' => '1010', 'account_name' => 'Bank Account - Main', 'normal_balance' => 'debit', 'description' => 'Primary bank checking account'],
                ['type_code' => 'CA', 'account_code' => '1020', 'account_name' => 'Bank Account - Savings', 'normal_balance' => 'debit', 'description' => 'Business savings account'],
                ['type_code' => 'CA', 'account_code' => '1030', 'account_name' => 'Bank Account - Payroll', 'normal_balance' => 'debit', 'description' => 'Dedicated payroll account'],
                ['type_code' => 'CA', 'account_code' => '1040', 'account_name' => 'Cash in Transit', 'normal_balance' => 'debit', 'description' => 'Cash being transferred between accounts'],
                ['type_code' => 'CA', 'account_code' => '1100', 'account_name' => 'Accounts Receivable', 'normal_balance' => 'debit', 'description' => 'Money owed by customers'],
                ['type_code' => 'CA', 'account_code' => '1200', 'account_name' => 'Inventory', 'normal_balance' => 'debit', 'description' => 'Goods held for sale'],
                ['type_code' => 'CA', 'account_code' => '1300', 'account_name' => 'Prepaid Expenses', 'normal_balance' => 'debit', 'description' => 'Expenses paid in advance'],
                ['type_code' => 'CA', 'account_code' => '1310', 'account_name' => 'Supplier Advances', 'normal_balance' => 'debit', 'description' => 'Advance payments made to suppliers before invoice settlement'],
                ['type_code' => 'CA', 'account_code' => '1320', 'account_name' => 'Employee Advances', 'normal_balance' => 'debit', 'description' => 'Advances and loans recoverable from employees'],
                ['type_code' => 'OA', 'account_code' => '1400', 'account_name' => 'Deposits', 'normal_balance' => 'debit', 'description' => 'Security deposits paid'],
                ['type_code' => 'OA', 'account_code' => '1500', 'account_name' => 'Tax Receivable (VAT/GST Input)', 'normal_balance' => 'debit', 'description' => 'Tax refunds due'],
                ['type_code' => 'FA', 'account_code' => '1600', 'account_name' => 'Equipment', 'normal_balance' => 'debit', 'description' => 'Office and business equipment'],
                ['type_code' => 'FA', 'account_code' => '1610', 'account_name' => 'Accumulated Depreciation - Equipment', 'normal_balance' => 'credit', 'description' => 'Accumulated depreciation on equipment'],
                ['type_code' => 'FA', 'account_code' => '1700', 'account_name' => 'Buildings', 'normal_balance' => 'debit', 'description' => 'Building assets'],
                ['type_code' => 'FA', 'account_code' => '1710', 'account_name' => 'Accumulated Depreciation - Buildings', 'normal_balance' => 'credit', 'description' => 'Accumulated depreciation on buildings'],
                ['type_code' => 'CL', 'account_code' => '2000', 'account_name' => 'Accounts Payable', 'normal_balance' => 'credit', 'description' => 'Money owed to suppliers'],
                ['type_code' => 'CL', 'account_code' => '2100', 'account_name' => 'Accrued Expenses', 'normal_balance' => 'credit', 'description' => 'Expenses incurred but not yet paid'],
                ['type_code' => 'CL', 'account_code' => '2200', 'account_name' => 'Tax Payable (Income Tax)', 'normal_balance' => 'credit', 'description' => 'Taxes owed'],
                ['type_code' => 'CL', 'account_code' => '2210', 'account_name' => 'VAT Payable (Sales Tax Output)', 'normal_balance' => 'credit', 'description' => 'VAT owed to government'],
                ['type_code' => 'CL', 'account_code' => '2220', 'account_name' => 'GST Payable', 'normal_balance' => 'credit', 'description' => 'GST owed to government'],
                ['type_code' => 'CL', 'account_code' => '2300', 'account_name' => 'Short-term Loans', 'normal_balance' => 'credit', 'description' => 'Loans due within one year'],
                ['type_code' => 'CL', 'account_code' => '2350', 'account_name' => 'Customer Deposits', 'normal_balance' => 'credit', 'description' => 'Advance payments from customers for future services'],
                ['type_code' => 'CL', 'account_code' => '2400', 'account_name' => 'Payroll Liabilities', 'normal_balance' => 'credit', 'description' => 'Unpaid employee salaries and benefits'],
                ['type_code' => 'LTL', 'account_code' => '2500', 'account_name' => 'Long-term Debt', 'normal_balance' => 'credit', 'description' => 'Debts due after one year'],
                ['type_code' => 'SC', 'account_code' => '3100', 'account_name' => 'Share Capital', 'normal_balance' => 'credit', 'description' => 'Owner\'s investment in business'],
                ['type_code' => 'RE', 'account_code' => '3200', 'account_name' => 'Retained Earnings', 'normal_balance' => 'credit', 'description' => 'Accumulated business profits'],
                ['type_code' => 'SR', 'account_code' => '4100', 'account_name' => 'Sales Revenue', 'normal_balance' => 'credit', 'description' => 'Revenue from product sales'],
                ['type_code' => 'SR', 'account_code' => '4010', 'account_name' => 'Product Sales', 'normal_balance' => 'credit', 'description' => 'Revenue from product sales'],
                ['type_code' => 'SR', 'account_code' => '4200', 'account_name' => 'Service Revenue', 'normal_balance' => 'credit', 'description' => 'Revenue from services provided'],
                ['type_code' => 'SR', 'account_code' => '4030', 'account_name' => 'Consulting Revenue', 'normal_balance' => 'credit', 'description' => 'Revenue from consulting services'],
                ['type_code' => 'SR', 'account_code' => '4040', 'account_name' => 'Subscription Revenue', 'normal_balance' => 'credit', 'description' => 'Revenue from subscription services'],
                ['type_code' => 'OI', 'account_code' => '4110', 'account_name' => 'Commission Income', 'normal_balance' => 'credit', 'description' => 'Income from commissions'],
                ['type_code' => 'OI', 'account_code' => '4120', 'account_name' => 'Rental Income', 'normal_balance' => 'credit', 'description' => 'Income from rental properties'],
                ['type_code' => 'OI', 'account_code' => '4130', 'account_name' => 'Maintenance Income', 'normal_balance' => 'credit', 'description' => 'Income from maintenance services'],
                ['type_code' => 'OI', 'account_code' => '4140', 'account_name' => 'Training Income', 'normal_balance' => 'credit', 'description' => 'Income from training services'],
                ['type_code' => 'OI', 'account_code' => '4300', 'account_name' => 'Other Income', 'normal_balance' => 'credit', 'description' => 'Miscellaneous income'],
                ['type_code' => 'SR', 'account_code' => '4400', 'account_name' => 'Project Revenue', 'normal_balance' => 'credit', 'description' => 'Revenue from project-based work'],
                ['type_code' => 'COGS', 'account_code' => '5100', 'account_name' => 'Cost of Goods Sold', 'normal_balance' => 'debit', 'description' => 'Direct cost of products sold'],
                ['type_code' => 'OE', 'account_code' => '5200', 'account_name' => 'Salaries Expense', 'normal_balance' => 'debit', 'description' => 'Employee salaries'],
                ['type_code' => 'OE', 'account_code' => '5210', 'account_name' => 'Employee Benefits', 'normal_balance' => 'debit', 'description' => 'Employee benefits and insurance'],
                ['type_code' => 'OE', 'account_code' => '5220', 'account_name' => 'Sales Commission Expense', 'normal_balance' => 'debit', 'description' => 'Commission paid to sales agents'],
                ['type_code' => 'OE', 'account_code' => '5300', 'account_name' => 'Rent Expense', 'normal_balance' => 'debit', 'description' => 'Office rent payments'],
                ['type_code' => 'OE', 'account_code' => '5310', 'account_name' => 'Office Supplies', 'normal_balance' => 'debit', 'description' => 'General office supplies'],
                ['type_code' => 'OE', 'account_code' => '5320', 'account_name' => 'Marketing Expense', 'normal_balance' => 'debit', 'description' => 'Marketing and advertising costs'],
                ['type_code' => 'OE', 'account_code' => '5330', 'account_name' => 'Travel Expense', 'normal_balance' => 'debit', 'description' => 'Business travel expenses'],
                ['type_code' => 'AE', 'account_code' => '5400', 'account_name' => 'Utilities Expense', 'normal_balance' => 'debit', 'description' => 'Electricity, water, internet'],
                ['type_code' => 'AE', 'account_code' => '5410', 'account_name' => 'Insurance Expense', 'normal_balance' => 'debit', 'description' => 'Business insurance premiums'],
                ['type_code' => 'AE', 'account_code' => '5420', 'account_name' => 'Professional Fees', 'normal_balance' => 'debit', 'description' => 'Legal and accounting fees'],
                ['type_code' => 'AE', 'account_code' => '5430', 'account_name' => 'Depreciation Expense', 'normal_balance' => 'debit', 'description' => 'Depreciation on fixed assets'],
                ['type_code' => 'FE', 'account_code' => '5500', 'account_name' => 'Interest Expense', 'normal_balance' => 'debit', 'description' => 'Interest on loans and debt'],
                ['type_code' => 'FE', 'account_code' => '5510', 'account_name' => 'Bank Charges', 'normal_balance' => 'debit', 'description' => 'Bank fees and charges'],
                ['type_code' => 'TE', 'account_code' => '5600', 'account_name' => 'Tax Expense', 'normal_balance' => 'debit', 'description' => 'Income tax expense'],
                ['type_code' => 'OX', 'account_code' => '5700', 'account_name' => 'Bad Debt Expense', 'normal_balance' => 'debit', 'description' => 'Uncollectible accounts expense'],
                ['type_code' => 'OX', 'account_code' => '5800', 'account_name' => 'Miscellaneous Expense', 'normal_balance' => 'debit', 'description' => 'Other miscellaneous expenses'],
            ];
        }

        return [
            ['type_code' => 'CA', 'account_code' => '1000', 'account_name' => 'Caixa', 'normal_balance' => 'debit', 'description' => 'Numerario fisico em caixa'],
            ['type_code' => 'CA', 'account_code' => '1005', 'account_name' => 'Fundo de Caixa', 'normal_balance' => 'debit', 'description' => 'Fundo para pequenas despesas'],
            ['type_code' => 'CA', 'account_code' => '1010', 'account_name' => 'Conta Bancaria Principal', 'normal_balance' => 'debit', 'description' => 'Conta bancaria principal da empresa'],
            ['type_code' => 'CA', 'account_code' => '1020', 'account_name' => 'Conta Bancaria Poupanca', 'normal_balance' => 'debit', 'description' => 'Conta poupanca da empresa'],
            ['type_code' => 'CA', 'account_code' => '1030', 'account_name' => 'Conta Bancaria de Salarios', 'normal_balance' => 'debit', 'description' => 'Conta bancaria reservada a salarios'],
            ['type_code' => 'CA', 'account_code' => '1040', 'account_name' => 'Caixa em Transito', 'normal_balance' => 'debit', 'description' => 'Valores em transito entre contas'],
            ['type_code' => 'CA', 'account_code' => '1100', 'account_name' => 'Clientes', 'normal_balance' => 'debit', 'description' => 'Valores a receber de clientes'],
            ['type_code' => 'CA', 'account_code' => '1200', 'account_name' => 'Inventario', 'normal_balance' => 'debit', 'description' => 'Mercadorias disponiveis para venda'],
            ['type_code' => 'CA', 'account_code' => '1300', 'account_name' => 'Despesas Antecipadas', 'normal_balance' => 'debit', 'description' => 'Gastos pagos antecipadamente'],
            ['type_code' => 'CA', 'account_code' => '1310', 'account_name' => 'Adiantamentos a Fornecedores', 'normal_balance' => 'debit', 'description' => 'Pagamentos adiantados a fornecedores antes da fatura final'],
            ['type_code' => 'CA', 'account_code' => '1320', 'account_name' => 'Adiantamentos a Trabalhadores', 'normal_balance' => 'debit', 'description' => 'Adiantamentos e emprestimos recuperaveis de trabalhadores'],
            ['type_code' => 'OA', 'account_code' => '1400', 'account_name' => 'Depositos', 'normal_balance' => 'debit', 'description' => 'Caucoes pagas'],
            ['type_code' => 'OA', 'account_code' => '1500', 'account_name' => 'IVA a Recuperar', 'normal_balance' => 'debit', 'description' => 'Impostos a recuperar'],
            ['type_code' => 'FA', 'account_code' => '1600', 'account_name' => 'Equipamento', 'normal_balance' => 'debit', 'description' => 'Equipamento administrativo e operacional'],
            ['type_code' => 'FA', 'account_code' => '1610', 'account_name' => 'Depreciacoes Acumuladas - Equipamento', 'normal_balance' => 'credit', 'description' => 'Depreciacoes acumuladas do equipamento'],
            ['type_code' => 'FA', 'account_code' => '1700', 'account_name' => 'Edificios', 'normal_balance' => 'debit', 'description' => 'Imoveis utilizados pela empresa'],
            ['type_code' => 'FA', 'account_code' => '1710', 'account_name' => 'Depreciacoes Acumuladas - Edificios', 'normal_balance' => 'credit', 'description' => 'Depreciacoes acumuladas dos edificios'],
            ['type_code' => 'CL', 'account_code' => '2000', 'account_name' => 'Fornecedores', 'normal_balance' => 'credit', 'description' => 'Valores a pagar a fornecedores'],
            ['type_code' => 'CL', 'account_code' => '2100', 'account_name' => 'Gastos a Pagar', 'normal_balance' => 'credit', 'description' => 'Gastos incorridos ainda nao pagos'],
            ['type_code' => 'CL', 'account_code' => '2200', 'account_name' => 'Imposto sobre o Rendimento a Pagar', 'normal_balance' => 'credit', 'description' => 'Impostos sobre o rendimento a pagar'],
            ['type_code' => 'CL', 'account_code' => '2210', 'account_name' => 'IVA Liquidado', 'normal_balance' => 'credit', 'description' => 'IVA a entregar ao Estado'],
            ['type_code' => 'CL', 'account_code' => '2220', 'account_name' => 'IVA/GST a Pagar', 'normal_balance' => 'credit', 'description' => 'IVA ou GST a entregar ao Estado'],
            ['type_code' => 'CL', 'account_code' => '2300', 'account_name' => 'Emprestimos de Curto Prazo', 'normal_balance' => 'credit', 'description' => 'Emprestimos venciveis ate um ano'],
            ['type_code' => 'CL', 'account_code' => '2350', 'account_name' => 'Adiantamentos de Clientes', 'normal_balance' => 'credit', 'description' => 'Adiantamentos recebidos de clientes'],
            ['type_code' => 'CL', 'account_code' => '2400', 'account_name' => 'Remuneracoes a Pagar', 'normal_balance' => 'credit', 'description' => 'Salarios e beneficios por liquidar'],
            ['type_code' => 'LTL', 'account_code' => '2500', 'account_name' => 'Divida de Longo Prazo', 'normal_balance' => 'credit', 'description' => 'Dividas venciveis apos um ano'],
            ['type_code' => 'SC', 'account_code' => '3100', 'account_name' => 'Capital Social', 'normal_balance' => 'credit', 'description' => 'Capital investido pelos socios'],
            ['type_code' => 'RE', 'account_code' => '3200', 'account_name' => 'Resultados Transitados', 'normal_balance' => 'credit', 'description' => 'Resultados acumulados da empresa'],
            ['type_code' => 'SR', 'account_code' => '4100', 'account_name' => 'Receitas de Vendas', 'normal_balance' => 'credit', 'description' => 'Rendimentos de vendas de produtos'],
            ['type_code' => 'SR', 'account_code' => '4010', 'account_name' => 'Vendas de Produtos', 'normal_balance' => 'credit', 'description' => 'Rendimentos de vendas de produtos'],
            ['type_code' => 'SR', 'account_code' => '4200', 'account_name' => 'Prestacao de Servicos', 'normal_balance' => 'credit', 'description' => 'Rendimentos de servicos prestados'],
            ['type_code' => 'SR', 'account_code' => '4030', 'account_name' => 'Receitas de Consultoria', 'normal_balance' => 'credit', 'description' => 'Rendimentos de servicos de consultoria'],
            ['type_code' => 'SR', 'account_code' => '4040', 'account_name' => 'Receitas de Subscricoes', 'normal_balance' => 'credit', 'description' => 'Rendimentos de servicos por subscricao'],
            ['type_code' => 'OI', 'account_code' => '4110', 'account_name' => 'Rendimentos de Comissoes', 'normal_balance' => 'credit', 'description' => 'Rendimentos obtidos em comissoes'],
            ['type_code' => 'OI', 'account_code' => '4120', 'account_name' => 'Rendimentos de Rendas', 'normal_balance' => 'credit', 'description' => 'Rendimentos de arrendamentos'],
            ['type_code' => 'OI', 'account_code' => '4130', 'account_name' => 'Rendimentos de Manutencao', 'normal_balance' => 'credit', 'description' => 'Rendimentos de servicos de manutencao'],
            ['type_code' => 'OI', 'account_code' => '4140', 'account_name' => 'Rendimentos de Formacao', 'normal_balance' => 'credit', 'description' => 'Rendimentos de acoes de formacao'],
            ['type_code' => 'OI', 'account_code' => '4300', 'account_name' => 'Outros Rendimentos', 'normal_balance' => 'credit', 'description' => 'Outros rendimentos diversos'],
            ['type_code' => 'SR', 'account_code' => '4400', 'account_name' => 'Rendimentos de Projetos', 'normal_balance' => 'credit', 'description' => 'Rendimentos associados a projetos'],
            ['type_code' => 'COGS', 'account_code' => '5100', 'account_name' => 'Custo das Mercadorias Vendidas', 'normal_balance' => 'debit', 'description' => 'Custo direto das mercadorias vendidas'],
            ['type_code' => 'OE', 'account_code' => '5200', 'account_name' => 'Gastos com Salarios', 'normal_balance' => 'debit', 'description' => 'Gastos com remuneracoes'],
            ['type_code' => 'OE', 'account_code' => '5210', 'account_name' => 'Beneficios a Empregados', 'normal_balance' => 'debit', 'description' => 'Gastos com beneficios e seguros de empregados'],
            ['type_code' => 'OE', 'account_code' => '5220', 'account_name' => 'Gastos com Comissoes de Vendas', 'normal_balance' => 'debit', 'description' => 'Comissoes pagas a vendedores'],
            ['type_code' => 'OE', 'account_code' => '5300', 'account_name' => 'Renda', 'normal_balance' => 'debit', 'description' => 'Pagamentos de renda de instalacoes'],
            ['type_code' => 'OE', 'account_code' => '5310', 'account_name' => 'Material de Escritorio', 'normal_balance' => 'debit', 'description' => 'Material de escritorio de uso corrente'],
            ['type_code' => 'OE', 'account_code' => '5320', 'account_name' => 'Gastos de Marketing', 'normal_balance' => 'debit', 'description' => 'Gastos de marketing e publicidade'],
            ['type_code' => 'OE', 'account_code' => '5330', 'account_name' => 'Gastos de Viagem', 'normal_balance' => 'debit', 'description' => 'Gastos de deslocacao em servico'],
            ['type_code' => 'AE', 'account_code' => '5400', 'account_name' => 'Utilidades', 'normal_balance' => 'debit', 'description' => 'Agua, eletricidade e internet'],
            ['type_code' => 'AE', 'account_code' => '5410', 'account_name' => 'Seguros', 'normal_balance' => 'debit', 'description' => 'Premios de seguro da empresa'],
            ['type_code' => 'AE', 'account_code' => '5420', 'account_name' => 'Honorarios Profissionais', 'normal_balance' => 'debit', 'description' => 'Honorarios juridicos e contabilisticos'],
            ['type_code' => 'AE', 'account_code' => '5430', 'account_name' => 'Gastos de Depreciacao', 'normal_balance' => 'debit', 'description' => 'Depreciacao do ativo fixo'],
            ['type_code' => 'FE', 'account_code' => '5500', 'account_name' => 'Juros Suportados', 'normal_balance' => 'debit', 'description' => 'Juros de emprestimos e financiamentos'],
            ['type_code' => 'FE', 'account_code' => '5510', 'account_name' => 'Encargos Bancarios', 'normal_balance' => 'debit', 'description' => 'Comissoes e encargos bancarios'],
            ['type_code' => 'TE', 'account_code' => '5600', 'account_name' => 'Gastos com Impostos', 'normal_balance' => 'debit', 'description' => 'Gastos com imposto sobre o rendimento'],
            ['type_code' => 'OX', 'account_code' => '5700', 'account_name' => 'Perdas por Incobraveis', 'normal_balance' => 'debit', 'description' => 'Perdas com clientes incobraveis'],
            ['type_code' => 'OX', 'account_code' => '5800', 'account_name' => 'Gastos Diversos', 'normal_balance' => 'debit', 'description' => 'Outros gastos diversos'],
        ];
    }

    public static function localizedAccountCategoryName(?string $code, ?string $fallback = null, ?string $locale = null): ?string
    {
        return self::resolveDefinitionValue(self::accountCategoryDefinitions(self::normalizeCatalogLocale($locale)), 'code', $code, 'name', $fallback);
    }

    public static function localizedAccountTypeName(?string $code, ?string $fallback = null, ?string $locale = null): ?string
    {
        return self::resolveDefinitionValue(self::accountTypeDefinitions(self::normalizeCatalogLocale($locale)), 'code', $code, 'name', $fallback);
    }

    public static function localizedChartOfAccountName(?string $accountCode, ?string $fallback = null, ?string $locale = null): ?string
    {
        return self::resolveDefinitionValue(self::chartOfAccountDefinitions(self::normalizeCatalogLocale($locale)), 'account_code', $accountCode, 'account_name', $fallback);
    }

    private static function resolveDefinitionValue(array $definitions, string $lookupKey, ?string $lookupValue, string $valueKey, ?string $fallback = null): ?string
    {
        if (!$lookupValue) {
            return $fallback;
        }

        foreach ($definitions as $definition) {
            if (($definition[$lookupKey] ?? null) === $lookupValue) {
                return $definition[$valueKey] ?? $fallback;
            }
        }

        return $fallback;
    }

    private static function normalizeCatalogLocale(?string $locale = null): string
    {
        $locale = strtolower($locale ?: app()->getLocale());

        return str_starts_with($locale, 'pt') ? 'pt' : 'en';
    }

    private static function createAccountCategories($company_id)
    {
        $exist = AccountCategory::where('created_by', $company_id)->first();
        if($exist) return;

        $categories = self::accountCategoryDefinitions();

        foreach ($categories as $category) {
            $category['creator_id'] = $company_id;
            $category['created_by'] = $company_id;
            AccountCategory::create($category);
        }
    }

    private static function createAccountTypes($company_id)
    {
        $exist = AccountType::where('created_by', $company_id)->first();
        if($exist) return;

        $categories = AccountCategory::where('created_by', $company_id)->get()->keyBy('code');
        if(count($categories) == 0) return;

        $accountTypes = self::accountTypeDefinitions();

        foreach ($accountTypes as $type) {
            $categoryCode = $type['category_code'];
            unset($type['category_code']);

            if (isset($categories[$categoryCode])) {
                $type['category_id'] = $categories[$categoryCode]->id;
                $type['is_system_type'] = 1;
                $type['creator_id'] = $company_id;
                $type['created_by'] = $company_id;
                AccountType::create($type);
            }
        }
    }

    private static function createChartOfAccounts($company_id)
    {
        $exist = ChartOfAccount::where('created_by', $company_id)->first();
        if($exist) return;

        $accountTypes = AccountType::where('created_by', $company_id)->get()->keyBy('code');
        if(count($accountTypes) == 0) return;

        $chartOfAccounts = self::chartOfAccountDefinitions();

        foreach ($chartOfAccounts as $account) {
            $typeCode = $account['type_code'];
            unset($account['type_code']);

            if (isset($accountTypes[$typeCode])) {
                $account['account_type_id'] = $accountTypes[$typeCode]->id;
                $account['is_system_account'] = 1;
                $account['creator_id'] = $company_id;
                $account['created_by'] = $company_id;
                ChartOfAccount::create($account);
            }
        }
    }


    public static function GivePermissionToVendor($company_id = null)
    {
        $vendor_permission = [
            'manage-dashboard',
            'manage-account',
            'manage-account-dashboard',
            'manage-vendor-payments',
            'manage-own-vendor-payments',
            'view-vendor-payments',
            'manage-debit-notes',
            'manage-own-debit-notes',
            'view-debit-notes',
        ];

        $vendor_role = Role::where('name', 'vendor')->where('created_by', $company_id)->first();
        self::syncPermissionsByName($vendor_role, $vendor_permission);
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $client_permission = [
            'manage-dashboard',
            'manage-account',
            'manage-account-dashboard',
            'manage-customer-payments',
            'manage-own-customer-payments',
            'view-customer-payments',
            'manage-credit-notes',
            'manage-own-credit-notes',
            'view-credit-notes'
        ];

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $client_permission);
        }
    }

    private static function syncPermissionsByName(?Role $role, array $permissionNames): void
    {
        if (!$role || empty($permissionNames)) {
            return;
        }

        $permissions = Permission::whereIn('name', array_values(array_unique($permissionNames)))->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
    }
}
