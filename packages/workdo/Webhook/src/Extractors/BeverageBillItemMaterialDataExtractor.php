<?php

namespace Workdo\Webhook\Extractors;

class BeverageBillItemMaterialDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beverageBillMaterialItem)) return [];

        $beverageBillMaterialItem = $event->beverageBillMaterialItem;

        // Safe access to nested relationships
        $billOfMaterial = $beverageBillMaterialItem->billOfMaterial ?? null;
        $item = $billOfMaterial?->item ?? null;
        $rawMaterial = $beverageBillMaterialItem->rawMaterial ?? null;
        $rawMaterialItem = $rawMaterial?->item ?? null;
        $collectionCenter = $rawMaterial?->collectionCenter ?? null;

        return [
            'beverage_bill_material_item' => [
                'id' => $beverageBillMaterialItem->id,
                'item' => $item?->name ?? null,
                'raw_material' => $rawMaterialItem?->name ?? null,
                'collection_center' => $collectionCenter?->location_name ?? null,
                'quantity' => $beverageBillMaterialItem->quantity,
                'price' => $beverageBillMaterialItem->price,
                'sub_total' => $beverageBillMaterialItem->sub_total,
                'created_at' => $beverageBillMaterialItem->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
