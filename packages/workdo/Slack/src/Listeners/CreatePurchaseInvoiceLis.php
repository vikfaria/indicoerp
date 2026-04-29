<?php

namespace Workdo\Slack\Listeners;

use App\Events\CreatePurchaseInvoice;
use Workdo\Slack\Services\SendMsg;

class CreatePurchaseInvoiceLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreatePurchaseInvoice $event)
    {
        $purchase = $event->purchaseInvoice;

        if (company_setting('Slack New Purchase') == 'on') {
            $uArr = [
                 'purchase_id' => $purchase->invoice_number,
            ];
            
            SendMsg::SendMsgs($uArr, 'New Purchase');
        }
    }
}