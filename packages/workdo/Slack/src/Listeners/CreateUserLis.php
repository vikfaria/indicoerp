<?php

namespace Workdo\Slack\Listeners;

use App\Events\CreateUser;
use Workdo\Slack\Services\SendMsg;

class CreateUserLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateUser $event)
    {
        $user = $event->user;
        if (company_setting('Slack New User') == 'on') {
            $uArr = [
                'user_name' => $user->name,
            ];

            SendMsg::SendMsgs($uArr, 'New User');
        }
    }
}
