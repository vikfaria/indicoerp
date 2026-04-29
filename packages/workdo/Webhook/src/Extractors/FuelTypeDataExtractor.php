<?php

namespace Workdo\Webhook\Extractors;

class FuelTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fueltype)) return [];

        $fueltype = $event->fueltype;

        return [
            'fuel_type' => [
                'id' => $fueltype->id,
                'name' => $fueltype->name,
                'created_at' => $fueltype->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
