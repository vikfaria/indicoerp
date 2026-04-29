<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentLocationDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentLocation)) return [];

        $fixEquipmentLocation = $event->fixEquipmentLocation;

        return [
            'fixequipment_location' => [
                'id' => $fixEquipmentLocation->id,
                'name' => $fixEquipmentLocation->name,
                'address' => $fixEquipmentLocation->address,
                'description' => $fixEquipmentLocation->description,
                'created_at' => $fixEquipmentLocation->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
