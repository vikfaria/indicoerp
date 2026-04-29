<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Hrm\Events\CreateAward;
use Workdo\Telegram\Services\SendMsg;

class CreateAwardLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateAward $event)
    {
        if(company_setting('Telegram New Award')  == 'on')
        {
            $request = $event->request;
            $award   = $event->award;
            $emp     = User::find($award->employee_id);
            if(!empty($emp) && !empty($award->awardType))
            {
                $uArr = [
                    'award_name' => $award->awardType->name,
                    'user_name' => $emp->name,
                    'date' => $award->award_date
                ];
                SendMsg::SendMsgs($uArr , 'New Award');
            }
        }
    }
}
