<?php

namespace Workdo\Calendar\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Calendar\Models\CalenderUtility;

class GiveRoleToPermission
{
    public function handle(GivePermissionToRole $event)
    {
        $role_id = $event->role_id;
        $rolename = $event->rolename;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        
        if (!empty($user_module)) {
            if (in_array("Calendar", $user_module)) {
                CalenderUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}