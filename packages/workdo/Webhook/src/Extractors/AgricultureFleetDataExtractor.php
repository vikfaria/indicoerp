<?php

namespace Workdo\Webhook\Extractors;

class AgricultureFleetDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturefleet)) return [];

        $agriculturefleet = $event->agriculturefleet;

        return [
            'agriculture_fleet' => [
                'id' => $agriculturefleet->id,
                'fleet_name' => $agriculturefleet->fleet_name ?? null,
                'capacity' => $agriculturefleet->capacity ?? null,
                'price' => $agriculturefleet->price ?? null,
                'quantity' => $agriculturefleet->quantity ?? null,
                'created_at' => $agriculturefleet->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
