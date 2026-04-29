<?php

namespace Workdo\Slack\Listeners;

use Workdo\Slack\Services\SendMsg;
use Workdo\Training\Events\CreateTrainer;

class CreateTrainerLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateTrainer $event)
    {
        if (company_setting('Slack New Trainer')  == 'on') {
            $trainer = $event->trainer;

            $uArr = [
                'branch_name' => $trainer->branch->branch_name
            ];
            SendMsg::SendMsgs($uArr, 'New Trainer');
        }
    }
}
