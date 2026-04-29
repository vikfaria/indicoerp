<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Slack\Services\SendMsg;
use Workdo\Timesheet\Events\CreateTimesheet;

class CreateTimesheetLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateTimesheet $event)
    {
        $timesheet = $event->timesheet;
        $user = User::find($timesheet->created_by);

        if (company_setting('Slack New Timesheet') == 'on') {
            $uArr = [
                'user_name' => $user->name,
                'type' => $timesheet->type,
            ];

            SendMsg::SendMsgs($uArr, 'New Timesheet');
        }
    }
}
