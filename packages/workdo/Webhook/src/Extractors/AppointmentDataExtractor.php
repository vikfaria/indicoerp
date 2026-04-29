<?php

namespace Workdo\Webhook\Extractors;

class AppointmentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->appointment)) return [];

        $appointment = $event->appointment;
        $appointment_type = $appointment->appointment_type == 0 ? 'Paid' : 'Free';

        // Format week_day from JSON array to comma-separated string
        $weekDays = null;
        if ($appointment->week_day) {
            $weekDayArray = json_decode($appointment->week_day, true);
            if (is_array($weekDayArray)) {
                $weekDays = implode(', ', $weekDayArray);
            }
        }

        return [
            'appointment' => [
                'id' => $appointment->id,
                'appointment_name' => $appointment->appointment_name,
                'appointment_type' => $appointment_type,
                'week_day' => $weekDays,
                'duration' => $appointment->duration,
                'created_at' => $appointment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
