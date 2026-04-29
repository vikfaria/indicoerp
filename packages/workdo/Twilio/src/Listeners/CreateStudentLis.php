<?php

namespace Workdo\Twilio\Listeners;

use Workdo\School\Events\CreateStudent;
use Workdo\Twilio\Services\SendMsg;

class CreateStudentLis
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
    public function handle(CreateStudent $event)
    {
        if (company_setting('Twilio New Students') == 'on') {

            $student = $event->student;

            $class       = $student->class;
            $studentInfo = $student->admission->studentInfo;

            if (!empty($student) && !empty($class) && !empty($studentInfo->phone)) {
                $uArr = [
                    'student_name' => trim(
                        $studentInfo->first_name . ' ' .
                        ($studentInfo->middle_name ? $studentInfo->middle_name . ' ' : '') .
                        $studentInfo->last_name
                    ),
                    'class_name'   => $class->name
                ];

                SendMsg::SendMsgs($studentInfo->phone, $uArr, 'New Students');
            }
        }
    }
}
