<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\Calendar\Models\CalenderUtility;

class CreateSalesMeetingListener
{
    public function handle(CreateSalesMeeting $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarSalesMeeting = $event->meeting;
            $calendarRequest = $event->request;

            $type = 'meeting';
            $calendarSalesMeeting->title = $calendarRequest->name;
            $calendarSalesMeeting->start_date = $calendarRequest->start_date;
            $calendarSalesMeeting->end_date = $calendarRequest->end_date;

            CalenderUtility::addCalendarData($calendarSalesMeeting, $type, $calendarSalesMeeting->created_by);
        }
    }
}