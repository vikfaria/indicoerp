<?php

namespace Workdo\Webhook\Extractors;

class PortfolioCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->portfolioCategory)) return [];

        $portfolioCategory = $event->portfolioCategory;
        $status = $portfolioCategory->is_active == 'true' ? 'Active' : 'Inactive';

        return [
            'portfolio_category' => [
                'id' => $portfolioCategory->id,
                'name' => $portfolioCategory->name,
                'description' => $portfolioCategory->description,
                'status' => $status,
                'created_at' => $portfolioCategory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
