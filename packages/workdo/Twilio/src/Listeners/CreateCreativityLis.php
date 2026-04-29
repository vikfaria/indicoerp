<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\InnovationCenter\Events\CreateCreativity;
use Workdo\InnovationCenter\Models\Challenge;
use Workdo\Twilio\Services\SendMsg;

class CreateCreativityLis
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
    public function handle(CreateCreativity $event)
    {
        if (company_setting('Twilio New Creativity') == 'on') {

            $creativity = $event->creativity;

            $challenge = Challenge::find($creativity->challenge_id);
            $user      = User::find($creativity->created_by);

            if (!empty($user->mobile_no) && !empty($challenge)) {
                $uArr = [
                    'name'      => $creativity->creativity_name,
                    'challenge' => $challenge->challenge_name ?? null,
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Creativity');
            }
        }
    }
}
