<?php

namespace Workdo\Slack\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Lead\Events\CreateDeal;
use Workdo\Slack\Services\SendMsg;

class CreateDealLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateDeal $event)
    {
        if (company_setting('Slack New Deal') == 'on') {
            $uArr = [];

            SendMsg::SendMsgs($uArr, 'New Deal');
        }
    }
}