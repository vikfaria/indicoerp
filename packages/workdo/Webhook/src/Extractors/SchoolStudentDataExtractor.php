<?php

namespace Workdo\Webhook\Extractors;

class SchoolStudentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->student)) return [];

        $student = $event->student;

        return [
            'school_student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'admission_number' => $student->admission?->admission_number ?? null,
                'grade' => $student->grade?->name ?? null,
                'class' => $student->class?->name ?? null,
                'status' => $student->status,
                'created_at' => $student->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
