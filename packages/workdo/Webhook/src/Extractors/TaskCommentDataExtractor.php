<?php

namespace Workdo\Webhook\Extractors;

class TaskCommentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->comment)) return [];

        $comment = $event->comment;

        return [
            'task_comment' => [
                'id' => $comment->id,
                'task' => $comment->task?->title ?? null,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
