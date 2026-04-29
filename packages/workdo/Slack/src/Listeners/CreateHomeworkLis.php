<?php

namespace Workdo\Slack\Listeners;

use Workdo\School\Events\CreateHomework;
use Workdo\School\Models\SchoolClass;
use Workdo\School\Models\SchoolSubject;
use Workdo\Slack\Services\SendMsg;

class CreateHomeworkLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHomework $event)
    {
        $homework = $event->homework;
        $class = SchoolClass::find($homework->class_id);
        $subject = SchoolSubject::find($homework->subject_id);
        if (company_setting('Slack New Homework') == 'on') {
            $uArr = [
                'homework_title' => $homework->title,
                'class_name' => $class->name,
                'subject_name' => $subject->name
            ];

            SendMsg::SendMsgs($uArr, 'New Homework');
        }
    }
}
