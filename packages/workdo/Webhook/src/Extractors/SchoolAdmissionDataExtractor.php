<?php

namespace Workdo\Webhook\Extractors;

class SchoolAdmissionDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->admission)) return [];

        $admission = $event->admission;

        return [
            'school_admission' => [
                'id' => $admission->id,
                'admission_number' => $admission->admission_number,
                'application_date' => $admission->application_date,
                'class_applying_for' => $admission->class_applying_for,
                'academic_year' => $admission->academic_year,
                'admission_status' => $admission->admission_status,
                'remarks' => $admission->remarks,
                'branch' => $admission->branch?->name ?? null,
                'created_at' => $admission->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
