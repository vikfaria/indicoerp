<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Sales\Events\CreateSalesCall;
use Workdo\Calendar\Models\CalenderUtility;

class CreateSalesCallListener
{
    public function handle(CreateSalesCall $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarSalesCall = $event->salesCall;
            $calendarRequest = $event->request;

            $type = 'call';
            $calendarSalesCall->title = $calendarRequest->name;
            $calendarSalesCall->start_date = $calendarRequest->start_date;
            $calendarSalesCall->end_date = $calendarRequest->end_date;

            CalenderUtility::addCalendarData($calendarSalesCall, $type, $calendarSalesCall->created_by);
        }
    }
}