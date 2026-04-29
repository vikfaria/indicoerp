<?php

namespace Workdo\Webhook\Extractors;

class AppointmentStatusDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->schedule)) return [];

        $schedule = $event->schedule;

        return [
            'appointment_status' => [
                'id' => $schedule->id,
                'unique_id' => $schedule->unique_id,
                'user' => $schedule->user?->name ?? null,
                'name' => $schedule->name,
                'email' => $schedule->email,
                'phone' => $schedule->phone,
                'date' => $schedule->date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'appointment' => $schedule->appointment?->appointment_name ?? null,
                'status' => $schedule->status,
                'created_at' => $schedule->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
