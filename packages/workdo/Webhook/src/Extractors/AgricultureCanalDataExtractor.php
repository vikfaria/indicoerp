<?php

namespace Workdo\Webhook\Extractors;

class AgricultureCanalDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturecanal)) return [];

        $agriculturecanal = $event->agriculturecanal;

        return [
            'agriculture_canal' => [
                'id' => $agriculturecanal->id,
                'name' => $agriculturecanal->name ?? null,
                'code' => $agriculturecanal->code ?? null,
                'office' => $agriculturecanal->office ? $agriculturecanal->office->name : null,
                'created_at' => $agriculturecanal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
