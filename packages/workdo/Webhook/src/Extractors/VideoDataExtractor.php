<?php

namespace Workdo\Webhook\Extractors;

class VideoDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->videoHubVideo)) return [];

        $videoHubVideo = $event->videoHubVideo;

        return [
            'videohub_video' => [
                'id' => $videoHubVideo->id,
                'title' => $videoHubVideo->title,
                'module' => $videoHubVideo->module,
                'video' => $videoHubVideo->video,
                'description' => $videoHubVideo->description,
                'created_at' => $videoHubVideo->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
