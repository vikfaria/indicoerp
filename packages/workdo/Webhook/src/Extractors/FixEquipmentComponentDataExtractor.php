<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentComponentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentComponent)) return [];

        $fixEquipmentComponent = $event->fixEquipmentComponent;

        return [
            'fixequipment_component' => [
                'id' => $fixEquipmentComponent->id,
                'title' => $fixEquipmentComponent->title,
                'price' => $fixEquipmentComponent->price,
                'quantity' => $fixEquipmentComponent->quantity,
                'category' => $fixEquipmentComponent->category?->title ?? null,
                'asset' => $fixEquipmentComponent->asset?->asset_name ?? null,
                'created_at' => $fixEquipmentComponent->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
