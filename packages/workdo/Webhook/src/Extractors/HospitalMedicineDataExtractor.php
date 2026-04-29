<?php

namespace Workdo\Webhook\Extractors;

class HospitalMedicineDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalMedicine)) return [];

        $hospitalMedicine = $event->hospitalMedicine;

        return [
            'hospital_medicine' => [
                'id' => $hospitalMedicine->id,
                'name' => $hospitalMedicine->name,
                'generic_name' => $hospitalMedicine->generic_name,
                'unit' => $hospitalMedicine->unit,
                'quantity_available' => $hospitalMedicine->quantity_available,
                'price_per_unit' => $hospitalMedicine->price_per_unit,
                'dosage' => $hospitalMedicine->dosage,
                'expiration_date' => $hospitalMedicine->expiration_date,
                'side_effect' => $hospitalMedicine->side_effect,
                'description' => $hospitalMedicine->description,
                'manufacturer' => $hospitalMedicine->manufacturer?->name ?? null,
                'medicine_category' => $hospitalMedicine->medicine_category?->name ?? null,
                'created_at' => $hospitalMedicine->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
