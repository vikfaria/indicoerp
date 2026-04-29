<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\EventType;
use Illuminate\Database\Seeder;



class DemoEventTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (EventType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $eventTypes = [
            'Team Meeting',
            'Training Session',
            'Performance Review',
            'Company Event',
            'Workshop',
            'Conference',
            'Seminar',
            'Webinar',
            'Town Hall',
            'Board Meeting',
            'Client Meeting',
            'Project Kickoff',
            'Sprint Planning',
            'Daily Standup',
            'Retrospective',
            'Product Demo',
            'Sales Presentation',
            'Interview',
            'Onboarding',
            'Team Building',
            'Holiday Party',
            'Awards Ceremony',
            'Networking Event',
            'Product Launch',
            'Strategy Session'
        ];

        foreach ($eventTypes as $eventType) {
            EventType::updateOrCreate(
                [
                    'event_type' => $eventType,
                    'created_by' => $userId
                ],
                [
                    'creator_id' => $userId,
                ]
            );
        }
    }
}