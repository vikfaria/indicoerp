<?php

namespace Workdo\Webhook\Extractors;

class ToDoDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->todo)) return [];

        $todo = $event->todo;

        return [
            'todo' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'module' => $todo->module,
                'sub_module' => $todo->sub_module,
                'description' => $todo->description,
                'start_date' => $todo->start_date,
                'due_date' => $todo->due_date,
                'created_at' => $todo->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
