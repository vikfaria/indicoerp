<?php

namespace Workdo\Slack\Listeners;

use Workdo\School\Events\CreateSubject;
use Workdo\School\Models\SchoolClass;
use Workdo\Slack\Services\SendMsg;

class CreateSubjectLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSubject $event)
    {
        $subject = $event->subject;
        
        if (company_setting('Slack New Subject') == 'on') {
            $uArr = [
                'subject_name' => $subject->name,
            ];

            SendMsg::SendMsgs($uArr, 'New Subject');
        }
    }
}