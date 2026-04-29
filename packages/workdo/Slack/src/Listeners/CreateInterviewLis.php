<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Slack\Services\SendMsg;

class CreateInterviewLis
{
    public function __construct()
    {
        //
    }

    public function handle($event)
    {
        if(company_setting('Slack Interview Schedule')  == 'on')
        {
            $schedule = $event->interview;
            $usersId  = is_array($schedule->interviewer_ids) ? $schedule->interviewer_ids : explode(',', $schedule->interviewer_ids);
            $users    = User::whereIn('id', $usersId)->pluck('name')->toArray();
            $intevier = implode(', ', $users);
            $employee = Candidate::where('id',$schedule->candidate_id)->first();
            if(!empty($employee->phone)){
                $uArr = [
                    'user_name'   => $intevier,
                    'application' => $schedule->jobPosting->title
                ];
                SendMsg::SendMsgs($uArr, 'Interview Schedule');
            }
        }
    }
}