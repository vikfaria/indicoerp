<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\InnovationCenter\Events\CreateChallenge;
use Workdo\Twilio\Services\SendMsg;

class CreateChallengeLis
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
    public function handle(CreateChallenge $event)
    {
        if (company_setting('Twilio New Challenge') == 'on') {

            $Challenges = $event->challenge;

            $user = User::find($Challenges->created_by);

            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name'     => $Challenges->challenge_name,
                    'position' => $Challenges->position == 0 ? 'OnGoing' : ($Challenges->position == 1 ? 'OnHold' : 'Finished')
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Challenge');
            }
        }
    }
}
