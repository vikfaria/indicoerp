<?php

namespace Workdo\Slack\Listeners;

use Workdo\Lead\Events\LeadMoved;
use Workdo\Lead\Models\LeadStage;
use Workdo\Slack\Services\SendMsg;

class LeadMovedLis
{
    public function __construct()
    {
        //
    }

    public function handle(LeadMoved $event)
    {
        $lead = $event->lead;
        $request = $event->request;
        $newStage = LeadStage::where('id', $request->stage_id)->first();

        if (company_setting('Slack Lead Moved') == 'on') {
            $uArr = [
                'lead_name' => $lead->name,
                'old_stage' => $lead->stage->name,
                'new_stage' => $newStage->name
            ];

            SendMsg::SendMsgs($uArr, 'Lead Moved');
        }
    }
}
