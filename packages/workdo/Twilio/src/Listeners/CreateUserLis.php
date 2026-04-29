<?php

namespace Workdo\Twilio\Listeners;

use App\Events\CreateUser;
use Workdo\Twilio\Services\SendMsg;

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
        if (company_setting('Twilio New User') == 'on') {
            $user = $event->user;
            $to   = $user->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'user_name' => $user->name,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New User');
            }
        }
    }
}
