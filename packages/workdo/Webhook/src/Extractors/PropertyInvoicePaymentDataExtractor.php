<?php

namespace Workdo\Webhook\Extractors;

class PropertyInvoicePaymentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->invoice_payment)) return [];

        $invoice_payment = $event->invoice_payment;

        return [
            'invoice_payment' => [
                'id' => $invoice_payment->id,
                'user' => $invoice_payment->user ? $invoice_payment->user->name : null,
                'date' => $invoice_payment->date ?? null,
                'amount' => $invoice_payment->amount ?? null,
                'payment_type' => $invoice_payment->payment_type ?? null,
                'created_at' => $invoice_payment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
