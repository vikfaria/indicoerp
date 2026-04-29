<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Calendar\Models\CalenderUtility;

class CreateGoogleMeetingListener
{
    public function handle($event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarGoogleMeeting = $event->googleMeeting;
            $calendarRequest = $event->request;
            
            $type = 'google_meet';
            $calendarGoogleMeeting->title = $calendarRequest->title;
            $calendarGoogleMeeting->start_date = $calendarRequest->start_time;
            $calendarGoogleMeeting->end_date = $calendarRequest->start_time;

            CalenderUtility::addCalendarData($calendarGoogleMeeting, $type, $calendarGoogleMeeting->created_by);
        }
    }
}
