<?php

namespace Workdo\Webhook\Extractors;

class InsurancePolicyTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->policyType)) return [];

        $policyType = $event->policyType;

        return [
            'insurance_policy_type' => [
                'id' => $policyType->id,
                'name' => $policyType->name,
                'code' => $policyType->code,
                'created_at' => $policyType->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
