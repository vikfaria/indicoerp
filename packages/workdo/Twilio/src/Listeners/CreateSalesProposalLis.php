<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use App\Events\CreateSalesProposal;
use Workdo\Twilio\Services\SendMsg;

class CreateSalesProposalLis
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
    public function handle(CreateSalesProposal $event)
    {
        if (company_setting('Twilio New Proposal') == 'on') {

            $request = $event->request;
            $user    = User::find($request->customer_id);

            if (!empty($user->mobile_no)) {
                $uArr = [];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Proposal');
            }
        }
    }
}
