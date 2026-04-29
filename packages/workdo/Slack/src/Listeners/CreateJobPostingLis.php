<?php

namespace Workdo\Slack\Listeners;

use Workdo\Recruitment\Events\CreateJobPosting;
use Workdo\Slack\Services\SendMsg;

class CreateJobPostingLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateJobPosting $event)
    {
        $job = $event->jobposting;

        if (company_setting('Slack New Job Posting')  == 'on') {
            $uArr = [
                'job_name' => $job->title,
            ];

            SendMsg::SendMsgs($uArr, 'New Job Posting');
        }
    }
}
