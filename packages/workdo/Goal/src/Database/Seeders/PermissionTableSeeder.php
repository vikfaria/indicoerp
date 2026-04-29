<?php

namespace Workdo\Goal\Database\Seeders;

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
            ['name' => 'manage-goal', 'module' => 'goal', 'label' => 'Manage Goal'],

            // Category management
            ['name' => 'manage-categories', 'module' => 'categories', 'label' => 'Manage Categories'],
            ['name' => 'manage-any-categories', 'module' => 'categories', 'label' => 'Manage All Categories'],
            ['name' => 'manage-own-categories', 'module' => 'categories', 'label' => 'Manage Own Categories'],
            ['name' => 'create-categories', 'module' => 'categories', 'label' => 'Create Categories'],
            ['name' => 'edit-categories', 'module' => 'categories', 'label' => 'Edit Categories'],
            ['name' => 'delete-categories', 'module' => 'categories', 'label' => 'Delete Categories'],

            // Goal management
            ['name' => 'manage-goals', 'module' => 'goals', 'label' => 'Manage Goals'],
            ['name' => 'manage-any-goals', 'module' => 'goals', 'label' => 'Manage All Goals'],
            ['name' => 'manage-own-goals', 'module' => 'goals', 'label' => 'Manage Own Goals'],
            ['name' => 'view-goals', 'module' => 'goals', 'label' => 'View Goals'],
            ['name' => 'create-goals', 'module' => 'goals', 'label' => 'Create Goals'],
            ['name' => 'edit-goals', 'module' => 'goals', 'label' => 'Edit Goals'],
            ['name' => 'delete-goals', 'module' => 'goals', 'label' => 'Delete Goals'],
            ['name' => 'active-goals', 'module' => 'goals', 'label' => 'Active Goals'],

            // Goal Milestone management
            ['name' => 'manage-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Manage Goal Milestones'],
            ['name' => 'manage-any-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Manage All Goal Milestones'],
            ['name' => 'manage-own-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Manage Own Goal Milestones'],
            ['name' => 'create-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Create Goal Milestones'],
            ['name' => 'edit-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Edit Goal Milestones'],
            ['name' => 'delete-goal-milestones', 'module' => 'goal-milestones', 'label' => 'Delete Goal Milestones'],

            // Goal Contributions management
            ['name' => 'manage-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Manage Goal Contributions'],
            ['name' => 'manage-any-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Manage All Goal Contributions'],
            ['name' => 'manage-own-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Manage Own Goal Contributions'],
            ['name' => 'create-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Create Goal Contributions'],
            ['name' => 'edit-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Edit Goal Contributions'],
            ['name' => 'delete-goal-contributions', 'module' => 'goal-contributions', 'label' => 'Delete Goal Contributions'],

            // Goal Tracking management
            ['name' => 'manage-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Manage Goal Tracking'],
            ['name' => 'manage-any-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Manage All Goal Tracking'],
            ['name' => 'manage-own-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Manage Own Goal Tracking'],
            ['name' => 'view-goal-tracking', 'module' => 'goal-tracking', 'label' => 'View Goal Tracking'],
            ['name' => 'create-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Create Goal Tracking'],
            ['name' => 'edit-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Edit Goal Tracking'],
            ['name' => 'delete-goal-tracking', 'module' => 'goal-tracking', 'label' => 'Delete Goal Tracking'],

        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Goal',
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
