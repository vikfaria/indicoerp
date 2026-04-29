<?php

namespace Workdo\Slack\Listeners;

use Workdo\Contract\Events\CreateContract;
use Workdo\Contract\Models\Contract;
use Workdo\Slack\Services\SendMsg;

class CreateContractLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateContract $event)
    {
        $contract = $event->contract;

        if (company_setting('Slack New Contract') == 'on') {
            $uArr = [
                'contract_number' => $contract->contract_number,
            ];

            SendMsg::SendMsgs($uArr, 'New Contract');
        }
    }
}
