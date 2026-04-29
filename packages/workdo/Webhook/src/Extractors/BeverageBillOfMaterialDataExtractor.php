<?php

namespace Workdo\Webhook\Extractors;

class BeverageBillOfMaterialDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beverageBillOfMaterial)) return [];

        $beverageBillOfMaterial = $event->beverageBillOfMaterial;

        return [
            'beverage_bill_of_material' => [
                'id' => $beverageBillOfMaterial->id,
                'item' => $beverageBillOfMaterial->item?->name ?? null,
                'collection_center' => $beverageBillOfMaterial->collectionCenter?->location_name ?? null,
                'quantity' => $beverageBillOfMaterial->quantity,
                'created_at' => $beverageBillOfMaterial->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
