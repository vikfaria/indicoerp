<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentLicenseDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentLicense)) return [];

        $fixEquipmentLicense = $event->fixEquipmentLicense;

        return [
            'fixequipment_License' => [
                'id' => $fixEquipmentLicense->id,
                'title' => $fixEquipmentLicense->title,
                'license_number' => $fixEquipmentLicense->license_number,
                'purchase_date' => $fixEquipmentLicense->purchase_date,
                'expire_date' => $fixEquipmentLicense->expire_date,
                'purchase_price' => $fixEquipmentLicense->purchase_price,
                'category' => $fixEquipmentLicense->category?->name ?? null,
                'asset' => $fixEquipmentLicense->asset?->asset_name ?? null,
                'created_at' => $fixEquipmentLicense->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
