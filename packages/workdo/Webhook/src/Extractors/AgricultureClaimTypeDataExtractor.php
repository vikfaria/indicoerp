<?php

namespace Workdo\Webhook\Extractors;

class AgricultureClaimTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureclaimtype)) return [];

        $agricultureclaimtype = $event->agricultureclaimtype;

        return [
            'agriculture_claim_type' => [
                'id' => $agricultureclaimtype->id,
                'name' => $agricultureclaimtype->name ?? null,
                'created_at' => $agricultureclaimtype->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
