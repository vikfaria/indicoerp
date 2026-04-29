<?php

namespace Workdo\Webhook\Extractors;

class TaskDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->task)) return [];

        $task = $event->task;

        return [
            'task' => [
                'id' => $task->id,
                'project' => $task->project?->name ?? null,
                'milestone' => $task->milestone?->title ?? null,
                'title' => $task->title,
                'priority' => $task->priority,
                'duration' => (string) $task->duration,
                'description' => $task->description,
                'stage' => $task->taskStage?->name ?? null,
                'created_at' => $task->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
