<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Telegram\Services\SendMsg;
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
        if (company_setting('Telegram New Timesheet')  == 'on') {

            if(!empty($timesheet) && !empty($user))
            {
                $uArr = [
                    'user_name' => $user->name,
                    'type'      => $timesheet->type,
                ];
            }

            SendMsg::SendMsgs($uArr , 'New Timesheet');
        }
    }
}
