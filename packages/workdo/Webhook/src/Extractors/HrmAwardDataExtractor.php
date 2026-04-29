<?php

namespace Workdo\Webhook\Extractors;

class HrmAwardDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->award)) return [];

        $award = $event->award;

        return [
            'award' => [
                'id' => $award->id,
                'employee' => $award->employee ? $award->employee->name : null,
                'award_type' => $award->awardType ? $award->awardType->name : null,
                'award_date' => $award->award_date ?? null,
                'description' => $award->description ?? null,
                'created_at' => $award->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
