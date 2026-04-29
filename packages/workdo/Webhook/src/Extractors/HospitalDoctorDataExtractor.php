<?php

namespace Workdo\Webhook\Extractors;

class HospitalDoctorDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->hospitaldoctor)) return [];

        $hospitaldoctor = $event->hospitaldoctor;

        return [
            'hospital_doctor' => [
                'id' => $hospitaldoctor->id,
                'license_number' => $hospitaldoctor->license_number,
                'years_of_experience' => $hospitaldoctor->years_of_experience,
                'consultation_fee' => $hospitaldoctor->consultation_fee,
                'qualifications' => $hospitaldoctor->qualifications,
                'hospital_specialization' => $hospitaldoctor->hospital_specialization?->name ?? null,
                'doctor_code' => $hospitaldoctor->doctor_code,
                'created_at' => $hospitaldoctor->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
