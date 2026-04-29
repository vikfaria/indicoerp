<?php

namespace Workdo\Slack\Listeners;

use Workdo\CleaningManagement\Events\CreateCleaningTeam;
use Workdo\Slack\Services\SendMsg;

class CreateCleaningTeamLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCleaningTeam $event)
    {
        $cleaning_team = $event->cleaningTeam;

        if (company_setting('Slack New Cleaning Team') == 'on') {
            $uArr = [
                'team_name' => $cleaning_team->name
            ];

            SendMsg::SendMsgs($uArr, 'New Cleaning Team');
        }
    }
}