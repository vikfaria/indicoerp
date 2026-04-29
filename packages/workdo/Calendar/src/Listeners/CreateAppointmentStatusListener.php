<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Calendar\Models\CalenderUtility;

class CreateAppointmentStatusListener
{
    public function handle(AppointmentStatus $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarSchedule = $event->schedule;
            
            if ($calendarSchedule->status == 'approved') {
                $type = 'appointment';
                $calendarSchedule->title = $calendarSchedule->name ?? 'Appointment';
                $calendarSchedule->start_date = $calendarSchedule->date->format('Y-m-d') . ' ' . $calendarSchedule->start_time;
                $calendarSchedule->end_date = $calendarSchedule->date->format('Y-m-d') . ' ' . $calendarSchedule->end_time;
                $created_by = $calendarSchedule->created_by ?? null;

                CalenderUtility::addCalendarData($calendarSchedule, $type, $created_by);
            }
        }
    }
}