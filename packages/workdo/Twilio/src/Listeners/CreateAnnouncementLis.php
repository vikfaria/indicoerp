<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Hrm\Events\CreateAnnouncement;
use Workdo\Twilio\Services\SendMsg;

class CreateAnnouncementLis
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
    public function handle(CreateAnnouncement $event)
    {
        if (company_setting('Twilio New Announcement') == 'on') {

            $request      = $event->request;
            $announcement = $event->announcement;

            $branch_name = $announcement->departments ?? '';
            $to          = \Auth::user()->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'announcement_name' => $request->title,
                    'branch_name'       => $branch_name ?? '',
                    'start_date'        => $request->start_date,
                    'end_date'          => $request->end_date
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Announcement');
            }

        }
    }
}
