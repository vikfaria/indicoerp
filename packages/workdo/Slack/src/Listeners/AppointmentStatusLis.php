<?php

namespace Workdo\Slack\Listeners;

use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Slack\Services\SendMsg;

class AppointmentStatusLis
{
    public function __construct()
    {
        //
    }

    public function handle(AppointmentStatus $event)
    {
        $schedule = $event->schedule;

        if (company_setting('Slack Appointment Status') == 'on') {
            $uArr = [
                'appointment_name'=>$schedule->appointment->name,
                'status'=>$schedule->status,
            ];

            SendMsg::SendMsgs($uArr, 'Appointment Status', $schedule->created_by);
        }
    }
}
