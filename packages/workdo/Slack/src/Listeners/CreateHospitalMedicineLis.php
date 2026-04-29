<?php

namespace Workdo\Slack\Listeners;

use Workdo\HospitalManagement\Events\CreateHospitalMedicine;
use Workdo\Slack\Services\SendMsg;

class CreateHospitalMedicineLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHospitalMedicine $event)
    {
        $medicine = $event->hospitalMedicine;

        if (company_setting('Slack New Hospital Medicine') == 'on') {
            $uArr = [
                'name' => $medicine->name
            ];

            SendMsg::SendMsgs($uArr, 'New Hospital Medicine');
        }
    }
}