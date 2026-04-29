<?php

namespace Workdo\Webhook\Extractors;

use Workdo\ProductService\Models\ProductServiceItem;

class PreventiveMaintenanceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->preventiveMaintenance)) return [];

        $preventiveMaintenance = $event->preventiveMaintenance;

        // Handle multiple parts_id (comma-separated string)
        $partsNames = [];

        if (Module_is_active('ProductService')) {
            if ($preventiveMaintenance->parts_id) {
                $partIds = explode(',', $preventiveMaintenance->parts_id);
                foreach ($partIds as $partId) {
                    $part = ProductServiceItem::find(trim($partId));
                    if ($part) {
                        $partsNames[] = $part->name;
                    }
                }
            }
        }
        $parts = !empty($partsNames) ? implode(', ', $partsNames) : null;

        return [
            'preventive_maintenance' => [
                'id' => $preventiveMaintenance->id,
                'name' => $preventiveMaintenance->name,
                'tags' => $preventiveMaintenance->tags,
                'description' => $preventiveMaintenance->description,
                'location' => $preventiveMaintenance->location?->name ?? null,
                'parts' => $parts,
                'created_at' => $preventiveMaintenance->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
