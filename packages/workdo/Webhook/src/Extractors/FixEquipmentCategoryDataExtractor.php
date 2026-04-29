<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixequipmentcategory)) return [];

        $fixequipmentcategory = $event->fixequipmentcategory;

        return [
            'fixequipment_category' => [
                'id' => $fixequipmentcategory->id,
                'title' => $fixequipmentcategory->title,
                'created_at' => $fixequipmentcategory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
