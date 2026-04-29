<?php

namespace Workdo\Webhook\Extractors;

class VehicleTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->vehicletype)) return [];

        $vehicletype = $event->vehicletype;

        return [
            'vehicle_type' => [
                'id' => $vehicletype->id,
                'name' => $vehicletype->name,
                'created_at' => $vehicletype->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
