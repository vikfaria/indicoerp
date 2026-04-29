<?php

namespace Workdo\Webhook\Extractors;

class CourierPaymentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->payment)) return [];

        $payment = $event->payment;
        $payment_type = $payment->payment_type == '1' ? 'Cash' : '-';
        $payment_status = $payment->payment_status == '1' ? 'Success' : 'Pending';

        return [
            'courier_payment' => [
                'id' => $payment->id,
                'payment_date' => $payment->payment_date,
                'price' => $payment->price,
                'payment_type' => $payment_type,
                'payment_status' => $payment_status,
                'description' => $payment->description,
                'receipt' => $payment->receipt,
                'courier' => $payment->courier?->sender_name ?? null,
                'tracking_status' => $payment->tracking_status?->name ?? null,
                'created_at' => $payment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
