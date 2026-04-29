<?php

namespace Workdo\Webhook\Database\Seeders;

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
            ['name' => 'manage-webhooks', 'module' => 'webhook', 'label' => 'Manage Webhooks'],
            ['name' => 'create-webhooks', 'module' => 'webhook', 'label' => 'Create Webhooks'],
            ['name' => 'edit-webhooks', 'module' => 'webhook', 'label' => 'Edit Webhooks'],
            ['name' => 'delete-webhooks', 'module' => 'webhook', 'label' => 'Delete Webhooks'],
        ];

        $company_role = Role::where('name', 'company')->first();
        $superadminRole = Role::where('name', 'superadmin')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Webhook',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }

            if ($superadminRole && !$superadminRole->hasPermissionTo($permission_obj)) {
                $superadminRole->givePermissionTo($permission_obj);
            }
        }
    }
}