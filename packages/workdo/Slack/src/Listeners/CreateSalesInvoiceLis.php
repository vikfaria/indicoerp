<?php

namespace Workdo\Slack\Listeners;

use App\Events\CreateSalesInvoice;
use Workdo\Slack\Services\SendMsg;

class CreateSalesInvoiceLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesInvoice $event)
    {
        $invoice = $event->salesInvoice;

        if (company_setting('Slack New Sales Invoice') == 'on') {
            $uArr = [
                'sales_invoice_id' => $invoice->invoice_number
            ];

            SendMsg::SendMsgs($uArr, 'New Sales Invoice');
        }
    }
}