<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Appointment\Events\AppointmentStatus;

class AppointmentStatusLis
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
    public function handle(AppointmentStatus $event)
    {
        $schedule = $event->schedule;
        if (company_setting('Telegram Appointment Status') == 'on') {
            $uArr = [
                'appointment_name' => $schedule->appointment->appointment_name,
                'status' => $schedule->status,
            ];
            SendMsg::SendMsgs($uArr,'Appointment Status');
        }
    }
}
