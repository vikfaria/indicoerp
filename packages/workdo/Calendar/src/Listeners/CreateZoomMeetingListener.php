<?php

namespace Workdo\Calendar\Listeners;

use Workdo\ZoomMeeting\Events\CreateZoomMeeting;
use Workdo\Calendar\Models\CalenderUtility;

class CreateZoomMeetingListener
{
    public function handle(CreateZoomMeeting $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarZoomMeeting = $event->meeting;
            $calendarRequest = $event->request;

            $type = 'zoom_meeting';
            $calendarZoomMeeting->title = $calendarRequest->title;
            $calendarZoomMeeting->start_date = $calendarRequest->start_time;
            $calendarZoomMeeting->end_date = $calendarRequest->start_time;

            CalenderUtility::addCalendarData($calendarZoomMeeting, $type, $calendarZoomMeeting->created_by);
        }
    }
}