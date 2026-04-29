<?php

namespace Workdo\Webhook\Extractors;

class VendorDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->vendor)) return [];

        $vendor = $event->vendor;

        return [
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->contact_person_name,
                'email' => $vendor->contact_person_email,
                'mobile' => $vendor->contact_person_mobile,
                'company_name' => $vendor->company_name,
                'tax_number' => $vendor->tax_number,
                'payment_terms' => $vendor->payment_terms,
                'billing_address' => $vendor->billing_address,
                'shipping_address' => $vendor->shipping_address,
                'notes' => $vendor->notes,
                'vendor_code' => $vendor->vendor_code,
                'created_at' => $vendor->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}