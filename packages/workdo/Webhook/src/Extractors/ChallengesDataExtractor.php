<?php

namespace Workdo\Webhook\Extractors;

class ChallengesDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->challenge)) return [];

        $challenge = $event->challenge;

        return [
            'challenges' => [
                'id' => $challenge->id,
                'challenge_name' => $challenge->challenge_name,
                'end_date' => $challenge->end_date,
                'explanation' => $challenge->explanation,
                'notes' => $challenge->notes,
                'category' => $challenge->category?->name ?? null,
                'created_at' => $challenge->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
