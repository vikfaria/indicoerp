<?php

namespace Workdo\Webhook\Extractors;

class CollectionCenterDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->collectionCenter)) return [];

        $collectionCenter = $event->collectionCenter;
        $status = $collectionCenter->status == 0 ? 'Active' : 'Inactive';

        return [
            'collection_center' => [
                'id' => $collectionCenter->id,
                'location_name' => $collectionCenter->location_name,
                'status' => $status,
                'created_at' => $collectionCenter->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
