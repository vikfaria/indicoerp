<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Slack\Services\SendMsg;
use Workdo\ToDo\Events\CompleteToDo;

class CompleteToDoLis
{
    public function __construct()
    {
        //
    }

    public function handle(CompleteToDo $event)
    {
        $toDo = $event->todo;
        $assignedIds = is_array($toDo->assigned_to) ? $toDo->assigned_to : explode(',', $toDo->assigned_to ?? '');
        $user = User::whereIn('id', $assignedIds)->get()->pluck('name');
        $user_detail = [];
        
        if (count($user) > 0) {
            foreach ($user as $datasand) {
                $user_detail[] = $datasand;
            }
        }
        $user = implode(',', $user_detail);


        if (company_setting('Slack Complete To Do') == 'on') {
            $uArr = [
                'user_name' => $user
            ];

            SendMsg::SendMsgs($uArr, 'Complete To Do');
        }
    }
}
