<?php

namespace Workdo\Telegram\Listeners;

use Workdo\CleaningManagement\Events\CreateCleaningTeam;
use Workdo\Telegram\Services\SendMsg;

class CreateCleaningTeamLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCleaningTeam $event)
    {
        $cleaning_team = $event->cleaningTeam;
        if (company_setting('Telegram New Cleaning Team')  == 'on') {

            if(!empty($cleaning_team))
            {
                $uArr = [
                    'team_name' => $cleaning_team->name
                ];
                SendMsg::SendMsgs($uArr , 'New Cleaning Team');
            }
        }
    }
}
