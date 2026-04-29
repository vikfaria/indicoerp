<?php

namespace Workdo\Webhook\Extractors;

class AgricultureActivityDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturecanal)) return [];

        $agriculturecanal = $event->agriculturecanal;

        return [
            'agriculture_canal' => [
                'id' => $agriculturecanal->id,
                'name' => $agriculturecanal->name ?? null,
                'crop' => $agriculturecanal->crop ? $agriculturecanal->crop->name : null,
                'season' => $agriculturecanal->season ? $agriculturecanal->season->name : null,
                'created_at' => $agriculturecanal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
