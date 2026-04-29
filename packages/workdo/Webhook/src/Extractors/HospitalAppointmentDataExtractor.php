<?php

namespace Workdo\Webhook\Extractors;

class HospitalAppointmentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalappointment)) return [];

        $hospitalappointment = $event->hospitalappointment;
        $hospitalDoctor = $hospitalappointment->doctor ?? null;
        $hospitalDoctorItem = $hospitalDoctor?->user ?? null;

        return [
            'hospital_appointment' => [
                'id' => $hospitalappointment->id,
                'appointment_date' => $hospitalappointment->appointment_date,
                'start_time' => $hospitalappointment->start_time,
                'end_time' => $hospitalappointment->end_time,
                'reason' => $hospitalappointment->reason,
                'notes' => $hospitalappointment->notes,
                'consultation_fee' => $hospitalappointment->consultation_fee,
                'patient' => $hospitalappointment->patient?->name ?? null,
                'doctor' => $hospitalDoctorItem->name ?? null,
                'appointment_number' => $hospitalappointment->appointment_number,
                'created_at' => $hospitalappointment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
