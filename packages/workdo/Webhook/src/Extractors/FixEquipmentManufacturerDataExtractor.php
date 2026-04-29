<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentManufacturerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixequipmentmanufacturer)) return [];

        $fixequipmentmanufacturer = $event->fixequipmentmanufacturer;

        return [
            'fixequipment_manufacturer' => [
                'id' => $fixequipmentmanufacturer->id,
                'name' => $fixequipmentmanufacturer->name,
                'created_at' => $fixequipmentmanufacturer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
