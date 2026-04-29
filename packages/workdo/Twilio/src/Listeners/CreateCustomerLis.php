<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Account\Events\CreateCustomer;
use Workdo\Twilio\Services\SendMsg;

class CreateCustomerLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCustomer $event)
    {
        if (company_setting('Twilio New Customer') == 'on') {

            $user = User::find($event->customer->user_id);

            if (!empty($user->mobile_no)) {
                $uArr = [];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Customer');
            }
        }
    }
}
