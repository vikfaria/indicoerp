<?php

namespace Workdo\Webhook\Extractors;

class TrainingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->training)) return [];

        $training = $event->training;

        return [
            'training' => [
                'id' => $training->id,
                'title' => $training->title ?? null,
                'description' => $training->description ?? null,
                'training_type' => $training->trainingType ? $training->trainingType->name : null,
                'trainer' => $training->trainer ? $training->trainer->name : null,
                'branch' => $training->branch ? $training->branch->branch_name : null,
                'department' => $training->department ? $training->department->department_name : null,
                'start_date' => $training->start_date ?? null,
                'end_date' => $training->end_date ?? null,
                'start_time' => $training->start_time ?? null,
                'end_time' => $training->end_time ?? null,
                'location' => $training->location ?? null,
                'max_participants' => $training->max_participants ?? null,
                'cost' => $training->cost ?? null,
                'status' => $training->status ?? null,
                'created_at' => $training->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
