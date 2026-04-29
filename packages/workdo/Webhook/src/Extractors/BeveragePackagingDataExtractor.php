<?php

namespace Workdo\Webhook\Extractors;

class BeveragePackagingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beveragePackaging)) return [];

        $beveragePackaging = $event->beveragePackaging;

        // Safe access to nested relationships
        $manufacturing = $beveragePackaging->manufacturing ?? null;
        $manufacturingItem = $manufacturing?->item ?? null;
        $collectionCenter = $beveragePackaging->collectionCenter ?? null;

        return [
            'beverage_packaging' => [
                'id' => $beveragePackaging->id,
                'manufacturing_item' => $manufacturingItem?->name ?? null,
                'collection_center' => $collectionCenter?->location_name ?? null,
                'created_at' => $beveragePackaging->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
