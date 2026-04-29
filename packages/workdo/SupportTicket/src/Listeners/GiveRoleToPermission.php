<?php

namespace Workdo\SupportTicket\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\SupportTicket\Models\SupporUtility;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GiveRoleToPermission
{
    public function __construct()
    {
        //
    }

    public function handle(GivePermissionToRole $event)
    {
        $role_id = $event->role_id;
        $rolename = $event->rolename;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module)) {
            if (in_array("SupportTicket", $user_module)) {
                SupporUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}