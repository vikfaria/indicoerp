<?php

namespace Workdo\Webhook\Extractors;

class BugDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->bug)) return [];

        $bug = $event->bug;

        return [
            'bug' => [
                'id' => $bug->id,
                'project' => $bug->project?->name ?? null,
                'title' => $bug->title,
                'priority' => $bug->priority,
                'stage' => $bug->bugStage?->name ?? null,
                'description' => $bug->description,
                'created_at' => $bug->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
