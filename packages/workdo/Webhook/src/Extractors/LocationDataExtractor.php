<?php

namespace Workdo\Webhook\Extractors;

class LocationDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->location)) return [];

        $location = $event->location;

        return [
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'created_at' => $location->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
