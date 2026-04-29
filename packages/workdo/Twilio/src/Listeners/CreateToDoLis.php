<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\ToDo\Events\CreateToDo;
use Workdo\Twilio\Services\SendMsg;

class CreateToDoLis
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
    public function handle(CreateToDo $event)
    {
        if (company_setting('Twilio New To Do') == 'on') {

            $toDo = $event->todo;

            $userIds = is_array($toDo->assigned_to) ? $toDo->assigned_to : explode(',', $toDo->assigned_to ?? '');
            $users   = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                if (!empty($user->mobile_no)) {
                    $uArr = [
                        'name'   => $toDo->title,
                        'module' => $toDo->sub_module
                    ];

                    SendMsg::SendMsgs($user->mobile_no, $uArr, 'New To Do');
                }
            }
        }
    }
}
