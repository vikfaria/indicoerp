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
                foreach ($permission as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }

            if ($rolename == 'client') {
                $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
                foreach ($permission as $permission_v) {
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