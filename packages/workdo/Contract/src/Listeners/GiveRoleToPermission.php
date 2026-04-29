<?php

namespace Workdo\Contract\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Contract\Models\ContractUtility;

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
            if (in_array("Contract", $user_module)) {
                ContractUtility::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}