<?php

namespace Workdo\Quotation\Listeners;

use App\Events\GivePermissionToRole;
use Workdo\Quotation\Models\SalesQuotation;

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
            if (in_array("Quotation", $user_module)) {
                SalesQuotation::GivePermissionToRoles($role_id, $rolename);
            }
        }
    }
}