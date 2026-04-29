<?php

namespace Workdo\Slack\Listeners;

use Workdo\CMMS\Events\CreateSupplier;
use Workdo\Slack\Services\SendMsg;

class CreateSupplierLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSupplier $event)
    {
        $request = $event->request;
        $user = $request->name;
        
        if (company_setting('Slack New Supplier') == 'on') {
            $uArr = [
                'user_name' => $user,
            ];

            SendMsg::SendMsgs($uArr, 'New Supplier');
        }
    }
}
