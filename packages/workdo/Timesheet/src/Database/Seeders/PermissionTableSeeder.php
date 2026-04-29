<?php

namespace Workdo\Timesheet\Database\Seeders;

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
            ['name' => 'manage-timesheet', 'module' => 'timesheet', 'label' => 'Manage Timesheet'],
            ['name' => 'manage-any-timesheet', 'module' => 'timesheet', 'label' => 'Manage All Timesheet'],
            ['name' => 'manage-own-timesheet', 'module' => 'timesheet', 'label' => 'Manage Own Timesheet'],
            ['name' => 'create-timesheet', 'module' => 'timesheet', 'label' => 'Create Timesheet'],
            ['name' => 'edit-timesheet', 'module' => 'timesheet', 'label' => 'Edit Timesheet'],
            ['name' => 'delete-timesheet', 'module' => 'timesheet', 'label' => 'Delete Timesheet'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Timesheet',
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