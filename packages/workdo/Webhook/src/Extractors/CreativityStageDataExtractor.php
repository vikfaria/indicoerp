<?php

namespace Workdo\Webhook\Extractors;

class CreativityStageDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->stage)) return [];

        $stage = $event->stage;

        return [
            'creativity_stage' => [
                'id' => $stage->id,
                'name' => $stage->name,
                'created_at' => $stage->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
