<?php

namespace Workdo\ZoomMeeting\Database\Seeders;

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
            // ZoomMeeting settings
            ['name' => 'manage-zoom-meeting-settings', 'module' => 'zoom meeting settings', 'label' => 'Manage Zoom Meeting Settings'],
            ['name' => 'edit-zoom-meeting-settings', 'module' => 'zoom meeting settings', 'label' => 'Edit Zoom Meeting Settings'],

            // ZoomMeeting management
            ['name' => 'manage-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Manage Zoom Meetings'],
            ['name' => 'manage-any-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Manage All Zoom Meetings'],
            ['name' => 'manage-own-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Manage Own Zoom Meetings'],
            ['name' => 'view-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'View Zoom Meetings'],
            ['name' => 'create-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Create Zoom Meetings'],
            ['name' => 'edit-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Edit Zoom Meetings'],
            ['name' => 'delete-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Delete Zoom Meetings'],
            ['name' => 'join-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Join Zoom Meetings'],
            ['name' => 'start-zoom-meetings', 'module' => 'zoom-meetings', 'label' => 'Start Zoom Meetings'],
            ['name' => 'update-zoom-meeting-status', 'module' => 'zoom-meetings', 'label' => 'Update Zoom Meeting Status'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'ZoomMeeting',
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