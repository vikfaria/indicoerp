<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Recruitment\Events\CreateInterview;
use Workdo\Calendar\Models\CalenderUtility;

class CreateInterviewListener
{
    public function handle(CreateInterview $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarInterview = $event->interview;
            $calendarRequest = $event->request;

            $type = 'interview_schedule';
            $calendarInterview->title = 'Interview - ' . ($calendarInterview->candidate->first_name ?? '') . ' ' . ($calendarInterview->candidate->last_name ?? '');
            $calendarInterview->start_date = $calendarRequest->scheduled_date . ' ' . $calendarRequest->scheduled_time;
            
            // Calculate end date based on duration
            $startDateTime = new \DateTime($calendarInterview->start_date);
            $duration = (int) $calendarRequest->duration;
            $startDateTime->add(new \DateInterval('PT' . $duration . 'M'));
            $calendarInterview->end_date = $startDateTime->format('Y-m-d H:i:s');

            CalenderUtility::addCalendarData($calendarInterview, $type, $calendarInterview->created_by);
        }
    }
}