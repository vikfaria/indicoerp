<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentPreDefinedKitDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentPreDefinedKit)) return [];

        $fixEquipmentPreDefinedKit = $event->fixEquipmentPreDefinedKit;

        return [
            'fixequipment_predefinedkit' => [
                'id' => $fixEquipmentPreDefinedKit->id,
                'title' => $fixEquipmentPreDefinedKit->title,
                'asset' => $fixEquipmentPreDefinedKit->asset?->asset_name ?? null,
                'component' => $fixEquipmentPreDefinedKit->component?->title ?? null,
                'created_at' => $fixEquipmentPreDefinedKit->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
