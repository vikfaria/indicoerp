<?php

namespace Workdo\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FormBuilderUtility extends Model
{
    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-formbuilder',
            'manage-own-formbuilder-form',
            'view-formbuilder-responses',
        ];

        $client_permission = [
            'manage-formbuilder',
            'manage-own-formbuilder-form',
            'create-formbuilder-form',
            'edit-formbuilder-form',
            'view-formbuilder-responses',
        ];


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
