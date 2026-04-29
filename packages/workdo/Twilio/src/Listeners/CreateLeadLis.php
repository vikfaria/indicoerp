<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Twilio\Services\SendMsg;
use Workdo\Lead\Events\CreateLead;

class CreateLeadLis
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
    public function handle(CreateLead $event)
    {
        if (company_setting('Twilio New Lead') == 'on') {

            $request    = $event->lead;
            $AssignUser = User::find($request->user_id);
            $to         = $AssignUser->mobile_no;

            if (!empty($to)) {
                $uArr = [];

                SendMsg::SendMsgs($to, $uArr, 'New Lead');
            }
        }
    }
}
