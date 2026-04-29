<?php

namespace Workdo\Webhook\Extractors;

class SchoolClassTimetableDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->timetable)) return [];

        $timetable = $event->timetable;

        return [
            'school_class_timetable' => [
                'id' => $timetable->id,
                'class' => $timetable->class?->name ?? null,
                'subject' => $timetable->subject?->name ?? null,
                'teacher' => $timetable->teacher?->name ?? null,
                'day_of_week' => $timetable->day_of_week,
                'start_time' => $timetable->start_time,
                'end_time' => $timetable->end_time,
                'created_at' => $timetable->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
