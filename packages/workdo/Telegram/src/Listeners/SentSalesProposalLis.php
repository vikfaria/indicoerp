<?php

namespace Workdo\Telegram\Listeners;

use App\Events\SentSalesProposal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Telegram\Services\SendMsg;

class SentSalesProposalLis
{
    public function __construct()
    {
        //
    }

    public function handle(SentSalesProposal $event)
    {
        if(company_setting('Telegram Proposal Status Updated')  == 'on')
        {
            $uArr = [];
            SendMsg::SendMsgs($uArr,'Proposal Status Updated');
        }
    }
}
