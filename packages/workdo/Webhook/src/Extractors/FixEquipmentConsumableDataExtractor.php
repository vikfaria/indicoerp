<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentConsumableDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentConsumable)) return [];

        $fixEquipmentConsumable = $event->fixEquipmentConsumable;

        return [
            'fixequipment_consumable' => [
                'id' => $fixEquipmentConsumable->id,
                'title' => $fixEquipmentConsumable->title,
                'date' => $fixEquipmentConsumable->date,
                'price' => $fixEquipmentConsumable->price,
                'quantity' => $fixEquipmentConsumable->quantity,
                'category' => $fixEquipmentConsumable->category?->title ?? null,
                'asset' => $fixEquipmentConsumable->asset?->asset_name ?? null,
                'manufacturer' => $fixEquipmentConsumable->manufacturer?->title ?? null,
                'created_at' => $fixEquipmentConsumable->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
