<?php

namespace Workdo\Contract\Database\Seeders;

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
            // Contract management
            ['name' => 'manage-contracts', 'module' => 'contracts', 'label' => 'Manage Contracts'],
            ['name' => 'manage-any-contracts', 'module' => 'contracts', 'label' => 'Manage All Contracts'],
            ['name' => 'manage-own-contracts', 'module' => 'contracts', 'label' => 'Manage Own Contracts'],
            ['name' => 'view-contracts', 'module' => 'contracts', 'label' => 'View Contracts'],
            ['name' => 'create-contracts', 'module' => 'contracts', 'label' => 'Create Contracts'],
            ['name' => 'edit-contracts', 'module' => 'contracts', 'label' => 'Edit Contracts'],
            ['name' => 'delete-contracts', 'module' => 'contracts', 'label' => 'Delete Contracts'],
            ['name' => 'duplicate-contracts', 'module' => 'contracts', 'label' => 'Duplicate Contracts'],
            ['name' => 'preview-contracts', 'module' => 'contracts', 'label' => 'Preview Contracts'],
            ['name' => 'signatures-contracts', 'module' => 'contracts', 'label' => 'Sign Contracts'],

            // ContractType management
            ['name' => 'manage-contract-types', 'module' => 'contract-types', 'label' => 'Manage Contract Types'],
            ['name' => 'manage-any-contract-types', 'module' => 'contract-types', 'label' => 'Manage All Contract Types'],
            ['name' => 'manage-own-contract-types', 'module' => 'contract-types', 'label' => 'Manage Own Contract Types'],
            ['name' => 'create-contract-types', 'module' => 'contract-types', 'label' => 'Create Contract Types'],
            ['name' => 'edit-contract-types', 'module' => 'contract-types', 'label' => 'Edit Contract Types'],
            ['name' => 'delete-contract-types', 'module' => 'contract-types', 'label' => 'Delete Contract Types'],

            // Contract attachments
            ['name' => 'manage-any-contract-attachments', 'module' => 'contract-attachments', 'label' => 'Manage All Contract Attachments'],
            ['name' => 'manage-own-contract-attachments', 'module' => 'contract-attachments', 'label' => 'Manage Own Contract Attachments'],
            ['name' => 'create-contract-attachments', 'module' => 'contract-attachments', 'label' => 'Create Contract Attachments'],
            ['name' => 'delete-contract-attachments', 'module' => 'contract-attachments', 'label' => 'Delete Contract Attachments'],

            // Contract comments
            ['name' => 'manage-any-contract-comments', 'module' => 'contract-comments', 'label' => 'Manage All Contract Comments'],
            ['name' => 'manage-own-contract-comments', 'module' => 'contract-comments', 'label' => 'Manage Own Contract Comments'],
            ['name' => 'create-contract-comments', 'module' => 'contract-comments', 'label' => 'Create Contract Comments'],
            ['name' => 'edit-contract-comments', 'module' => 'contract-comments', 'label' => 'Edit Contract Comments'],
            ['name' => 'delete-contract-comments', 'module' => 'contract-comments', 'label' => 'Delete Contract Comments'],

            // Contract notes
            ['name' => 'manage-any-contract-notes', 'module' => 'contract-notes', 'label' => 'Manage All Contract Notes'],
            ['name' => 'manage-own-contract-notes', 'module' => 'contract-notes', 'label' => 'Manage Own Contract Notes'],
            ['name' => 'create-contract-notes', 'module' => 'contract-notes', 'label' => 'Create Contract Notes'],
            ['name' => 'edit-contract-notes', 'module' => 'contract-notes', 'label' => 'Edit Contract Notes'],
            ['name' => 'delete-contract-notes', 'module' => 'contract-notes', 'label' => 'Delete Contract Notes'],

            // Contract renewals
            ['name' => 'manage-any-contract-renewals', 'module' => 'contract-renewals', 'label' => 'Manage All Contract Renewals'],
            ['name' => 'manage-own-contract-renewals', 'module' => 'contract-renewals', 'label' => 'Manage Own Contract Renewals'],
            ['name' => 'create-contract-renewals', 'module' => 'contract-renewals', 'label' => 'Create Contract Renewals'],
            ['name' => 'edit-contract-renewals', 'module' => 'contract-renewals', 'label' => 'Edit Contract Renewals'],
            ['name' => 'delete-contract-renewals', 'module' => 'contract-renewals', 'label' => 'Delete Contract Renewals'],

            // Contract settings
            ['name' => 'manage-contract-settings', 'module' => 'settings', 'label' => 'Manage Contract Settings'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Contract',
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

