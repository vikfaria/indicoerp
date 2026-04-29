<?php

namespace Workdo\Twilio\Listeners;

use Workdo\School\Events\CreateAdmission;
use Workdo\Twilio\Services\SendMsg;

class CreateAdmissionLis
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
    public function handle(CreateAdmission $event)
    {
        if (company_setting('Twilio New Addmissions') == 'on') {

            $admission   = $event->admission;
            $studentInfo = $admission->studentInfo;

            if ($admission && $studentInfo && $studentInfo->phone) {
                $uArr = [
                    'student_name' => trim(
                        $studentInfo->first_name . ' ' .
                        ($studentInfo->middle_name ? $studentInfo->middle_name . ' ' : '') .
                        $studentInfo->last_name
                    ),
                ];

                SendMsg::SendMsgs($studentInfo->phone, $uArr, 'New Addmissions');
            }
        }
    }
}
