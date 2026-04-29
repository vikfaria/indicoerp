<?php

namespace Workdo\Webhook\Extractors;

class SchoolParentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->parent)) return [];

        $parent = $event->parent;

        return [
            'school_parent' => [
                'id' => $parent->id,
                'father_name' => $parent->father_name,
                'father_phone' => $parent->father_phone,
                'father_email' => $parent->father_email,
                'father_occupation' => $parent->father_occupation,
                'mother_name' => $parent->mother_name,
                'mother_phone' => $parent->mother_phone,
                'mother_email' => $parent->mother_email,
                'mother_occupation' => $parent->mother_occupation,
                'guardian_name' => $parent->guardian_name,
                'guardian_phone' => $parent->guardian_phone,
                'guardian_email' => $parent->guardian_email,
                'guardian_relation' => $parent->guardian_relation,
                'created_at' => $parent->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
