<?php

namespace Workdo\Webhook\Extractors;

class InsuranceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->insurance)) return [];

        $insurance = $event->insurance;

        return [
            'insurance' => [
                'id' => $insurance->id,
                'client' => $insurance->client?->name ?? null,
                'policy_type' => $insurance->policyType?->name ?? null,
                'policy' => $insurance->policy?->name ?? null,
                'agent' => $insurance->agent?->name ?? null,
                'duration' => $insurance->duration,
                'expiry_date' => $insurance->expiry_date,
                'created_at' => $insurance->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
