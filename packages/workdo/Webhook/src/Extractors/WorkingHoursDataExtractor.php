<?php

namespace Workdo\Webhook\Extractors;

class WorkingHoursDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->workingHours)) return [];

        $workingHours = $event->workingHours;

        return [
            'working_hours' => [
                'id' => $workingHours->id,
                'opening_time' => $workingHours->opening_time ?? null,
                'closing_time' => $workingHours->closing_time ?? null,
                'day_of_week' => $workingHours->day_of_week ?? null,
                'created_at' => $workingHours->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
