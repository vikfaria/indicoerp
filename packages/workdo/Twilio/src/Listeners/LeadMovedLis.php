<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Lead\Events\LeadMoved;
use Workdo\Lead\Models\LeadStage;
use Workdo\Twilio\Services\SendMsg;

class LeadMovedLis
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
    public function handle(LeadMoved $event)
    {
        if (company_setting('Twilio Lead Moved') == 'on') {
            $lead    = $event->lead;
            $request = $event->request;

            $newStage    = LeadStage::where('id', $request->stage_id)->first();
            $AssignUsers = User::where('id', $lead->user_id)->first();
            $to          = $AssignUsers->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'lead_name' => $lead->name,
                    'old_stage' => $lead->stage->name,
                    'new_stage' => $newStage->name
                ];

                SendMsg::SendMsgs($to, $uArr, 'Lead Moved');
            }
        }
    }
}
