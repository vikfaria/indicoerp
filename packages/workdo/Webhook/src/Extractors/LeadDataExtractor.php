<?php

namespace Workdo\Webhook\Extractors;

class LeadDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->lead)) return [];

        $lead = $event->lead;

        return [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'subject' => $lead->subject,
                'user' => $lead->user?->name ?? null,
                'pipeline' => $lead->pipeline?->name ?? null,
                'stage' => $lead->stage?->name ?? null,
                'phone' => $lead->phone,
                'date' => $lead->date,
                'created_at' => $lead->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
