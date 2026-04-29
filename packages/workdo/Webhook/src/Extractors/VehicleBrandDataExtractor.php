<?php

namespace Workdo\Webhook\Extractors;

class VehicleBrandDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->vehiclebrand)) return [];

        $vehiclebrand = $event->vehiclebrand;

        return [
            'vehicle_brand' => [
                'id' => $vehiclebrand->id,
                'name' => $vehiclebrand->name,
                'created_at' => $vehiclebrand->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
