<?php

namespace Workdo\Webhook\Extractors;

class HrmHolidayDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->holiday)) return [];

        $holiday = $event->holiday;

        return [
            'holiday' => [
                'id' => $holiday->id,
                'name' => $holiday->name ?? null,
                'start_date' => $holiday->start_date ?? null,
                'end_date' => $holiday->end_date ?? null,
                'holiday_type' => $holiday->holidayType ? $holiday->holidayType->holiday_type : null,
                'description' => $holiday->description ?? null,
                'created_at' => $holiday->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
