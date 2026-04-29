<?php

namespace Workdo\Slack\Listeners;

use Workdo\Recruitment\Events\CreateCandidate;
use Workdo\Slack\Services\SendMsg;

class CreateCandidateLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCandidate $event)
    {
        $candidate = $event->candidate;

        if (company_setting('Slack New Candidate') == 'on') {
            $uArr = [
                'user_name' => $candidate->first_name . ' ' . $candidate->last_name,
                'job_name' => $candidate->job_posting ? $candidate->job_posting->title : '-'
            ];

            SendMsg::SendMsgs($uArr, 'New Candidate');
        }
    }
}