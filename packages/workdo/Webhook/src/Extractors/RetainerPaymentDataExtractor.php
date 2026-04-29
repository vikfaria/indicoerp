<?php

namespace Workdo\Webhook\Extractors;

class RetainerPaymentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->retainerPayment)) return [];

        $retainerPayment = $event->retainerPayment;

        return [
            'retainer_payment' => [
                'id' => $retainerPayment->id,
                'payment_date' => $retainerPayment->payment_date,
                'customer' => $retainerPayment->customer?->name ?? null,
                'bank_account' => $retainerPayment->bankAccount?->account_name ?? null,
                'reference_number' => $retainerPayment->reference_number,
                'payment_amount' => $retainerPayment->payment_amount,
                'notes' => $retainerPayment->notes,
                'payment_number' => $retainerPayment->payment_number,
                'created_at' => $retainerPayment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
