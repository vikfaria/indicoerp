<?php

namespace Workdo\Stripe\Database\Seeders;

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
            ['name' => 'edit-stripe-settings', 'module' => 'stripe', 'label' => 'Edit Stripe Settings'],
            ['name' => 'manage-stripe-settings', 'module' => 'stripe', 'label' => 'Manage Stripe Settings'],
        ];

        $superadminRole = Role::where('name', 'superadmin')->first();

        $companyRole = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Stripe',
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