<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Documents\Events\CreateDocument;
use Workdo\Slack\Services\SendMsg;

class CreateDocumentLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateDocument $event)
    {
        $documents = $event->document;
        $user = User::find($documents->created_by);

        if (company_setting('Slack New Document') == 'on') {
            $uArr = [
                'name' => $documents->subject,
                'user_name' => !empty($user) ? $user->name : '-'
            ];

            SendMsg::SendMsgs($uArr, 'New Document');
        }
    }
}
