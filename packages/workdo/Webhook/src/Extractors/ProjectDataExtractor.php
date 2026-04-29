<?php

namespace Workdo\Webhook\Extractors;

class ProjectDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->project)) return [];

        $project = $event->project;

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'budget' => $project->budget,
                'start_date' => $project->start_date?->format('d-m-Y') ?? null,
                'end_date' => $project->end_date?->format('d-m-Y') ?? null,
                'status' => $project->status,
                'type' => $project->type,
                'created_at' => $project->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}