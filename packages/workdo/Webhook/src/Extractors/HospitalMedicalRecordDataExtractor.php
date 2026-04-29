<?php

namespace Workdo\Webhook\Extractors;

class HospitalMedicalRecordDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalmedicalrecord)) return [];

        $hospitalmedicalrecord = $event->hospitalmedicalrecord;

        return [
            'hospital_medical_record' => [
                'id' => $hospitalmedicalrecord->id,
                'chief_complaint' => $hospitalmedicalrecord->chief_complaint,
                'diagnosis' => $hospitalmedicalrecord->diagnosis,
                'treatment_plan' => $hospitalmedicalrecord->treatment_plan,
                'symptoms' => $hospitalmedicalrecord->symptoms,
                'prescription' => $hospitalmedicalrecord->prescription,
                'follow_up_instructions' => $hospitalmedicalrecord->follow_up_instructions,
                'test_results' => $hospitalmedicalrecord->test_results,
                'vital_signs' => $hospitalmedicalrecord->vital_signs,
                'follow_up_days' => $hospitalmedicalrecord->follow_up_days,
                'patient' => $hospitalmedicalrecord->patient?->name ?? null,
                'appointment_number' => $hospitalmedicalrecord->appointment?->appointment_number ?? null,
                'created_at' => $hospitalmedicalrecord->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
