<?php

namespace Workdo\Telegram\Listeners;

use App\Events\CreatePurchaseInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Telegram\Services\SendMsg;

class CreatePurchaseInvoiceLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreatePurchaseInvoice $event)
    {
        if(company_setting('Telegram New Purchase')  == 'on')
        {
            $purchase = $event->purchaseInvoice;
            $uArr = [
                'purchase_id' => $purchase->invoice_number
            ];
            SendMsg::SendMsgs($uArr, 'New Purchase');
        }
    }
}
