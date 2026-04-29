<?php

namespace Workdo\Telegram\Listeners;

use Workdo\HospitalManagement\Events\CreateHospitalMedicine;
use Workdo\Telegram\Services\SendMsg;

class CreateHospitalMedicineLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHospitalMedicine $event)
    {
        $medicine = $event->hospitalMedicine;
        if (company_setting('Telegram New Hospital Medicine')  == 'on') {

            if(!empty($medicine))
            {
                $uArr = [
                    'name' => $medicine->name
                ];
                SendMsg::SendMsgs($uArr , 'New Hospital Medicine');
            }
        }
    }
}
