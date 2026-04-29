<?php

namespace Workdo\Paypal\Database\Seeders;

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
            ['name' => 'edit-paypal-settings', 'module' => 'paypal', 'label' => 'Edit PayPal Settings'],
            ['name' => 'manage-paypal-settings', 'module' => 'paypal', 'label' => 'Manage PayPal Settings'],
        ];

        $companyRole = Role::where('name', 'company')->first();
        $superadminRole = Role::where('name', 'superadmin')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Paypal',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($companyRole && !$companyRole->hasPermissionTo($permission_obj)) {
                $companyRole->givePermissionTo($permission_obj);
            }

            if ($superadminRole && !$superadminRole->hasPermissionTo($permission_obj)) {
                $superadminRole->givePermissionTo($permission_obj);
            }
        }
    }
}