<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Calendar\Models\CalenderUtility;

class CreateHolidayListener
{
    public function handle($event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarHoliday = $event->holiday;
            $calendarRequest = $event->request;

            $type = 'holiday';
            $calendarHoliday->title = $calendarRequest->name;
            $calendarHoliday->start_date = $calendarRequest->start_date;
            $calendarHoliday->end_date = $calendarRequest->end_date;

            CalenderUtility::addCalendarData($calendarHoliday, $type, $calendarHoliday->created_by);
        }
    }
}
