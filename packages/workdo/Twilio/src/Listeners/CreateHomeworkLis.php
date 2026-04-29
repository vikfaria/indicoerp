<?php

namespace Workdo\Twilio\Listeners;

use Workdo\School\Events\CreateHomework;
use Workdo\School\Models\SchoolStudent;
use Workdo\Twilio\Services\SendMsg;

class CreateHomeworkLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateHomework $event)
    {
        if (company_setting('Twilio New Homework') == 'on') {

            $homework = $event->homework;

            $class   = $homework->class;
            $subject = $homework->subject;

            $students = SchoolStudent::with('admission.studentInfo')
                ->where('class_id', $class->id)
                ->get();

            foreach ($students as $student) {
                if ($student->phone) {
                    $uArr = [
                        'homework_title' => $homework->title,
                        'class_name'     => $class->name,
                        'subject_name'   => $subject->name,
                    ];

                    SendMsg::SendMsgs($student->phone, $uArr, 'New Homework');
                }
            }
        }
    }
}
