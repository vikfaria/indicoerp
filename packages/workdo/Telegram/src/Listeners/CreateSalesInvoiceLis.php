<?php

namespace Workdo\Telegram\Listeners;

use App\Events\CreateSalesInvoice;
use Workdo\Telegram\Services\SendMsg;


class CreateSalesInvoiceLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateSalesInvoice $event)
    {
        if(company_setting('Telegram New Sales Invoice')  == 'on')
        {
            $invoice = $event->salesInvoice;
            if(!empty($invoice))
            {
                $uArr = [
                    'sales_invoice_id' => $invoice->invoice_number
                ];
                SendMsg::SendMsgs($uArr , 'New Sales Invoice');
            }
        }
    }
}
