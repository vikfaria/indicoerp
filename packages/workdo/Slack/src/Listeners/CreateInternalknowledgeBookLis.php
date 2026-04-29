<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeBook;
use Workdo\Slack\Services\SendMsg;

class CreateInternalknowledgeBookLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateInternalknowledgeBook $event)
    {
        $book = $event->internalknowledgeBook;

        $userIds = is_array($book->users)? $book->users : explode(',', $book->users);
        $userNames = User::whereIn('id', $userIds)->pluck('name')->toArray();
        $userList = implode(', ', $userNames);

        if (company_setting('Slack New Book') == 'on') {
            $uArr = [
                'name' => $book->title,
                'user_name' => $userList,
            ];

            SendMsg::SendMsgs($uArr, 'New Book');
        }
    }
}