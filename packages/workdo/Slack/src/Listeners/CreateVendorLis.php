<?php

namespace Workdo\Slack\Listeners;

use Workdo\Account\Events\CreateVendor;
use Workdo\Slack\Services\SendMsg;

class CreateVendorLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateVendor $event)
    {
        if (company_setting('Slack New Vendor') == 'on') {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'New Vendor');
        }
    }
}