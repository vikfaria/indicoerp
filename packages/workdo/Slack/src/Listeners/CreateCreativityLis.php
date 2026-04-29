<?php

namespace Workdo\Slack\Listeners;

use Workdo\InnovationCenter\Events\CreateCreativity;
use Workdo\InnovationCenter\Models\Challenge;
use Workdo\Slack\Services\SendMsg;

class CreateCreativityLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCreativity $event)
    {
        $creativity = $event->creativity;
        $challenge = Challenge::find($creativity->challenge_id);

        if (company_setting('Slack New Creativity') == 'on') {
            $uArr = [
                'name' => $creativity->creativity_name,
                'challenge' => !empty($challenge) ? $challenge->challenge_name : '-',
            ];

            SendMsg::SendMsgs($uArr, 'New Creativity');
        }
    }
}
