<?php

namespace Workdo\Webhook\Extractors;

use Workdo\Lead\Models\Source;
use Workdo\ProductService\Models\ProductServiceItem;

class LeadMovedDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->lead)) return [];

        $lead = $event->lead;

        // Get source and product names by ID
        $sourceName = null;
        $productName = null;

        if (Module_is_active('Lead')) {
            if ($lead->getAttribute('sources')) {
                $source = Source::find((int)$lead->getAttribute('sources'));
                $sourceName = $source?->name;
            }
        }

        if (Module_is_active('ProductService')) {
            if ($lead->getAttribute('products')) {
                $product = ProductServiceItem::find((int)$lead->getAttribute('products'));
                $productName = $product?->name;
            }
        }

        return [
            'lead_moved' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'subject' => $lead->subject,
                'user' => $lead->user?->name ?? null,
                'pipeline' => $lead->pipeline?->name ?? null,
                'stage' => $lead->stage?->name ?? null,
                'sources' => $sourceName,
                'products' => $productName,
                'phone' => $lead->phone,
                'date' => $lead->date?->format('d-m-Y') ?? null,
                'created_at' => $lead->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
