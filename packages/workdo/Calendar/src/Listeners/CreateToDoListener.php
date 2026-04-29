<?php

namespace Workdo\Calendar\Listeners;

use Workdo\ToDo\Events\CreateToDo;
use Workdo\Calendar\Models\CalenderUtility;

class CreateToDoListener
{
    public function handle(CreateToDo $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarToDo = $event->todo;
            $calendarRequest = $event->request;

            $type = 'todo';
            $calendarToDo->title = $calendarRequest->title;
            
            // Parse duration range (e.g., "2024-01-01 - 2024-01-05")
            $duration = $calendarRequest->duration;
            if ($duration && strpos($duration, ' - ') !== false) {
                $dates = explode(' - ', $duration);
                $calendarToDo->start_date = trim($dates[0]) . ' 09:00:00';
                $calendarToDo->end_date = trim($dates[1]) . ' 17:00:00';
            } else {
                $calendarToDo->start_date = $duration . ' 09:00:00';
                $calendarToDo->end_date = $duration . ' 17:00:00';
            }

            CalenderUtility::addCalendarData($calendarToDo, $type, $calendarToDo->created_by);
        }
    }
}