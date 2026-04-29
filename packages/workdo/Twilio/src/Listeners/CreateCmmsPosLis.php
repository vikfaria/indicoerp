<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CMMS\Events\CreateCmmspos;
use Workdo\Twilio\Services\SendMsg;

class CreateCmmsPosLis
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
    public function handle(CreateCmmsPos $event)
    {
        if (company_setting('Twilio New POs') == 'on') {

            $request = $event->request;
            $user    = User::find($request->user_id);
            $to      = \Auth::user()->mobile_no;

            if (!empty($user) && !empty($to)) {
                $uArr = [
                    'user_name' => $user->name,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New POs');
            }
        }
    }
}
