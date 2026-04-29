<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Lead\Events\LeadConvertDeal;

class LeadConvertDealLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(LeadConvertDeal $event)
    {
        if (company_setting('Telegram Lead to Deal Conversion')  == 'on')
        {
            $lead = $event->lead;
            if(!empty($lead))
            {
                $uArr = [
                    'name' => $lead->name
                ];
                SendMsg::SendMsgs($uArr , 'Lead to Deal Conversion');
            }
        }
    }
}
