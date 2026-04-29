<?php

namespace Workdo\Webhook\Extractors;

class TaskStageUpdateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->taskStage)) return [];

        $taskStage = $event->taskStage;

        return [
            'task_stage' => [
                'id' => $taskStage->id,
                'name' => $taskStage->name,
                'color' => $taskStage->color,
                'order' => $taskStage->order,
                'created_at' => $taskStage->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
