<?php

namespace Workdo\Webhook\Extractors;

class ComponentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->component)) return [];

        $component = $event->component;

        return [
            'component' => [
                'id' => $component->id,
                'name' => $component->name,
                'sku' => $component->sku,
                'component_tag' => $component->component_tag,
                'category' => $component->category,
                'assigned_date' => $component->assigned_date?->format('d-m-Y') ?? null,
                'description' => $component->description,
                'link' => $component->link,
                'model' => $component->model,
                'brand' => $component->brand,
                'operating_hours' => $component->operating_hours,
                'original_cost' => $component->original_cost,
                'purchase_cost' => $component->purchase_cost,
                'serial_number' => $component->serial_number,
                'service_contact' => $component->service_contact,
                'warranty_exp_date' => $component->warranty_exp_date?->format('d-m-Y') ?? null,
                'location' => $component->location?->name ?? null,
                'created_at' => $component->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
