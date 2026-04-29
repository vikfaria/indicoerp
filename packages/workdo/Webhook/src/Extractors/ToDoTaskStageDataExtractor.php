<?php

namespace Workdo\Webhook\Extractors;

class ToDoTaskStageDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->taskStage)) return [];

        $taskStage = $event->taskStage;

        return [
            'todo_task_stage' => [
                'id' => $taskStage->id,
                'name' => $taskStage->name,
                'color' => $taskStage->color,
                'created_at' => $taskStage->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
