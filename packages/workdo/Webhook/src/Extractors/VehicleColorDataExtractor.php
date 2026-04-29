<?php

namespace Workdo\Webhook\Extractors;

class VehicleColorDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->vehiclecolor)) return [];

        $vehiclecolor = $event->vehiclecolor;

        return [
            'vehicle_color' => [
                'id' => $vehiclecolor->id,
                'name' => $vehiclecolor->name,
                'created_at' => $vehiclecolor->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
