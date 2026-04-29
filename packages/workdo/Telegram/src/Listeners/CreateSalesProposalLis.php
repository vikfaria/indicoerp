<?php

namespace Workdo\Telegram\Listeners;

use App\Events\CreateSalesProposal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Telegram\Services\SendMsg;

class CreateSalesProposalLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesProposal $event)
    {
        if(company_setting('Telegram New Proposal')  == 'on')
        {
            $uArr = [];
            SendMsg::SendMsgs($uArr , 'New Proposal');
        }
    }
}
