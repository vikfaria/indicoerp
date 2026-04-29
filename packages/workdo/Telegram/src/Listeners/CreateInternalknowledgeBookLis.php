<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeBook;
use Workdo\Telegram\Services\SendMsg;

class CreateInternalknowledgeBookLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateInternalknowledgeBook $event)
    {
        $book = $event->internalknowledgeBook;
        $usersIds    = is_array($book->users) ? $book->users : explode(', ', $book->users);
        $user_detail = User::whereIn('id', $usersIds)->pluck('name')->toArray();
        $user = implode(',', $user_detail);
        if (company_setting('Telegram New Book')  == 'on') {

            $uArr = [
                'name'      => $book->title,
                'user_name' => $user
            ];
            SendMsg::SendMsgs($uArr , 'New Book');
        }
    }
}
