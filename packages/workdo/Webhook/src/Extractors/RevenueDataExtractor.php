<?php

namespace Workdo\Webhook\Extractors;

class RevenueDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->revenue)) return [];

        $revenue = $event->revenue;

        return [
            'revenue' => [
                'id' => $revenue->id,
                'revenue_date' => $revenue->revenue_date,
                'category' => $revenue->category?->category_name ?? null,
                'bank_account' => $revenue->bankAccount?->account_name ?? null,
                'amount' => $revenue->amount,
                'description' => $revenue->description,
                'reference_number' => $revenue->reference_number,
                'status' => $revenue->status,
                'revenue_number' => $revenue->revenue_number,
                'created_at' => $revenue->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
