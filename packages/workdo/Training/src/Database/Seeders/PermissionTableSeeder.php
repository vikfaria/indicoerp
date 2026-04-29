<?php

namespace Workdo\Training\Database\Seeders;

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
            ['name' => 'manage-training', 'module' => 'training', 'label' => 'Manage Training'],                    
            
            // Training Types permissions
            ['name' => 'manage-training-types', 'module' => 'training', 'label' => 'Manage Training Types'],
            ['name' => 'manage-any-training-types', 'module' => 'training', 'label' => 'Manage All Training Types'],
            ['name' => 'manage-own-training-types', 'module' => 'training', 'label' => 'Manage Own Training Types'],
            ['name' => 'create-training-types', 'module' => 'training', 'label' => 'Create Training Types'],
            ['name' => 'edit-training-types', 'module' => 'training', 'label' => 'Edit Training Types'],
            ['name' => 'delete-training-types', 'module' => 'training', 'label' => 'Delete Training Types'],
            
            // Trainers permissions
            ['name' => 'manage-trainers', 'module' => 'training', 'label' => 'Manage Trainers'],
            ['name' => 'manage-any-trainers', 'module' => 'training', 'label' => 'Manage All Trainers'],
            ['name' => 'manage-own-trainers', 'module' => 'training', 'label' => 'Manage Own Trainers'],
            ['name' => 'create-trainers', 'module' => 'training', 'label' => 'Create Trainers'],
            ['name' => 'edit-trainers', 'module' => 'training', 'label' => 'Edit Trainers'],
            ['name' => 'delete-trainers', 'module' => 'training', 'label' => 'Delete Trainers'],
            
            // Trainings permissions
            ['name' => 'manage-trainings', 'module' => 'training', 'label' => 'Manage Trainings'],
            ['name' => 'manage-any-trainings', 'module' => 'training', 'label' => 'Manage All Trainings'],
            ['name' => 'manage-own-trainings', 'module' => 'training', 'label' => 'Manage Own Trainings'],
            ['name' => 'create-trainings', 'module' => 'training', 'label' => 'Create Trainings'],
            ['name' => 'edit-trainings', 'module' => 'training', 'label' => 'Edit Trainings'],
            ['name' => 'delete-trainings', 'module' => 'training', 'label' => 'Delete Trainings'],
            
            // Training Tasks permissions
            ['name' => 'manage-training-tasks', 'module' => 'training', 'label' => 'Manage Training Tasks'],
            ['name' => 'manage-any-training-tasks', 'module' => 'training', 'label' => 'Manage All Training Tasks'],
            ['name' => 'manage-own-training-tasks', 'module' => 'training', 'label' => 'Manage Own Training Tasks'],
            ['name' => 'create-training-tasks', 'module' => 'training', 'label' => 'Create Training Tasks'],
            ['name' => 'edit-training-tasks', 'module' => 'training', 'label' => 'Edit Training Tasks'],
            ['name' => 'delete-training-tasks', 'module' => 'training', 'label' => 'Delete Training Tasks'],
            
            // Training Feedbacks permissions
            ['name' => 'manage-training-feedbacks', 'module' => 'training', 'label' => 'Manage Training Feedbacks'],
            ['name' => 'manage-any-training-feedbacks', 'module' => 'training', 'label' => 'Manage All Training Feedbacks'],
            ['name' => 'manage-own-training-feedbacks', 'module' => 'training', 'label' => 'Manage Own Training Feedbacks'],
            ['name' => 'create-training-feedbacks', 'module' => 'training', 'label' => 'Create Training Feedbacks'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Training',
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