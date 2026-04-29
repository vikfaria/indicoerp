<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use App\Events\CreatePurchaseInvoice;
use Workdo\Twilio\Services\SendMsg;

class CreatePurchaseInvoiceLis
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
    public function handle(CreatePurchaseInvoice $event)
    {
        if (company_setting('Twilio New Purchase Invoice') == 'on') {
            $request         = $event->request;
            $purchaseInvoice = $event->purchaseInvoice;

            $AssignUser = User::find($request->vendor_id);
            $to         = $AssignUser->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'purchase_id' => $purchaseInvoice->invoice_number,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Purchase Invoice');
            }
        }
    }
}
