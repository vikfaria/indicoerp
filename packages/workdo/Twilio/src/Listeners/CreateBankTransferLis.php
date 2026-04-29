<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Account\Events\CreateBankTransfer;
use Workdo\Twilio\Services\SendMsg;

class CreateBankTransferLis
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
    public function handle(CreateBankTransfer $event)
    {
        if (company_setting('Twilio Bank Transfer Payment Status Updated') == 'on') {

            $data = $event->bankTransfer;

            $to = \Auth::user()->mobile_no;


            if (!empty($data) && !empty($to)) {
                $uArr = [
                    'invoice_id' => $data->transfer_number
                ];

                SendMsg::SendMsgs($to, $uArr, 'Bank Transfer Payment Status Updated');
            }

        }
    }
}
