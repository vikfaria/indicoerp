<?php

namespace Workdo\Calendar\Database\Seeders;

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
            ['name' => 'manage-calendar', 'module' => 'calendar', 'label' => 'Manage Calendar'],
            ['name' => 'view-calendar', 'module' => 'calendar', 'label' => 'View Calendar'],
            ['name' => 'manage-google-calendar-settings', 'module' => 'google-calendar-settings', 'label' => 'Manage Google Calendar Settings'],
            ['name' => 'edit-google-calendar-settings', 'module' => 'google-calendar-settings', 'label' => 'Edit Google Calendar Settings'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Calendar',
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
