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

    private static function createAccountCategories($company_id)
    {
        $exist = AccountCategory::where('created_by', $company_id)->first();
        if($exist) return;

        $categories = [
            ['name' => 'Assets', 'code' => 'AST', 'type' => 'assets', 'description' => 'Resources owned by the company'],
            ['name' => 'Liabilities', 'code' => 'LIB', 'type' => 'liabilities', 'description' => 'Debts and obligations of the company'],
            ['name' => 'Equity', 'code' => 'EQT', 'type' => 'equity', 'description' => 'Owner\'s equity in the company'],
            ['name' => 'Revenue', 'code' => 'REV', 'type' => 'revenue', 'description' => 'Income generated from business operations'],
            ['name' => 'Expenses', 'code' => 'EXP', 'type' => 'expenses', 'description' => 'Costs incurred in business operations']
        ];

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

        $accountTypes = [
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
            ['category_code' => 'EXP', 'name' => 'Other Expenses', 'code' => 'OX', 'normal_balance' => 'debit', 'description' => 'Miscellaneous expenses']
        ];

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

        $chartOfAccounts = [
            // Current Assets - Cash & Bank
            ['type_code' => 'CA', 'account_code' => '1000', 'account_name' => 'Cash', 'normal_balance' => 'debit', 'description' => 'Physical cash in office'],
            ['type_code' => 'CA', 'account_code' => '1005', 'account_name' => 'Petty Cash', 'normal_balance' => 'debit', 'description' => 'Small cash for minor expenses'],
            ['type_code' => 'CA', 'account_code' => '1010', 'account_name' => 'Bank Account - Main', 'normal_balance' => 'debit', 'description' => 'Primary bank checking account'],
            ['type_code' => 'CA', 'account_code' => '1020', 'account_name' => 'Bank Account - Savings', 'normal_balance' => 'debit', 'description' => 'Business savings account'],
            ['type_code' => 'CA', 'account_code' => '1030', 'account_name' => 'Bank Account - Payroll', 'normal_balance' => 'debit', 'description' => 'Dedicated payroll account'],
            ['type_code' => 'CA', 'account_code' => '1040', 'account_name' => 'Cash in Transit', 'normal_balance' => 'debit', 'description' => 'Cash being transferred between accounts'],
            ['type_code' => 'CA', 'account_code' => '1100', 'account_name' => 'Accounts Receivable', 'normal_balance' => 'debit', 'description' => 'Money owed by customers'],
            ['type_code' => 'CA', 'account_code' => '1200', 'account_name' => 'Inventory', 'normal_balance' => 'debit', 'description' => 'Goods held for sale'],
            ['type_code' => 'CA', 'account_code' => '1300', 'account_name' => 'Prepaid Expenses', 'normal_balance' => 'debit', 'description' => 'Expenses paid in advance'],
            // Other Assets
            ['type_code' => 'OA', 'account_code' => '1400', 'account_name' => 'Deposits', 'normal_balance' => 'debit', 'description' => 'Security deposits paid'],
            ['type_code' => 'OA', 'account_code' => '1500', 'account_name' => 'Tax Receivable (VAT/GST Input)', 'normal_balance' => 'debit', 'description' => 'Tax refunds due'],
            // Fixed Assets
            ['type_code' => 'FA', 'account_code' => '1600', 'account_name' => 'Equipment', 'normal_balance' => 'debit', 'description' => 'Office and business equipment'],
            ['type_code' => 'FA', 'account_code' => '1610', 'account_name' => 'Accumulated Depreciation - Equipment', 'normal_balance' => 'credit', 'description' => 'Accumulated depreciation on equipment'],
            ['type_code' => 'FA', 'account_code' => '1700', 'account_name' => 'Buildings', 'normal_balance' => 'debit', 'description' => 'Building assets'],
            ['type_code' => 'FA', 'account_code' => '1710', 'account_name' => 'Accumulated Depreciation - Buildings', 'normal_balance' => 'credit', 'description' => 'Accumulated depreciation on buildings'],
            // Current Liabilities
            ['type_code' => 'CL', 'account_code' => '2000', 'account_name' => 'Accounts Payable', 'normal_balance' => 'credit', 'description' => 'Money owed to suppliers'],
            ['type_code' => 'CL', 'account_code' => '2100', 'account_name' => 'Accrued Expenses', 'normal_balance' => 'credit', 'description' => 'Expenses incurred but not yet paid'],
            ['type_code' => 'CL', 'account_code' => '2200', 'account_name' => 'Tax Payable (Income Tax)', 'normal_balance' => 'credit', 'description' => 'Taxes owed'],
            ['type_code' => 'CL', 'account_code' => '2210', 'account_name' => 'VAT Payable (Sales Tax Output)', 'normal_balance' => 'credit', 'description' => 'VAT owed to government'],
            ['type_code' => 'CL', 'account_code' => '2220', 'account_name' => 'GST Payable', 'normal_balance' => 'credit', 'description' => 'GST owed to government'],
            ['type_code' => 'CL', 'account_code' => '2300', 'account_name' => 'Short-term Loans', 'normal_balance' => 'credit', 'description' => 'Loans due within one year'],
            ['type_code' => 'CL', 'account_code' => '2350', 'account_name' => 'Customer Deposits', 'normal_balance' => 'credit', 'description' => 'Advance payments from customers for future services'],
            ['type_code' => 'CL', 'account_code' => '2400', 'account_name' => 'Payroll Liabilities', 'normal_balance' => 'credit', 'description' => 'Unpaid employee salaries and benefits'],
            // Long-term Liabilities
            ['type_code' => 'LTL', 'account_code' => '2500', 'account_name' => 'Long-term Debt', 'normal_balance' => 'credit', 'description' => 'Debts due after one year'],
            // Equity
            ['type_code' => 'SC', 'account_code' => '3100', 'account_name' => 'Share Capital', 'normal_balance' => 'credit', 'description' => 'Owner\'s investment in business'],
            ['type_code' => 'RE', 'account_code' => '3200', 'account_name' => 'Retained Earnings', 'normal_balance' => 'credit', 'description' => 'Accumulated business profits'],
            // Revenue
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
            // Expenses
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
            ['type_code' => 'OX', 'account_code' => '5800', 'account_name' => 'Miscellaneous Expense', 'normal_balance' => 'debit', 'description' => 'Other miscellaneous expenses']
        ];

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
        foreach ($vendor_permission as $permission_v) {
            $permission = Permission::where('name', $permission_v)->first();
            if (!empty($permission)) {
                if (!$vendor_role->hasPermissionTo($permission_v)) {
                    $vendor_role->givePermissionTo($permission);
                }
            }
        }
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
            foreach ($client_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }
    }
}
