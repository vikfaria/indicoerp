<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Lead\Events\LeadMoved;
use Workdo\Lead\Models\LeadStage;

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
        if (company_setting('Telegram Lead Moved')  == 'on')
        {
            $lead     = $event->lead;
            $request  = $event->request;
            $newStage = LeadStage::where('id',$request->stage_id)->first();
            if(!empty($lead) && !empty($newStage))
            {
                $uArr = [
                    'lead_name' => $lead->name,
                    'old_stage' => $lead->stage->name,
                    'new_stage' => $newStage->name
                ];
                SendMsg::SendMsgs($uArr , 'Lead Moved');
            }
        }
    }
}
