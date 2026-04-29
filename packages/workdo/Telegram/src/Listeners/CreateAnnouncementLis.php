<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Hrm\Events\CreateAnnouncement;
use Workdo\Hrm\Models\Branch;
use Workdo\Telegram\Services\SendMsg;

class CreateAnnouncementLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateAnnouncement $event)
    {
        if(company_setting('Telegram New Announcement')  == 'on')
        {
            $request = $event->request;
            $announcement = $event->announcement;

            if(empty($branch)){
                $branchs = Branch::where('created_by',$announcement->created_by)->get()->pluck('branch_name');
                $branchs_detail = [];
                if (count($branchs) > 0) {
                    foreach ($branchs as $datasand) {
                        $branchs_detail[] = $datasand;
                    }
                }
                $branch = implode(',', $branchs_detail);
            }
            $uArr = [
                'announcement_name' => $request->title,
                'branch_name' => $branch ?? '',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ];
            SendMsg::SendMsgs($uArr , 'New Announcement');
        }
    }
}
