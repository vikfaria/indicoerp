<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Appointment\Events\CreateSchedule;
use Workdo\Twilio\Services\SendMsg;

class CreateScheduleLis
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
    public function handle(CreateSchedule $event)
    {
        $schedule = $event->schedule;
        $request  = $event->request;
        $to       = $request->phone;

        if (company_setting('Twilio New Appointment', $schedule->created_by) == 'on') {

            if (!empty($to)) {
                $uArr = [
                    'business_name'    => $schedule->name,
                    'appointment_name' => $schedule->appointment->appointment_name,
                    'date'             => $request->date,
                    'time'             => $request->start_time,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Appointment', $schedule->created_by);
            }
        }
    }
}
