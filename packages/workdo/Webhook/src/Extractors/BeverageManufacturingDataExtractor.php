<?php

namespace Workdo\Webhook\Extractors;

class BeverageManufacturingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beverageManufacturing)) return [];

        $beverageManufacturing = $event->beverageManufacturing;

        return [
            'beverage_manufacturing' => [
                'id' => $beverageManufacturing->id,
                'collection_center' => $beverageManufacturing->collectionCenter?->location_name ?? null,
                'item' => $beverageManufacturing->item?->name ?? null,
                'quantity' => $beverageManufacturing->quantity,
                'schedule_date' => $beverageManufacturing->schedule_date,
                'total' => $beverageManufacturing->total,
                'created_at' => $beverageManufacturing->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
