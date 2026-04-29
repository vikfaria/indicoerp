<?php

namespace Workdo\Webhook\Extractors;

class VideoHubCommentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->videoHubComment)) return [];

        $videoHubComment = $event->videoHubComment;

        return [
            'videohub_comment' => [
                'id' => $videoHubComment->id,
                'video' => $videoHubComment->video?->title ?? null,
                'comment' => $videoHubComment->comment,
                'created_at' => $videoHubComment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
