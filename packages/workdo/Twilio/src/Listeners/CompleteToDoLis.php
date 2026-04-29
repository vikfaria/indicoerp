<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\ToDo\Events\CompleteToDo;
use Workdo\Twilio\Services\SendMsg;

class CompleteToDoLis
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
    public function handle(CompleteToDo $event)
    {
        if (company_setting('Twilio Complete To Do') == 'on') {

            $toDo  = $event->todo;
            $userId = is_array($toDo->assigned_to) ? $toDo->assigned_to : explode(',', $toDo->assigned_to);
            $users = User::whereIn('id', $userId)->get();

            foreach ($users as $user) {
                if (!empty($user->mobile_no)) {
                    $uArr = [
                        'user_name' => $user->name
                    ];

                    SendMsg::SendMsgs($user->mobile_no, $uArr, 'Complete To Do');
                }
            }
        }
    }
}
