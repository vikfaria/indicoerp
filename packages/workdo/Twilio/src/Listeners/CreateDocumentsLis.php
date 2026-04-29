<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Documents\Events\CreateDocument;
use Workdo\Twilio\Services\SendMsg;

class CreateDocumentsLis
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
    public function handle(CreateDocument $event)
    {
        if (company_setting('Twilio New Document') == 'on') {

            $documents = $event->document;
            $users     = $documents->assignedUsers;

            foreach ($users as $user) {
                if (!empty($user->mobile_no)) {
                    $uArr = [
                        'name'      => $documents->subject,
                        'user_name' => $user->name
                    ];

                    SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Document');
                }
            }
        }
    }
}
