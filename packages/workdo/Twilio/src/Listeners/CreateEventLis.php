<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Hrm\Events\CreateEvent;
use Workdo\Twilio\Services\SendMsg;

class CreateEventLis
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
    public function handle(CreateEvent $event)
    {
        if (company_setting('Twilio New Event') == 'on') {
            $request = $event->request;
            $event   = $event->event;

            $to = \Auth::user()->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'event_name'  => $request->title,
                    'branch_name' => $event->departments ?? '',
                    'start_date'  => $request->start_date,
                    'end_date'    => $request->end_date,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Event');
            }

        }
    }
}
