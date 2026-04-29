<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Twilio\Services\SendMsg;

class SentSalesProposalLis
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
    public function handle($event)
    {
        if (company_setting('Twilio Proposal Status Updated') == 'on') {

            $proposal = $event->salesProposal;

            $user = User::where('id', $proposal->customer_id)->first();

            if (!empty($user->mobile_no)) {
                $uArr = [];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'Proposal Status Updated');
            }
        }
    }
}
