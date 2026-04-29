<?php

namespace Workdo\Slack\Listeners;

use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\Slack\Services\SendMsg;

class CreateSalesMeetingLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesMeeting $event)
    {
        $request = $event->meeting;

        if (company_setting('Slack Meeting Assigned') == 'on') {
            $uArr = [
                'meeting_name' => $request->name
            ];

            SendMsg::SendMsgs($uArr, 'Meeting Assigned');
        }
    }
}