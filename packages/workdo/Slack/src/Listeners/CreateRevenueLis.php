<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Slack\Services\SendMsg;

class CreateRevenueLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateRevenue $event)
    {
        $revenue = $event->revenue;
        $customer         = User::where('id', $revenue->creator_id)->first();
        if (company_setting('Slack New Revenue') == 'on') {
            $uArr = [
                'amount' => $revenue->amount,
                'user_name' => $customer->name,
            ];

            SendMsg::SendMsgs($uArr, 'New Revenue');
        }
    }
}
