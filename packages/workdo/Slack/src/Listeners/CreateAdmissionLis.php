<?php

namespace Workdo\Slack\Listeners;

use Workdo\School\Events\CreateAdmission;
use Workdo\School\Models\SchoolStudentInfo;
use Workdo\Slack\Services\SendMsg;

class CreateAdmissionLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateAdmission $event)
    {
        $admission = $event->admission;
        if (company_setting('Slack New Addmissions') == 'on') {
            $student = SchoolStudentInfo::where('admission_id', $admission->id)->first();
            $studentName = $student ? trim($student->first_name . ' ' . $student->last_name) : '';

            $uArr = [
                'student_name' => $studentName
            ];

            SendMsg::SendMsgs($uArr, 'New Addmissions');
        }
    }
}
