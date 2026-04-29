<?php

namespace Workdo\Webhook\Extractors;

class InsuranceClaimDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->insuranceClaim)) return [];

        $insuranceClaim = $event->insuranceClaim;
        $insuranceClient = $insuranceClaim->insurance ?? null;
        $insuranceClientItem = $insuranceClient?->client ?? null;

        return [
            'insurance_claim' => [
                'id' => $insuranceClaim->id,
                'insurance_client' => $insuranceClientItem->name ?? null,
                'claim_reason' => $insuranceClaim->claim_reason,
                'created_at' => $insuranceClaim->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
