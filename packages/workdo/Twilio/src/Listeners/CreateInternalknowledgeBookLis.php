<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeBook;
use Workdo\Twilio\Services\SendMsg;

class CreateInternalknowledgeBookLis
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
    public function handle(CreateInternalknowledgeBook $event)
    {
        if (company_setting('Twilio New Book') == 'on') {

            $book  = $event->internalknowledgeBook;
            $users = User::whereIn('id', $book->users)->get();

            foreach ($users as $user) {
                if (!empty($user->mobile_no)) {
                    $uArr = [
                        'name'      => $book->title,
                        'user_name' => $user->name
                    ];

                    SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Book');
                }
            }
        }

    }
}
