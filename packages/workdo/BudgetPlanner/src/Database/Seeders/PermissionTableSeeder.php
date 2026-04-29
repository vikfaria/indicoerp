<?php

namespace Workdo\BudgetPlanner\Database\Seeders;

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
            ['name' => 'manage-budget-planner', 'module' => 'budget-planner', 'label' => 'Manage BudgetPlanner'],

            // BudgetPeriod management
            ['name' => 'manage-budget-periods', 'module' => 'budget-periods', 'label' => 'Manage BudgetPeriods'],
            ['name' => 'manage-any-budget-periods', 'module' => 'budget-periods', 'label' => 'Manage All BudgetPeriods'],
            ['name' => 'manage-own-budget-periods', 'module' => 'budget-periods', 'label' => 'Manage Own BudgetPeriods'],
            ['name' => 'create-budget-periods', 'module' => 'budget-periods', 'label' => 'Create BudgetPeriods'],
            ['name' => 'edit-budget-periods', 'module' => 'budget-periods', 'label' => 'Edit BudgetPeriods'],
            ['name' => 'delete-budget-periods', 'module' => 'budget-periods', 'label' => 'Delete BudgetPeriods'],
            ['name' => 'approve-budget-periods', 'module' => 'budget-periods', 'label' => 'Approve BudgetPeriods'],
            ['name' => 'active-budget-periods', 'module' => 'budget-periods', 'label' => 'Active BudgetPeriods'],
            ['name' => 'close-budget-periods', 'module' => 'budget-periods', 'label' => 'Close BudgetPeriods'],

            // Budgets management
            ['name' => 'manage-budgets', 'module' => 'budgets', 'label' => 'Manage Budgets'],
            ['name' => 'manage-any-budgets', 'module' => 'budgets', 'label' => 'Manage All Budgets'],
            ['name' => 'manage-own-budgets', 'module' => 'budgets', 'label' => 'Manage Own Budgets'],
            ['name' => 'create-budgets', 'module' => 'budgets', 'label' => 'Create Budgets'],
            ['name' => 'edit-budgets', 'module' => 'budgets', 'label' => 'Edit Budgets'],
            ['name' => 'delete-budgets', 'module' => 'budgets', 'label' => 'Delete Budgets'],
            ['name' => 'approve-budgets', 'module' => 'budgets', 'label' => 'Approve Budgets'],
            ['name' => 'active-budgets', 'module' => 'budgets', 'label' => 'Active Budgets'],
            ['name' => 'close-budgets', 'module' => 'budgets', 'label' => 'Close Budgets'],

            // Budget Allocation management
            ['name' => 'manage-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Manage Budget Allocations'],
            ['name' => 'manage-any-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Manage All Budget Allocations'],
            ['name' => 'manage-own-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Manage Own Budget Allocations'],
            ['name' => 'create-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Create Budget Allocations'],
            ['name' => 'edit-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Edit Budget Allocations'],
            ['name' => 'delete-budget-allocations', 'module' => 'budget-allocations', 'label' => 'Delete Budget Allocations'],

            // Budget Monitoring management
            ['name' => 'manage-budget-monitoring', 'module' => 'budget-monitoring', 'label' => 'Manage Budget Monitoring'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'BudgetPlanner',
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
