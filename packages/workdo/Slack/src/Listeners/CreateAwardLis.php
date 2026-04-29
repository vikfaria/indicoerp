<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Hrm\Events\CreateAward;
use Workdo\Slack\Services\SendMsg;

class CreateAwardLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateAward $event)
    {
        if (company_setting('Slack New Award') == 'on') {
            $request = $event->request;
            $emp = User::find($request->employee_id);
            $award = $event->award;
            
            $uArr = [
                'award_name' => $award->awardType->name,
                'user_name' => $emp->name,
                'date' => $request->date
            ];
            SendMsg::SendMsgs($uArr, 'New Award');
        }
    }
}
