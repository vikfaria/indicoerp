<?php

namespace Workdo\Webhook\Extractors;

class MilestoneDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->milestone)) return [];

        $milestone = $event->milestone;

        return [
            'milestone' => [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'cost' => $milestone->cost,
                'start_date' => $milestone->start_date?->format('d-m-Y') ?? null,
                'end_date' => $milestone->end_date?->format('d-m-Y') ?? null,
                'summary' => $milestone->summary,
                'project' => $milestone->project?->name ?? null,
                'created_at' => $milestone->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
