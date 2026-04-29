<?php

namespace Workdo\Account\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            ['name' => 'manage-account', 'module' => 'account', 'label' => 'Manage Account'],
            ['name' => 'manage-account-dashboard', 'module' => 'account', 'label' => 'Manage Account Dashboard'],

            // vendor's permission
            ['name' => 'manage-vendors', 'module' => 'vendors', 'label' => 'Manage Vendors'],
            ['name' => 'manage-any-vendors', 'module' => 'vendors', 'label' => 'Manage All Vendors'],
            ['name' => 'manage-own-vendors', 'module' => 'vendors', 'label' => 'Manage Own Vendors'],
            ['name' => 'view-vendors', 'module' => 'vendors', 'label' => 'View Vendors'],
            ['name' => 'create-vendors', 'module' => 'vendors', 'label' => 'Create Vendors'],
            ['name' => 'edit-vendors', 'module' => 'vendors', 'label' => 'Edit Vendors'],
            ['name' => 'delete-vendors', 'module' => 'vendors', 'label' => 'Delete Vendors'],

            // customer's permissions
            ['name' => 'manage-customers', 'module' => 'customers', 'label' => 'Manage Customers'],
            ['name' => 'manage-any-customers', 'module' => 'customers', 'label' => 'Manage All Customers'],
            ['name' => 'manage-own-customers', 'module' => 'customers', 'label' => 'Manage Own Customers'],
            ['name' => 'view-customers', 'module' => 'customers', 'label' => 'View Customers'],
            ['name' => 'create-customers', 'module' => 'customers', 'label' => 'Create Customers'],
            ['name' => 'edit-customers', 'module' => 'customers', 'label' => 'Edit Customers'],
            ['name' => 'delete-customers', 'module' => 'customers', 'label' => 'Delete Customers'],

            // BankAccount management
            ['name' => 'manage-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Manage BankAccounts'],
            ['name' => 'manage-any-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Manage All BankAccounts'],
            ['name' => 'manage-own-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Manage Own BankAccounts'],
            ['name' => 'view-bank-accounts', 'module' => 'bank-accounts', 'label' => 'View BankAccounts'],
            ['name' => 'create-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Create BankAccounts'],
            ['name' => 'edit-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Edit BankAccounts'],
            ['name' => 'delete-bank-accounts', 'module' => 'bank-accounts', 'label' => 'Delete BankAccounts'],

            // AccountType management
            ['name' => 'manage-account-types', 'module' => 'account-types', 'label' => 'Manage AccountTypes'],
            ['name' => 'manage-any-account-types', 'module' => 'account-types', 'label' => 'Manage All AccountTypes'],
            ['name' => 'manage-own-account-types', 'module' => 'account-types', 'label' => 'Manage Own AccountTypes'],
            ['name' => 'view-account-types', 'module' => 'account-types', 'label' => 'View AccountTypes'],
            ['name' => 'create-account-types', 'module' => 'account-types', 'label' => 'Create AccountTypes'],
            ['name' => 'edit-account-types', 'module' => 'account-types', 'label' => 'Edit AccountTypes'],
            ['name' => 'delete-account-types', 'module' => 'account-types', 'label' => 'Delete AccountTypes'],

            // ChartOfAccount management
            ['name' => 'manage-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Manage ChartOfAccounts'],
            ['name' => 'manage-any-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Manage All ChartOfAccounts'],
            ['name' => 'manage-own-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Manage Own ChartOfAccounts'],
            ['name' => 'view-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'View ChartOfAccounts'],
            ['name' => 'create-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Create ChartOfAccounts'],
            ['name' => 'edit-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Edit ChartOfAccounts'],
            ['name' => 'delete-chart-of-accounts', 'module' => 'chart-of-accounts', 'label' => 'Delete ChartOfAccounts'],

            // VendorPayment management
            ['name' => 'manage-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Manage Vendor Payments'],
            ['name' => 'manage-any-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Manage All Vendor Payments'],
            ['name' => 'manage-own-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Manage Own Vendor Payments'],
            ['name' => 'view-vendor-payments', 'module' => 'vendor-payments', 'label' => 'View Vendor Payments'],
            ['name' => 'create-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Create Vendor Payments'],
            ['name' => 'cleared-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Cleared Vendor Payments'],
            ['name' => 'delete-vendor-payments', 'module' => 'vendor-payments', 'label' => 'Delete Vendor Payments'],

            // CustomerPayment management
            ['name' => 'manage-customer-payments', 'module' => 'customer-payments', 'label' => 'Manage Customer Payments'],
            ['name' => 'manage-any-customer-payments', 'module' => 'customer-payments', 'label' => 'Manage All Customer Payments'],
            ['name' => 'manage-own-customer-payments', 'module' => 'customer-payments', 'label' => 'Manage Own Customer Payments'],
            ['name' => 'view-customer-payments', 'module' => 'customer-payments', 'label' => 'View Customer Payments'],
            ['name' => 'create-customer-payments', 'module' => 'customer-payments', 'label' => 'Create Customer Payments'],
            ['name' => 'cleared-customer-payments', 'module' => 'customer-payments', 'label' => 'Clear Customer Payments'],
            ['name' => 'delete-customer-payments', 'module' => 'customer-payments', 'label' => 'Delete Customer Payments'],

            // BankTransaction Management
            ['name' => 'manage-bank-transactions', 'module' => 'bank-transaction', 'label' => 'Manage Bank Transaction'],
            ['name' => 'reconcile-bank-transactions', 'module' => 'bank-transaction', 'label' => 'Reconcile Bank Transaction'],

            // DebitNote management
            ['name' => 'manage-debit-notes', 'module' => 'debit-notes', 'label' => 'Manage Debit Notes'],
            ['name' => 'manage-any-debit-notes', 'module' => 'debit-notes', 'label' => 'Manage All Debit Notes'],
            ['name' => 'manage-own-debit-notes', 'module' => 'debit-notes', 'label' => 'Manage Own Debit Notes'],
            ['name' => 'view-debit-notes', 'module' => 'debit-notes', 'label' => 'View Debit Notes'],
            ['name' => 'create-debit-notes', 'module' => 'debit-notes', 'label' => 'Create Debit Notes'],
            ['name' => 'approve-debit-notes', 'module' => 'debit-notes', 'label' => 'Approve Debit Notes'],
            ['name' => 'delete-debit-notes', 'module' => 'debit-notes', 'label' => 'Delete Debit Notes'],

            // CreditNote management
            ['name' => 'manage-credit-notes', 'module' => 'credit-notes', 'label' => 'Manage Credit Notes'],
            ['name' => 'manage-any-credit-notes', 'module' => 'credit-notes', 'label' => 'Manage All Credit Notes'],
            ['name' => 'manage-own-credit-notes', 'module' => 'credit-notes', 'label' => 'Manage Own Credit Notes'],
            ['name' => 'view-credit-notes', 'module' => 'credit-notes', 'label' => 'View Credit Notes'],
            ['name' => 'create-credit-notes', 'module' => 'credit-notes', 'label' => 'Create Credit Notes'],
            ['name' => 'approve-credit-notes', 'module' => 'credit-notes', 'label' => 'Approve Credit Notes'],
            ['name' => 'delete-credit-notes', 'module' => 'credit-notes', 'label' => 'Delete Credit Notes'],

            // BankTransfer management
            ['name' => 'manage-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Manage Bank Transfers'],
            ['name' => 'manage-any-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Manage All Bank Transfers'],
            ['name' => 'manage-own-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Manage Own Bank Transfers'],
            ['name' => 'view-bank-transfers', 'module' => 'bank-transfers', 'label' => 'View Bank Transfers'],
            ['name' => 'create-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Create Bank Transfers'],
            ['name' => 'edit-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Edit Bank Transfers'],
            ['name' => 'delete-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Delete Bank Transfers'],
            ['name' => 'process-bank-transfers', 'module' => 'bank-transfers', 'label' => 'Process Bank Transfers'],

            // RevenueCategories management
            ['name' => 'manage-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Manage RevenueCategories'],
            ['name' => 'manage-any-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Manage All RevenueCategories'],
            ['name' => 'manage-own-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Manage Own RevenueCategories'],
            ['name' => 'create-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Create RevenueCategories'],
            ['name' => 'edit-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Edit RevenueCategories'],
            ['name' => 'delete-revenue-categories', 'module' => 'revenue-categories', 'label' => 'Delete RevenueCategories'],

            // ExpenseCategories management
            ['name' => 'manage-expense-categories', 'module' => 'expense-categories', 'label' => 'Manage ExpenseCategories'],
            ['name' => 'manage-any-expense-categories', 'module' => 'expense-categories', 'label' => 'Manage All ExpenseCategories'],
            ['name' => 'manage-own-expense-categories', 'module' => 'expense-categories', 'label' => 'Manage Own ExpenseCategories'],
            ['name' => 'create-expense-categories', 'module' => 'expense-categories', 'label' => 'Create ExpenseCategories'],
            ['name' => 'edit-expense-categories', 'module' => 'expense-categories', 'label' => 'Edit ExpenseCategories'],
            ['name' => 'delete-expense-categories', 'module' => 'expense-categories', 'label' => 'Delete ExpenseCategories'],

            // Revenue
            ['name' => 'manage-revenues', 'module' => 'revenues', 'label' => 'Manage Revenues'],
            ['name' => 'manage-any-revenues', 'module' => 'revenues', 'label' => 'Manage All Revenues'],
            ['name' => 'manage-own-revenues', 'module' => 'revenues', 'label' => 'Manage Own Revenues'],
            ['name' => 'view-revenues', 'module' => 'revenues', 'label' => 'View Revenues'],
            ['name' => 'create-revenues', 'module' => 'revenues', 'label' => 'Create Revenues'],
            ['name' => 'edit-revenues', 'module' => 'revenues', 'label' => 'Edit Revenues'],
            ['name' => 'delete-revenues', 'module' => 'revenues', 'label' => 'Delete Revenues'],
            ['name' => 'approve-revenues', 'module' => 'revenues', 'label' => 'Approve Revenues'],
            ['name' => 'post-revenues', 'module' => 'revenues', 'label' => 'Post Revenues'],

            // Expense
            ['name' => 'manage-expenses', 'module' => 'expenses', 'label' => 'Manage Expenses'],
            ['name' => 'manage-any-expenses', 'module' => 'expenses', 'label' => 'Manage All Expenses'],
            ['name' => 'manage-own-expenses', 'module' => 'expenses', 'label' => 'Manage Own Expenses'],
            ['name' => 'view-expenses', 'module' => 'expenses', 'label' => 'View Expenses'],
            ['name' => 'create-expenses', 'module' => 'expenses', 'label' => 'Create Expenses'],
            ['name' => 'edit-expenses', 'module' => 'expenses', 'label' => 'Edit Expenses'],
            ['name' => 'delete-expenses', 'module' => 'expenses', 'label' => 'Delete Expenses'],
            ['name' => 'approve-expenses', 'module' => 'expenses', 'label' => 'Approve Expenses'],
            ['name' => 'post-expenses', 'module' => 'expenses', 'label' => 'Post Expenses'],

            // Reports
            ['name' => 'manage-account-reports', 'module' => 'account-reports', 'label' => 'Manage Account Reports'],
            ['name' => 'view-invoice-aging', 'module' => 'account-reports', 'label' => 'View Invoice Aging'],
            ['name' => 'print-invoice-aging', 'module' => 'account-reports', 'label' => 'Print Invoice Aging'],
            ['name' => 'view-bill-aging', 'module' => 'account-reports', 'label' => 'View Bill Aging'],
            ['name' => 'print-bill-aging', 'module' => 'account-reports', 'label' => 'Print Bill Aging'],
            ['name' => 'view-tax-summary', 'module' => 'account-reports', 'label' => 'View Tax Summary'],
            ['name' => 'print-tax-summary', 'module' => 'account-reports', 'label' => 'Print Tax Summary'],
            ['name' => 'view-customer-balance', 'module' => 'account-reports', 'label' => 'View Customer Balance'],
            ['name' => 'print-customer-balance', 'module' => 'account-reports', 'label' => 'Print Customer Balance'],
            ['name' => 'view-vendor-balance', 'module' => 'account-reports', 'label' => 'View Vendor Balance'],
            ['name' => 'print-vendor-balance', 'module' => 'account-reports', 'label' => 'Print Vendor Balance'],
            ['name' => 'view-customer-detail-report', 'module' => 'account-reports', 'label' => 'View Customer Detail Report'],
            ['name' => 'print-customer-detail-report', 'module' => 'account-reports', 'label' => 'Print Customer Detail Report'],
            ['name' => 'view-vendor-detail-report', 'module' => 'account-reports', 'label' => 'View Vendor Detail Report'],
            ['name' => 'print-vendor-detail-report', 'module' => 'account-reports', 'label' => 'Print Vendor Detail Report'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Account',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}
