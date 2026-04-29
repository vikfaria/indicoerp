<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentDepreciationDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixequipmentdepreciation)) return [];

        $fixequipmentdepreciation = $event->fixequipmentdepreciation;

        return [
            'fixequipment_depreciation' => [
                'id' => $fixequipmentdepreciation->id,
                'title' => $fixequipmentdepreciation->title,
                'rate' => $fixequipmentdepreciation->rate,
                'created_at' => $fixequipmentdepreciation->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
