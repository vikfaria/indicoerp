<?php

namespace Workdo\Timesheet\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Timesheet\Models\TimesheetUtility;

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
            if (in_array("Timesheet", $user_module)) {
                TimesheetUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}