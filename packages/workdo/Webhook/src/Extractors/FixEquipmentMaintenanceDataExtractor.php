<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentMaintenanceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentMaintenance)) return [];

        $fixEquipmentMaintenance = $event->fixEquipmentMaintenance;

        return [
            'fixequipment_maintenance' => [
                'id' => $fixEquipmentMaintenance->id,
                'maintenance_type' => $fixEquipmentMaintenance->maintenance_type,
                'price' => $fixEquipmentMaintenance->price,
                'maintenance_date' => $fixEquipmentMaintenance->maintenance_date,
                'description' => $fixEquipmentMaintenance->description,
                'asset' => $fixEquipmentMaintenance->asset?->asset_name ?? null,
                'created_at' => $fixEquipmentMaintenance->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
