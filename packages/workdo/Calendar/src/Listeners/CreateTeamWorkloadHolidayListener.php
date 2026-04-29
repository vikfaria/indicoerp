<?php

namespace Workdo\Calendar\Listeners;

use Workdo\TeamWorkload\Events\CreateTeamWorkloadHoliday;
use Workdo\Calendar\Models\CalenderUtility;

class CreateTeamWorkloadHolidayListener
{
    public function handle(CreateTeamWorkloadHoliday $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarHoliday = $event->holiday;
            $calendarRequest = $event->request;

            $type = 'teamworkload_holiday';
            $calendarHoliday->title = $calendarRequest->occasion;
            $calendarHoliday->start_date = $calendarRequest->start_date;
            $calendarHoliday->end_date = $calendarRequest->end_date;

            CalenderUtility::addCalendarData($calendarHoliday, $type, $calendarHoliday->created_by);
        }
    }
}