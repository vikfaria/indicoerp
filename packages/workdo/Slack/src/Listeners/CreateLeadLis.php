<?php

namespace Workdo\Slack\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Lead\Events\CreateLead;
use Workdo\Slack\Services\SendMsg;

class CreateLeadLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateLead $event)
    {
        if (company_setting('Slack New Lead') == 'on') {
            $uArr = [];

            SendMsg::SendMsgs($uArr, 'New Lead');
        }
    }
}