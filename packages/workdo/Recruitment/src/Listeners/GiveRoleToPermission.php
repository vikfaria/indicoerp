<?php

namespace Workdo\Recruitment\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Recruitment\Models\Job;

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
        
        if (!empty($user_module) && in_array("Recruitment", $user_module)) {
            Job::GivePermissionToRoles($role_id, $rolename);
        }
    }
}