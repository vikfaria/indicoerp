<?php

namespace Workdo\Webhook\Extractors;

class PortfolioDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->portfolio)) return [];

        $portfolio = $event->portfolio;

        return [
            'portfolio' => [
                'id' => $portfolio->id,
                'email' => $portfolio->email,
                'role' => $portfolio->role,
                'experience_years' => $portfolio->experience_years,
                'education' => $portfolio->education,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'category' => $portfolio->portfolio_category?->name ?? null,
                'live_url' => $portfolio->live_url,
                'repository_url' => $portfolio->repository_url,
                'client' => $portfolio->client,
                'duration' => $portfolio->duration,
                'team_size' => $portfolio->team_size,
                'start_date' => $portfolio->start_date,
                'end_date' => $portfolio->end_date,
                'budget' => $portfolio->budget,
                'industry' => $portfolio->industry,
                'contact_heading' => $portfolio->contact_heading,
                'contact_message' => $portfolio->contact_message,
                'slug' => $portfolio->slug,
                'created_at' => $portfolio->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
