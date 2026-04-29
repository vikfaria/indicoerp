<?php

namespace Workdo\Webhook\Extractors;

class HospitalBedTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalBedType)) return [];

        $hospitalBedType = $event->hospitalBedType;
        $status = $hospitalBedType->is_active == true ? 'Active' : 'Inactive';

        return [
            'hospital_bed_type' => [
                'id' => $hospitalBedType->id,
                'name' => $hospitalBedType->name,
                'base_charge' => $hospitalBedType->base_charge,
                'status' => $status,
                'created_at' => $hospitalBedType->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
