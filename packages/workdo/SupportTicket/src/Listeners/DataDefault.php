<?php

namespace Workdo\SupportTicket\Listeners;

use App\Events\DefaultData;
use Workdo\SupportTicket\Models\TicketField;
use Workdo\SupportTicket\Models\SupporUtility;

class DataDefault
{
    public function handle(DefaultData $event)
    {
        $company_id = $event->company_id;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        
        if (!empty($user_module) && in_array("SupportTicket", $user_module)) {
            TicketField::defaultdata($company_id);
            SupporUtility::defaultdata($company_id);
        }
    }
}