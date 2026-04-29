<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Telegram\Services\SendMsg;
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
        $userIds = is_array($toDo->assigned_to) ? $toDo->assigned_to : explode(', ', $toDo->assigned_to);
        $user_detail = User::whereIn('id', $userIds)->pluck('name')->toArray();
        $user = implode(',', $user_detail);

        if (company_setting('Telegram Complete To Do')  == 'on') {

            $uArr = [
                'user_name' => $user
            ];
            SendMsg::SendMsgs($uArr , 'Complete To Do');
        }
    }
}
