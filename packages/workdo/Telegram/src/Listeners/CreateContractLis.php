<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Contract\Events\CreateContract;


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
        if(company_setting('Telegram New Contract')  == 'on')
        {
            $contract = $event->contract;
            if(!empty($contract))
            {
                $uArr = [
                    'contract_number' => $contract->contract_number
                ];
                SendMsg::SendMsgs($uArr , 'New Contract');
            }
        }
    }
}
