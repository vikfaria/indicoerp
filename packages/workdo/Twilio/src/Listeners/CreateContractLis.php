<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Contract\Events\CreateContract;
use Workdo\Twilio\Services\SendMsg;

class CreateContractLis
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
    public function handle(CreateContract $event)
    {
        if (company_setting('Twilio New Contract') == 'on') {

            $contract = $event->contract;
            $user     = User::where('id', $contract->user_id)->first();
            $to       = $user->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'contract_number' => $contract->contract_number,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Contract');
            }
        }
    }
}
