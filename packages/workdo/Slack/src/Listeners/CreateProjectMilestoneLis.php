<?php

namespace Workdo\Slack\Listeners;

use Workdo\Slack\Services\SendMsg;
use Workdo\Taskly\Events\CreateProjectMilestone;

class CreateProjectMilestoneLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateProjectMilestone $event)
    {
        if (company_setting('Slack New Milestone') == 'on') {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'New Milestone');
        }
    }
}