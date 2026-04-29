<?php

namespace Workdo\Slack\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Sales\Events\CreateSalesQuote;
use Workdo\Slack\Services\SendMsg;

class CreateSalesQuoteLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesQuote $event)
    {
        $quote = $event->quote;
        if (company_setting('Slack New Quote') == 'on') {
            $uArr = [
                'quotation_id' => $quote->quote_number,
            ];

            SendMsg::SendMsgs($uArr, 'New Quote');
        }
    }
}