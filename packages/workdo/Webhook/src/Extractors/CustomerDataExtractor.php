<?php

namespace Workdo\Webhook\Extractors;

class CustomerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->customer)) return [];

        $customer = $event->customer;

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->contact_person_name,
                'email' => $customer->contact_person_email,
                'mobile' => $customer->contact_person_mobile,
                'company_name' => $customer->company_name,
                'tax_number' => $customer->tax_number,
                'payment_terms' => $customer->payment_terms,
                'billing_address' => $customer->billing_address,
                'shipping_address' => $customer->shipping_address,
                'notes' => $customer->notes,
                'customer_code' => $customer->customer_code,
                'created_at' => $customer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}