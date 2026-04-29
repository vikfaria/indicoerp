<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Twilio\Services\SendMsg;

class CreateRevenueLis
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
    public function handle(CreateRevenue $event)
    {
        if (company_setting('Twilio New Revenue') == 'on') {
            $request    = $event->request;
            $AssignUser = \Auth::user();

            if (!empty($AssignUser->mobile_no)) {
                $uArr = [
                    'amount'    => $request->amount,
                    'user_name' => $AssignUser->name,
                ];

                SendMsg::SendMsgs($AssignUser->mobile_no, $uArr, 'New Revenue');
            }
        }
    }
}
