<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Lead\Events\LeadConvertDeal;
use Workdo\Twilio\Services\SendMsg;

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
        if (company_setting('Twilio Lead to Deal Conversion') == 'on') {
            $lead        = $event->lead;
            $AssignUsers = User::where('id', $lead->user_id)->first();
            $to          = $AssignUsers->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'name' => $lead->name
                ];

                SendMsg::SendMsgs($to, $uArr, 'Lead to Deal Conversion');
            }
        }
    }
}
