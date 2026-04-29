<?php

namespace Workdo\Lead\Listeners;

use App\Events\DefaultData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Lead\Models\LeadUtility;

class DataDefault
{
    public function handle(DefaultData $event)
    {
        $company_id = $event->company_id;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if(!empty($user_module))
        {
            if (in_array("Lead", $user_module))
            {
                LeadUtility::defaultdata($company_id);
            }
        }
    }
}