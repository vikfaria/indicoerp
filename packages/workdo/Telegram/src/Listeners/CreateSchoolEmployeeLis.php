<?php

namespace Workdo\Telegram\Listeners;

use Workdo\School\Events\CreateEmployee;
use Workdo\Telegram\Services\SendMsg;

class CreateSchoolEmployeeLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateEmployee $event)
    {
        $employee = $event->employee;
        if (company_setting('Telegram New Teacher')  == 'on') {

            if(!empty($employee))
            {
                $uArr = [
                    'teacher_name' => $employee->user->name
                ];
                SendMsg::SendMsgs($uArr , 'New Teacher');
            }
        }
    }
}
