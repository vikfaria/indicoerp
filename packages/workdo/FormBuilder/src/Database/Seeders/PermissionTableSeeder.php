<?php

namespace Workdo\FormBuilder\Database\Seeders;

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
            ['name' => 'manage-formbuilder', 'module' => 'form', 'label' => 'Manage FormBuilder'],

            ['name' => 'manage-any-formbuilder-form', 'module' => 'form', 'label' => 'Manage All Form'],
            ['name' => 'manage-own-formbuilder-form', 'module' => 'form', 'label' => 'Manage Own Form'],
            ['name' => 'create-formbuilder-form', 'module' => 'form', 'label' => 'Create Form'],
            ['name' => 'edit-formbuilder-form', 'module' => 'form', 'label' => 'Edit Form'],
            ['name' => 'edit-formbuilder-form-fields', 'module' => 'form', 'label' => 'Edit Form Fields'],
            ['name' => 'delete-formbuilder-form-fields', 'module' => 'form', 'label' => 'Delete Form Fields'],
            ['name' => 'delete-formbuilder-form', 'module' => 'form', 'label' => 'Delete Form'],

            ['name' => 'view-formbuilder-responses', 'module' => 'form-responses', 'label' => 'View Form Responses'],
            ['name' => 'delete-formbuilder-responses', 'module' => 'form-responses', 'label' => 'Delete Form Responses'],

            ['name' => 'manage-formbuilder-conversions', 'module' => 'form-conversions', 'label' => 'Manage Form Conversions'],
            ['name' => 'edit-formbuilder-conversions', 'module' => 'form-conversions', 'label' => 'Edit Form Conversions'],
            
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'FormBuilder',
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
