<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Hrm\Events\CreateEvent;
use Workdo\Calendar\Models\CalenderUtility;

class CreateEventListener
{
    public function handle(CreateEvent $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarEvent = $event->event;
            $calendarRequest = $event->request;

            $type = 'event';
            $calendarEvent->title = $calendarRequest->title;
            $calendarEvent->start_date = $calendarRequest->start_date . ' ' . $calendarRequest->start_time;
            $calendarEvent->end_date = $calendarRequest->end_date . ' ' . $calendarRequest->end_time;

            CalenderUtility::addCalendarData($calendarEvent, $type, $calendarEvent->created_by);
        }
    }
}