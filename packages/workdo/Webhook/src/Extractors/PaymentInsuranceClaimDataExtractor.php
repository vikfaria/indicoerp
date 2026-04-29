<?php

namespace Workdo\Webhook\Extractors;

class PaymentInsuranceClaimDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->insuranceClaim)) return [];

        $insuranceClaim = $event->insuranceClaim;

        return [
            'payment_insurance_claim' => [
                'id' => $insuranceClaim->id,
                'claim_reason' => $insuranceClaim->claim_reason,
                'action_date' => $insuranceClaim->action_date,
                'amount' => $insuranceClaim->amount,
                'created_at' => $insuranceClaim->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
