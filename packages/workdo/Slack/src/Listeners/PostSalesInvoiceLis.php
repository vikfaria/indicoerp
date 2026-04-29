<?php

namespace Workdo\Slack\Listeners;

use App\Events\PostSalesInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Slack\Services\SendMsg;

class PostSalesInvoiceLis
{
    public function __construct()
    {
        //
    }

    public function handle(PostSalesInvoice $event)
    {
        $invoice = $event->salesInvoice;

        if (company_setting('Slack Sales Invoice Status Updated') == 'on') {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'Sales Invoice Status Updated');
        }
    }
}