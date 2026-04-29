<?php

namespace Workdo\Slack\Listeners;

use Workdo\Slack\Services\SendMsg;
use Workdo\ToDo\Events\CreateToDo;

class CreateToDoLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateToDo $event)
    {
        $toDo = $event->todo;

        if (company_setting('Slack New To Do') == 'on') {
            $uArr = [
                'name' => $toDo->title,
                'module' => $toDo->sub_module
            ];

            SendMsg::SendMsgs($uArr, 'New To Do');
        }
    }
}
