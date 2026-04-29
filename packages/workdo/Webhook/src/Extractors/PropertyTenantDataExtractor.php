<?php

namespace Workdo\Webhook\Extractors;

class PropertyTenantDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->propertytenant)) return [];

        $propertytenant = $event->propertytenant;

        return [
            'property_tenant' => [
                'id' => $propertytenant->id,
                'user' => $propertytenant->user ? $propertytenant->user->name : null,
                'total_family_member' => $propertytenant->total_family_member ?? null,
                'address' => $propertytenant->address ?? null,
                'city' => $propertytenant->city ?? null,
                'state' => $propertytenant->state ?? null,
                'country' => $propertytenant->country ?? null,
                'zip_code' => $propertytenant->zip_code ?? null,
                'lease_start_date' => $propertytenant->lease_start_date ?? null,
                'lease_expiry_date' => $propertytenant->lease_expiry_date ?? null,
                'tenant_type' => $propertytenant->tenant_type ?? null,
                'property' => $propertytenant->property ? $propertytenant->property->name : null,
                'property_unit' => $propertytenant->property_unit ? $propertytenant->property_unit->unit_name : null,
                'created_at' => $propertytenant->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
