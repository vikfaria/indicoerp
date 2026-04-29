<?php

namespace Workdo\Webhook\Extractors;

class BeautyServiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->service)) return [];

        $service = $event->service;

        return [
            'beauty_service' => [
                'id' => $service->id,
                'name' => $service->name ?? null,
                'max_bookable_persons' => $service->max_bookable_persons ?? null,
                'price' => $service->price ?? null,
                'time' => $service->time ?? null,
                'description' => $service->description ?? null,
                'service_type' => $service->service_type ? $service->service_type->name : null,
                'staff' => $service->staff ? $service->staff->name : null,
                'created_at' => $service->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
