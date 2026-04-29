<?php

namespace Workdo\Webhook\Extractors;

class PropertyUnitDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->propertyunit)) return [];

        $propertyunit = $event->propertyunit;

        return [
            'property_unit' => [
                'id' => $propertyunit->id,
                'unit_name' => $propertyunit->unit_name ?? null,
                'bedroom' => $propertyunit->bedroom ?? null,
                'baths' => $propertyunit->baths ?? null,
                'kitchen' => $propertyunit->kitchen ?? null,
                'unit_type' => $propertyunit->unit_type ?? null,
                'rent_type' => $propertyunit->rent_type ?? null,
                'rent' => $propertyunit->rent ?? null,
                'utilities_included' => $propertyunit->utilities_included ?? null,
                'description' => $propertyunit->description ?? null,
                'property' => $propertyunit->property ? $propertyunit->property->name : null,
                'created_at' => $propertyunit->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
