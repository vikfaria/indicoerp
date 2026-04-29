<?php

namespace Workdo\Webhook\Extractors;

class PropertyDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->property)) return [];

        $property = $event->property;

        return [
            'property' => [
                'id' => $property->id,
                'name' => $property->name ?? null,
                'address' => $property->address ?? null,
                'city' => $property->city ?? null,
                'state' => $property->state ?? null,
                'country' => $property->country ?? null,
                'zip_code' => $property->zip_code ?? null,
                'latitude' => $property->latitude ?? null,
                'longitude' => $property->longitude ?? null,
                'security_deposit' => $property->security_deposit ?? null,
                'maintenance_charge' => $property->maintenance_charge ?? null,
                'description' => $property->description ?? null,
                'created_at' => $property->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
