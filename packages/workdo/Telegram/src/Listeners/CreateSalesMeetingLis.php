<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Sales\Events\CreateSalesMeeting;

class CreateSalesMeetingLis
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
    public function handle(CreateSalesMeeting $event)
    {
        if(company_setting('Telegram Meeting Assigned')  == 'on')
        {
            $request = $event->request;
            $uArr = [
                'meeting_name' => $request->name
            ];
            SendMsg::SendMsgs($uArr , 'Meeting Assigned');
        }
    }
}
