<?php

namespace Workdo\ZoomMeeting\Helpers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ZoomMeetingUtility
{
     public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $permission = [
            'manage-zoom-meetings',
            'manage-own-zoom-meetings',  
            'view-zoom-meetings',
            'join-zoom-meetings',
            'start-zoom-meetings'
        ];
            
        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $permission);
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            self::syncPermissionsByName($roles_v, $permission);
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
