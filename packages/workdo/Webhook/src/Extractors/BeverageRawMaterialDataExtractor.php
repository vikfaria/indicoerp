<?php

namespace Workdo\Webhook\Extractors;

class BeverageRawMaterialDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beverageRawMaterial)) return [];

        $beverageRawMaterial = $event->beverageRawMaterial;
        $status = $beverageRawMaterial->status == 0 ? 'Active' : 'Inactive';

        return [
            'beverage_raw_material' => [
                'id' => $beverageRawMaterial->id,
                'collection_center' => $beverageRawMaterial->collectionCenter?->location_name ?? null,
                'item' => $beverageRawMaterial->item?->name ?? null,
                'price' => $beverageRawMaterial->price,
                'status' => $status,
                'created_at' => $beverageRawMaterial->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
