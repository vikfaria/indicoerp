<?php

namespace Workdo\Webhook\Extractors;

class RepairCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->repaircategory)) return [];

        $repaircategory = $event->repaircategory;

        return [
            'repair_category' => [
                'id' => $repaircategory->id,
                'name' => $repaircategory->name,
                'created_at' => $repaircategory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
