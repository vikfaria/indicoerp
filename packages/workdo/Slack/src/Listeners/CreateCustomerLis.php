<?php

namespace Workdo\Slack\Listeners;

use Workdo\Account\Events\CreateCustomer;
use Workdo\Slack\Services\SendMsg;

class CreateCustomerLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCustomer $event)
    {
        if (company_setting('Slack New Customer') == 'on') {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'New Customer');
        }
    }
}