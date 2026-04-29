<?php

namespace Workdo\Webhook\Extractors;

class TrainerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->trainer)) return [];

        $trainer = $event->trainer;

        return [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name ?? null,
                'contact' => $trainer->contact ?? null,
                'email' => $trainer->email ?? null,
                'experience' => $trainer->experience ?? null,
                'branch' => $trainer->branch ? $trainer->branch->branch_name : null,
                'department' => $trainer->department ? $trainer->department->department_name : null,
                'expertise' => $trainer->expertise ?? null,
                'qualification' => $trainer->qualification ?? null,
                'created_at' => $trainer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
