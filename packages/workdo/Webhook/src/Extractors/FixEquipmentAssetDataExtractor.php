<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentAssetDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentAsset)) return [];

        $fixEquipmentAsset = $event->fixEquipmentAsset;

        return [
            'fixequipment_asset' => [
                'id' => $fixEquipmentAsset->id,
                'asset_name' => $fixEquipmentAsset->asset_name,
                'model' => $fixEquipmentAsset->model,
                'serial_number' => $fixEquipmentAsset->serial_number,
                'purchase_date' => $fixEquipmentAsset->purchase_date,
                'purchase_price' => $fixEquipmentAsset->purchase_price,
                'description' => $fixEquipmentAsset->description,
                'location' => $fixEquipmentAsset->location?->name ?? null,
                'supplier' => $fixEquipmentAsset->supplier?->name ?? null,
                'manufacturer' => $fixEquipmentAsset->manufacturer?->title ?? null,
                'category' => $fixEquipmentAsset->category?->title ?? null,
                'status' => $fixEquipmentAsset->status?->title ?? null,
                'depreciation' => $fixEquipmentAsset->depreciation?->title ?? null,
                'created_at' => $fixEquipmentAsset->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
