<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\ZoomMeeting\Events\CreateZoomMeeting;

class CreateZoommeetingLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateZoomMeeting $event)
    {
        $new  = $event->meeting;
        $name = $new->title;
        $date = $new->start_time;
        $user = $new->host->name;

        if (company_setting('Telegram New Zoom Meeting')  == 'on') {
            $uArr = [
                'meeting_name' => $name,
                'user_name'    => $user,
                'date'         => $date
            ];
            SendMsg::SendMsgs($uArr , 'New Zoom Meeting');
        }
    }
}
