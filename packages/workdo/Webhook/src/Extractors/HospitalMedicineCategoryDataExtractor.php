<?php

namespace Workdo\Webhook\Extractors;

class HospitalMedicineCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalMedicineCategory)) return [];

        $hospitalMedicineCategory = $event->hospitalMedicineCategory;
        $status = $hospitalMedicineCategory->is_active == true ? 'Active' : 'Inactive';

        return [
            'hospital_medicine_category' => [
                'id' => $hospitalMedicineCategory->id,
                'name' => $hospitalMedicineCategory->name,
                'description' => $hospitalMedicineCategory->description,
                'status' => $status,
                'created_at' => $hospitalMedicineCategory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
