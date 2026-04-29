<?php

namespace Workdo\Webhook\Extractors;

class VisitPurposeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->visitpurpose)) return [];

        $visitpurpose = $event->visitpurpose;

        return [
            'visit_purpose' => [
                'id' => $visitpurpose->id,
                'name' => $visitpurpose->name,
                'created_at' => $visitpurpose->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
