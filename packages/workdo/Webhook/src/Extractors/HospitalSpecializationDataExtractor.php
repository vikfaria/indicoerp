<?php

namespace Workdo\Webhook\Extractors;

class HospitalSpecializationDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalSpecialization)) return [];

        $hospitalSpecialization = $event->hospitalSpecialization;
        $status = $hospitalSpecialization->is_active == 1 ? 'Active' : 'Inactive';

        return [
            'hospital_specialization' => [
                'id' => $hospitalSpecialization->id,
                'name' => $hospitalSpecialization->name,
                'description' => $hospitalSpecialization->description,
                'status' => $status,
                'created_at' => $hospitalSpecialization->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
