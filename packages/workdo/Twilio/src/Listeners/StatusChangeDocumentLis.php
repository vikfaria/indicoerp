<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Documents\Events\StatusChangeDocument;
use Workdo\Twilio\Services\SendMsg;

class StatusChangeDocumentLis
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
    public function handle(StatusChangeDocument $event)
    {
        if (company_setting('Twilio Update Status Document') == 'on') {
            $documents = $event->document;

            $user    = \Auth::user();
            $company = User::find(creatorId());

            if (!empty($company->mobile_no)) {
                $uArr = [
                    'status'    => $documents->status,
                    'user_name' => !empty($user) ? $user->name : '-'
                ];

                SendMsg::SendMsgs($company->mobile_no, $uArr, 'Update Status Document');
            }
        }
    }
}
