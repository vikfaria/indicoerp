<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Notes\Events\CreateNote;
use Workdo\Telegram\Services\SendMsg;

class CreateNoteLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateNote $event)
    {
        $note = $event->note;

        if ($note->type == 0) {
            $noteType = 'Personal';
            $userList = User::find($note->created_by)->name;
        } else {
            $noteType = 'Shared';
            $assignedUsers = is_array($note->assigned_users) ? $note->assigned_users : explode(',', $note->assigned_users);
            $userNames = User::whereIn('id', $assignedUsers)->pluck('name')->toArray();
            $userList = implode(', ', $userNames);
        }

        if (company_setting('Slack New Note') == 'on') {
            $uArr = [
                'note_type' => $noteType,
                'user_name' => $userList,
            ];

            SendMsg::SendMsgs($uArr, 'New Note');
        }
    }
}
