<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Hrm\Events\CreateHoliday;
use Workdo\Telegram\Services\SendMsg;

class CreateHolidayLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHoliday $event)
    {
        if(company_setting('Telegram New Holidays')  == 'on')
        {
            $request = $event->request;
            $uArr = [
                'name' => $request->name,
                'start_date'=>$request->start_date,
                'end_date' => $request->end_date
            ];
            SendMsg::SendMsgs($uArr , 'New Holidays');
        }
    }
}
