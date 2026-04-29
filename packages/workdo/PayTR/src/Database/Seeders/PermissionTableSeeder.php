<?php

namespace Workdo\PayTR\Database\Seeders;

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
            ['name' => 'edit-paytr-settings', 'module' => 'paytr', 'label' => 'Edit PayTR Settings'],
            ['name' => 'manage-paytr-settings', 'module' => 'paytr', 'label' => 'Manage PayTR Settings'],
        ];

        $superadminRole = Role::where('name', 'superadmin')->first();
        $companyRole = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'PayTR',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($superadminRole && !$superadminRole->hasPermissionTo($permission_obj)) {
                $superadminRole->givePermissionTo($permission_obj);
            }

            if ($companyRole && !$companyRole->hasPermissionTo($permission_obj)) {
                $companyRole->givePermissionTo($permission_obj);
            }
        }
    }
}