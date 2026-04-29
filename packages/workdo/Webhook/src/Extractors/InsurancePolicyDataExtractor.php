<?php

namespace Workdo\Webhook\Extractors;

class InsurancePolicyDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->policy)) return [];

        $policy = $event->policy;

        return [
            'insurance_policy' => [
                'id' => $policy->id,
                'name' => $policy->name,
                'policy_type' => $policy->policy_type?->name ?? null,
                'duration' => $policy->duration,
                'minimum_duration' => $policy->minimum_duration,
                'maximum_duration' => $policy->maximum_duration,
                'amount' => $policy->amount,
                'agent_commission' => $policy->agent_commission,
                'commission_amount' => $policy->commission_amount,
                'claim_rate' => $policy->claim_rate,
                'description' => $policy->description,
                'created_at' => $policy->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
