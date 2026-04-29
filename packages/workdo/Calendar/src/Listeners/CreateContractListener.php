<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Calendar\Models\CalenderUtility;

class CreateContractListener
{
    public function handle($event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarContract = $event->contract;
            $calendarRequest = $event->request;
            
            $type = 'contract_end';
            $calendarContract->title = $calendarRequest->subject;
            $calendarContract->start_date = $calendarRequest->start_date;
            $calendarContract->end_date = $calendarRequest->end_date;

            CalenderUtility::addCalendarData($calendarContract, $type, $calendarContract->created_by);
        }
    }
}
