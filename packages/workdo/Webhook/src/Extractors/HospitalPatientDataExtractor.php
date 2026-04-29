<?php

namespace Workdo\Webhook\Extractors;

class HospitalPatientDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitalpatient)) return [];

        $hospitalpatient = $event->hospitalpatient;

        $hospitalDoctor = $hospitalpatient->doctor ?? null;
        $hospitalDoctorItem = $hospitalDoctor?->user ?? null;

        return [
            'hospital_patient' => [
                'id' => $hospitalpatient->id,
                'name' => $hospitalpatient->name,
                'email' => $hospitalpatient->email,
                'date_of_birth' => $hospitalpatient->date_of_birth,
                'mobile_no' => $hospitalpatient->mobile_no,
                'emergency_contact_name' => $hospitalpatient->emergency_contact_name,
                'emergency_contact_mobile_no' => $hospitalpatient->emergency_contact_mobile_no,
                'medical_history' => $hospitalpatient->medical_history,
                'allergies' => $hospitalpatient->allergies,
                'address' => $hospitalpatient->address,
                'doctor' => $hospitalDoctorItem->name ?? null,
                'patient_code' => $hospitalpatient->patient_code,
                'created_at' => $hospitalpatient->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
