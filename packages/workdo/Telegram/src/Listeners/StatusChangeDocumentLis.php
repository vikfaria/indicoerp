<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Documents\Events\StatusChangeDocument;
use Workdo\Telegram\Services\SendMsg;

class StatusChangeDocumentLis
{
    public function __construct()
    {
        //
    }

    public function handle(StatusChangeDocument $event)
    {
        $documents = $event->document;
        $user      = User::find($documents->creator_id);

        if (company_setting('Telegram Update Status Document')  == 'on') {
            $uArr = [
                'status' => $documents->status,
                'user_name' => !empty($user) ? $user->name : '-'
            ];
            SendMsg::SendMsgs($uArr , 'Update Status Document');
        }
    }
}
