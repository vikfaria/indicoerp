<?php

namespace Workdo\Telegram\Listeners;

use Workdo\InnovationCenter\Events\CreateChallenge;
use Workdo\Telegram\Services\SendMsg;

class CreateChallengeLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateChallenge $event)
    {
        $Challenges = $event->challenge;
        if($Challenges->position == 0) {
            $status = 'OnGoing';
        } elseif($Challenges->position == 1) {
            $status = 'OnHold';
        } elseif($Challenges->position == 2) {
            $status = 'Finished';
        }
        if (company_setting('Telegram New Challenge')  == 'on') {

            $uArr = [
                'name'     => $Challenges->challenge_name,
                'position' => $status
            ];
            SendMsg::SendMsgs($uArr , 'New Challenge');
        }
    }
}
