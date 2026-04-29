<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Account\Models\Customer;
use Workdo\Telegram\Services\SendMsg;

class CreateRevenueLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateRevenue $event)
    {
        if(company_setting('Telegram New Revenue')  == 'on')
        {
            $request = $event->request;
            $revenue = $event->revenue;
            $customer = User::where('id', $revenue->creator_id)->first();
            $uArr = [
                'amount' => $request->amount    ,
                'user_name' => $customer['name'],
            ];
            SendMsg::SendMsgs($uArr , 'New Revenue');
        }
    }
}
