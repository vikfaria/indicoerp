<?php

namespace Workdo\Lead\Listeners;

use App\Events\GivePermissionToRole;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Lead\Models\LeadUtility;

class GiveRoleToPermission
{
    public function handle(GivePermissionToRole $event)
    {
        $role_id = $event->role_id;
        $rolename = $event->rolename;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module)) {
            if (in_array("Lead", $user_module)) {
                LeadUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}