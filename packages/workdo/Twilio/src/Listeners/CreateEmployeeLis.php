<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\School\Events\CreateEmployee;
use Workdo\Twilio\Services\SendMsg;

class CreateEmployeeLis
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
    public function handle(CreateEmployee $event)
    {
        if (company_setting('Twilio New Teacher') == 'on') {

            $employee = $event->employee;

            $user = User::find($employee->user_id);
            if (!empty($user) && !empty($user->mobile_no)) {
                $uArr = [
                    'teacher_name' => $user->name
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Teacher');
            }
        }
    }
}
