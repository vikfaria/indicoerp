<?php

namespace Workdo\Webhook\Extractors;

class HrmEventDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->event)) return [];

        $event = $event->event;

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title ?? null,
                'event_type' => $event->eventType ? $event->eventType->event_type : null,
                'start_date' => $event->start_date ?? null,
                'end_date' => $event->end_date ?? null,
                'start_time' => $event->start_time ?? null,
                'end_time' => $event->end_time ?? null,
                'location' => $event->location ?? null,
                'description' => $event->description ?? null,
                'color' => $event->color ?? null,
                'status' => $event->status ?? null,
                'created_at' => $event->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
