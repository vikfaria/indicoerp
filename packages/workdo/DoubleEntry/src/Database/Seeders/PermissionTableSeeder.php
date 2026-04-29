<?php

namespace Workdo\DoubleEntry\Database\Seeders;

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
            ['name' => 'manage-double-entry', 'module' => 'double-entry', 'label' => 'Manage Double Entry'],

            // Balance Sheet management
            ['name' => 'manage-balance-sheets', 'module' => 'balance-sheets', 'label' => 'Manage Balance Sheets'],
            ['name' => 'view-balance-sheets', 'module' => 'balance-sheets', 'label' => 'View Balance Sheets'],
            ['name' => 'create-balance-sheets', 'module' => 'balance-sheets', 'label' => 'Create Balance Sheets'],
            ['name' => 'finalize-balance-sheets', 'module' => 'balance-sheets', 'label' => 'Finalize Balance Sheets'],
            ['name' => 'delete-balance-sheets', 'module' => 'balance-sheets', 'label' => 'Delete Balance Sheets'],
            ['name' => 'print-balance-sheets', 'module' => 'balance-sheets', 'label' => 'Print Balance Sheets'],
            ['name' => 'year-end-close', 'module' => 'balance-sheets', 'label' => 'Year-End Close'],

            ['name' => 'create-balance-sheet-notes', 'module' => 'balance-sheet-notes', 'label' => 'Create Balance Sheet Notes'],
            ['name' => 'delete-balance-sheet-notes', 'module' => 'balance-sheet-notes', 'label' => 'Delete Balance Sheet Notes'],

            ['name' => 'create-balance-sheet-comparisons', 'module' => 'balance-sheet-comparisons', 'label' => 'Create Balance Sheet Comparisons'],
            ['name' => 'view-balance-sheet-comparisons', 'module' => 'balance-sheet-comparisons', 'label' => 'View Balance Sheet Comparisons'],

            ['name' => 'manage-ledger-summary', 'module' => 'ledger-summary', 'label' => 'Manage Ledger Summary'],
            ['name' => 'print-ledger-summary', 'module' => 'ledger-summary', 'label' => 'Print Ledger Summary'],

            ['name' => 'manage-profit-loss', 'module' => 'profit-loss', 'label' => 'Manage Profit & Loss'],
            ['name' => 'print-profit-loss', 'module' => 'profit-loss', 'label' => 'Print Profit & Loss'],

            ['name' => 'manage-trial-balance', 'module' => 'trial-balance', 'label' => 'Manage Trial Balance'],
            ['name' => 'print-trial-balance', 'module' => 'trial-balance', 'label' => 'Print Trial Balance'],

            ['name' => 'manage-double-entry-reports', 'module' => 'reports', 'label' => 'Manage Reports'],
            ['name' => 'view-general-ledger', 'module' => 'reports', 'label' => 'View General Ledger'],
            ['name' => 'view-account-statement', 'module' => 'reports', 'label' => 'View Account Statement'],
            ['name' => 'view-journal-entry', 'module' => 'reports', 'label' => 'View Journal Entry'],
            ['name' => 'view-account-balance', 'module' => 'reports', 'label' => 'View Account Balance'],

            ['name' => 'view-cash-flow', 'module' => 'reports', 'label' => 'View Cash Flow'],
            ['name' => 'view-expense-report', 'module' => 'reports', 'label' => 'View Expense Report'],
            ['name' => 'print-general-ledger', 'module' => 'reports', 'label' => 'Print General Ledger'],
            ['name' => 'print-account-statement', 'module' => 'reports', 'label' => 'Print Account Statement'],
            ['name' => 'print-journal-entry', 'module' => 'reports', 'label' => 'Print Journal Entry'],
            ['name' => 'print-account-balance', 'module' => 'reports', 'label' => 'Print Account Balance'],

            ['name' => 'print-cash-flow', 'module' => 'reports', 'label' => 'Print Cash Flow'],
            ['name' => 'print-expense-report', 'module' => 'reports', 'label' => 'Print Expense Report'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'DoubleEntry',
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