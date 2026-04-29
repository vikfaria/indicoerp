<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Hrm\Events\CreateHoliday;
use Workdo\Twilio\Services\SendMsg;

class CreateHolidayLis
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
    public function handle(CreateHoliday $event)
    {
        if (company_setting('Twilio New Holidays') == 'on') {
            $request = $event->request;
            $to      = \Auth::user()->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'name'       => $request->name,
                    'start_date' => $request->start_date,
                    'end_date'   => $request->end_date
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Holidays');
            }
        }
    }
}
