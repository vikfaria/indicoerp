<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CleaningManagement\Events\CreateCleaningTeam;
use Workdo\Twilio\Services\SendMsg;

class CreateCleaningTeamLis
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
    public function handle(CreateCleaningTeam $event)
    {
        if (company_setting('Twilio New Cleaning Team') == 'on') {

            $cleaning_team = $event->cleaningTeam;

            $users = User::whereIn('id', $cleaning_team->user_id)->get();

            foreach ($users as $user) {
                if (!empty($cleaning_team) && !empty($user->mobile_no)) {
                    $uArr = [
                        'team_name' => $cleaning_team->name
                    ];

                    SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Cleaning Team');
                }
            }
        }
    }
}
