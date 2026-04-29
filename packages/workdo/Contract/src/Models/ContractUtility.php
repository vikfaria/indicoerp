<?php

namespace Workdo\Contract\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Workdo\Contract\Models\ContractType;
class ContractUtility extends Model
{
    public static function defaultdata($company_id = null)
    {
        if (!empty($company_id)) {
            // Set contract prefix
            setSetting('contract_prefix', 'CON', $company_id);
        }
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-contracts',
            'manage-own-contracts',
            'view-contracts',
            'signatures-contracts',
            'preview-contracts',

            'manage-any-contract-attachments',
            'create-contract-attachments',
            'delete-contract-attachments',

            'manage-any-contract-comments',
            'create-contract-comments',
            'edit-contract-comments',
            'delete-contract-comments',

            'manage-any-contract-notes',
            'create-contract-notes',
            'edit-contract-notes',
            'delete-contract-notes',
        ];

        $client_permission = [
            'manage-contracts',
            'manage-own-contracts',
            'view-contracts',
            'create-contracts',
            'duplicate-contracts',
            'signatures-contracts',
            'preview-contracts',

            'manage-any-contract-attachments',
            'create-contract-attachments',
            'delete-contract-attachments',

            'manage-any-contract-comments',
            'create-contract-comments',
            'edit-contract-comments',
            'delete-contract-comments',

            'manage-any-contract-notes',
            'create-contract-notes',
            'edit-contract-notes',
            'delete-contract-notes',

            'manage-any-contract-renewals',
            'manage-own-contract-renewals',
            'create-contract-renewals',
            'edit-contract-renewals',
            'delete-contract-renewals',
        ];

        if ($rolename == 'company') {
            $roles_v = Role::where('name', 'company')->where('id', $role_id)->first();
            if ($roles_v) {
                $all_permissions = Permission::where('add_on', 'Contract')->get();
                foreach ($all_permissions as $permission) {
                    if (!$roles_v->hasPermissionTo($permission->name)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($staff_permission as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($client_permission as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }
    }
}
