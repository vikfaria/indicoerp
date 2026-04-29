<?php

namespace Workdo\Webhook\Extractors;

class HospitalBedDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalbed)) return [];

        $hospitalbed = $event->hospitalbed;

        return [
            'hospital_bed' => [
                'id' => $hospitalbed->id,
                'charges_per_day' => $hospitalbed->charges_per_day,
                'notes' => $hospitalbed->notes,
                'bed_type' => $hospitalbed->bedType?->name ?? null,
                'ward' => $hospitalbed->ward?->name ?? null,
                'bed_number' => $hospitalbed->bed_number,
                'created_at' => $hospitalbed->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
