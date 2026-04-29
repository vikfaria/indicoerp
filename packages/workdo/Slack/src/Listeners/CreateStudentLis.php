<?php

namespace Workdo\Slack\Listeners;

use Workdo\School\Events\CreateStudent;
use Workdo\School\Models\SchoolClass;
use Workdo\School\Models\SchoolStudentInfo;
use Workdo\Slack\Services\SendMsg;

class CreateStudentLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateStudent $event)
    {
        $student = $event->student;
        $class = SchoolClass::find($student->class_id);
        $student = SchoolStudentInfo::where('admission_id', $student->admission_id)->first();
        $studentName = $student ? trim($student->first_name . ' ' . $student->last_name) : '';

        if (company_setting('Slack New Students') == 'on') {
            $uArr = [
                'student_name' => $studentName,
                    'class_name' => $class->name
            ];

            SendMsg::SendMsgs($uArr, 'New Students');
        }
    }
}