<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Hrm\Events\CreateLeaveApplication;
use Workdo\Calendar\Models\CalenderUtility;

class CreateLeaveApplicationListener
{
    public function handle(CreateLeaveApplication $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarLeaveApplication = $event->leaveapplication;
            $calendarRequest = $event->request;

            $type = 'leave';
            $calendarLeaveApplication->title = 'Leave - ' . ($calendarLeaveApplication->employee->name ?? 'Employee');
            $calendarLeaveApplication->start_date = $calendarRequest->start_date . ' 00:00:00';
            $calendarLeaveApplication->end_date = $calendarRequest->end_date . ' 23:59:59';

            CalenderUtility::addCalendarData($calendarLeaveApplication, $type, $calendarLeaveApplication->created_by);
        }
    }
}