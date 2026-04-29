<?php

namespace Workdo\Twilio\Listeners;

use App\Events\CreateSalesInvoice;
use Workdo\Twilio\Services\SendMsg;

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
        if (company_setting('Twilio New Sales Invoice') == 'on') {
            $invoice = $event->salesInvoice;
            $to      = \Auth::user()->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'sales_invoice_id' => $invoice->invoice_number
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Sales Invoice');
            }
        }
    }
}
