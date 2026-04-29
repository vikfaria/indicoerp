<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Calendar\Models\CalenderUtility;

class CreateProjectTaskListener
{
    public function handle(CreateProjectTask $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarProjectTask = $event->task;
            $calendarRequest = $event->request;

            $type = 'task';
            $calendarProjectTask->title = $calendarRequest->title;
            
            // Parse duration range (e.g., "2024-01-01 - 2024-01-05")
            $duration = $calendarRequest->duration;
            if ($duration && strpos($duration, ' - ') !== false) {
                $dates = explode(' - ', $duration);
                $calendarProjectTask->start_date = trim($dates[0]) . ' 09:00:00';
                $calendarProjectTask->end_date = trim($dates[1]) . ' 17:00:00';
            } else {
                $calendarProjectTask->start_date = $duration . ' 09:00:00';
                $calendarProjectTask->end_date = $duration . ' 17:00:00';
            }

            CalenderUtility::addCalendarData($calendarProjectTask, $type, $calendarProjectTask->created_by);
        }
    }
}