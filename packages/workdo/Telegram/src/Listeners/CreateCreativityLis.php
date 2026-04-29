<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\InnovationCenter\Events\CreateCreativity;
use Workdo\Telegram\Services\SendMsg;

class CreateCreativityLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCreativity $event)
    {
        $creativity = $event->creativity;
        $challenge = $creativity->Challenge;

        if (company_setting('Telegram New Creativity')  == 'on') {

            $uArr = [
                'name' => $creativity->creativity_name,
                'challenge' => !empty($challenge) ? $challenge->challenge_name : '-',
            ];
            SendMsg::SendMsgs($uArr , 'New Creativity');
        }
    }
}
