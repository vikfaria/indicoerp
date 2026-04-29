<?php

namespace Workdo\Telegram\Listeners;

use App\Events\PostSalesInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Telegram\Services\SendMsg;

class PostSalesInvoiceLis
{
    public function __construct()
    {
        //
    }

    public function handle(PostSalesInvoice $event)
    {
        if(company_setting('Telegram Sales Invoice Status Updated')  == 'on')
        {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'Sales Invoice Status Updated');
        }
    }
}
