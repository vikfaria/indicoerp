<?php

namespace Workdo\Webhook\Extractors;

class FreightCustomerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightCustomer)) return [];

        $freightCustomer = $event->freightCustomer;

        return [
            'freight_customer' => [
                'id' => $freightCustomer->id,
                'name' => $freightCustomer->name,
                'email' => $freightCustomer->email,
                'mobile_no' => $freightCustomer->mobile_no,
                'address' => $freightCustomer->address,
                'city' => $freightCustomer->city,
                'state' => $freightCustomer->state,
                'country' => $freightCustomer->country,
                'zip' => $freightCustomer->zip,
                'created_at' => $freightCustomer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
