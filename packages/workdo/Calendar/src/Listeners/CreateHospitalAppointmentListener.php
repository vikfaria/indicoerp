<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Calendar\Models\CalenderUtility;
use Workdo\HospitalManagement\Events\UpdateHospitalAppointmentStatus;

class CreateHospitalAppointmentListener
{
    public function handle(UpdateHospitalAppointmentStatus $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarAppointment = $event->hospitalappointment;
            
            if ($calendarAppointment->status == '1') {
                $type = 'hospital_appointment';
                $calendarAppointment->title = $calendarAppointment->patient->name ?? 'Hospital Appointment';
                $calendarAppointment->start_date = $calendarAppointment->appointment_date->format('Y-m-d') . ' ' . $calendarAppointment->start_time;
                $calendarAppointment->end_date = $calendarAppointment->appointment_date->format('Y-m-d') . ' ' . $calendarAppointment->end_time;
                $created_by = $calendarAppointment->created_by ?? null;

                CalenderUtility::addCalendarData($calendarAppointment, $type, $created_by);
            }
        }
    }
}