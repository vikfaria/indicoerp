<?php

namespace Workdo\Webhook\Extractors;

class AgricultureEquipmentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureequipment)) return [];

        $agricultureequipment = $event->agricultureequipment;

        return [
            'agriculture_equipment' => [
                'id' => $agricultureequipment->id,
                'name' => $agricultureequipment->name ?? null,
                'quantity' => $agricultureequipment->quantity ?? null,
                'price' => $agricultureequipment->price ?? null,
                'created_at' => $agricultureequipment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
