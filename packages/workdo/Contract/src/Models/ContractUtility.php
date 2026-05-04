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
                if ($all_permissions->isNotEmpty()) {
                    $roles_v->givePermissionTo($all_permissions);
                }
            }
        }

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $staff_permission);
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $client_permission);
        }
    }

    private static function syncPermissionsByName(?Role $role, array $permissionNames): void
    {
        if (!$role || empty($permissionNames)) {
            return;
        }

        $permissions = Permission::whereIn('name', array_values(array_unique($permissionNames)))->get();
        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
    }
}
