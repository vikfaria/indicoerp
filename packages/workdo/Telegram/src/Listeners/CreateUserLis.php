<?php

namespace Workdo\Telegram\Listeners;

use App\Events\CreateUser;
use Workdo\Telegram\Services\SendMsg;

class CreateUserLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateUser $event)
    {
        $user = $event->user;
        if (company_setting('Telegram New User') == 'on') {
            $uArr = [
                'user_name' => $user->name,
            ];

            SendMsg::SendMsgs($uArr, 'New User');
        }
    }
}
