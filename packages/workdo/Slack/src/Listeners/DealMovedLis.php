<?php

namespace Workdo\Slack\Listeners;

use Workdo\Lead\Events\DealMoved;
use Workdo\Lead\Models\DealStage;
use Workdo\Slack\Services\SendMsg;

class DealMovedLis
{
    public function __construct()
    {
        //
    }

    public function handle(DealMoved $event)
    {
        $deal = $event->deal;
        $request = $event->request;
        $newStage = DealStage::where('id', $request->stage_id)->first();

        if (company_setting('Slack Deal Moved') == 'on') {
            $uArr = [
                'deal_name' => $deal->name,
                'old_stage' => $deal->stage->name,
                'new_stage' => $newStage->name,
            ];

            SendMsg::SendMsgs($uArr, 'Deal Moved');
        }
    }
}
