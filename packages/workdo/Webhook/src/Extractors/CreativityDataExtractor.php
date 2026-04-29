<?php

namespace Workdo\Webhook\Extractors;

class CreativityDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->creativity)) return [];

        $creativity = $event->creativity;

        return [
            'creativity' => [
                'id' => $creativity->id,
                'creativity_name' => $creativity->creativity_name,
                'status' => $creativity->Status?->name ?? null,
                'stage' => $creativity->Stage?->name ?? null,
                'challenge' => $creativity->Challenge?->challenge_name ?? null,
                'created_at' => $creativity->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
