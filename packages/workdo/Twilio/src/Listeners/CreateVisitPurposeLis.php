<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\VisitorManagement\Events\CreateVisitPurpose;
use Workdo\Twilio\Services\SendMsg;

class CreateVisitPurposeLis
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
    public function handle(CreateVisitPurpose $event)
    {
        if (company_setting('Twilio New Visit Reason') == 'on') {

            $visitPurpose = $event->visitpurpose;

            $user = User::find($visitPurpose->created_by);

            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name' => $visitPurpose->name
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Visit Reason');
            }
        }
    }
}
