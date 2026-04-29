<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentAccessoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentAccessory)) return [];

        $fixEquipmentAccessory = $event->fixEquipmentAccessory;

        return [
            'fixequipment_accessory' => [
                'id' => $fixEquipmentAccessory->id,
                'title' => $fixEquipmentAccessory->title,
                'price' => $fixEquipmentAccessory->price,
                'quantity' => $fixEquipmentAccessory->quantity,
                'category' => $fixEquipmentAccessory->category?->title ?? null,
                'asset' => $fixEquipmentAccessory->asset?->asset_name ?? null,
                'manufacturer' => $fixEquipmentAccessory->manufacturer?->title ?? null,
                'supplier' => $fixEquipmentAccessory->supplier?->name ?? null,
                'created_at' => $fixEquipmentAccessory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
