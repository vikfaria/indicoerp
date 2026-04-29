<?php

namespace Workdo\Slack\Listeners;

use App\Events\CreateSalesProposal;
use Workdo\Slack\Services\SendMsg;

class CreateSalesProposalLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesProposal $event)
    {
        if (company_setting('Slack New Sales Proposal') == 'on') {
            $uArr = [];
            SendMsg::SendMsgs($uArr, 'New Sales Proposal');
        }
    }
}