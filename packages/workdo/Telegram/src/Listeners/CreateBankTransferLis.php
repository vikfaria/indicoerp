<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Account\Events\CreateBankTransfer;
use Workdo\Telegram\Services\SendMsg;

class CreateBankTransferLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateBankTransfer $event)
    {
        $data = $event->bankTransfer;
        if (company_setting('Telegram Bank Transfer Payment Status Updated')  == 'on') {
            if(!empty($data))
            {
                $uArr = [
                    'invoice_id' => $data->transfer_number
                ];
            }
            SendMsg::SendMsgs($uArr , 'Bank Transfer Payment Status Updated');
        }
    }
}
