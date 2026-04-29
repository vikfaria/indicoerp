<?php

namespace Workdo\Calendar\Listeners;

use App\Events\DefaultData;
use Workdo\Calendar\Models\CalenderUtility;

class DataDefault
{
    public function handle(DefaultData $event)
    {
        $company_id = $event->company_id;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        
        if (!empty($user_module)) {
            if (in_array("Calendar", $user_module)) {
                CalenderUtility::GivePermissionToRoles(null, null);
            }
        }
    }
}