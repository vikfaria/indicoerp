<?php

namespace Workdo\Telegram\Listeners;

use Workdo\School\Events\CreateAdmission;
use Workdo\Telegram\Services\SendMsg;

class CreateAdmissionLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateAdmission $event)
    {
        $admission = $event->admission;
        if (company_setting('Telegram New Addmissions')  == 'on') {

            if(!empty($admission))
            {
                $uArr = [
                    'student_name' => $admission->studentInfo->first_name .' '. $admission->studentInfo->last_name
                ];
                SendMsg::SendMsgs($uArr , 'New Addmissions');
            }
        }
    }
}
