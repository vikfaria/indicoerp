<?php

namespace Workdo\Timesheet\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TimesheetUtility extends Model
{
    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-timesheet',
            'manage-own-timesheet',
            'create-timesheet',
            'edit-timesheet',
            'delete-timesheet',
        ];

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $staff_permission);
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
