<?php

namespace Workdo\Webhook\Extractors;

class HospitalWardDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalWard)) return [];

        $hospitalWard = $event->hospitalWard;
        $status = $hospitalWard->is_active == true ? 'Active' : 'Inactive';

        return [
            'hospital_ward' => [
                'id' => $hospitalWard->id,
                'name' => $hospitalWard->name,
                'floor' => $hospitalWard->floor,
                'capacity' => $hospitalWard->capacity,
                'status' => $status,
                'created_at' => $hospitalWard->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
