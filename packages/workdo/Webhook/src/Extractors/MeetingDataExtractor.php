<?php

namespace Workdo\Webhook\Extractors;

use App\Models\User;

class MeetingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meeting)) return [];

        $meeting = $event->meeting;

        // Handle user_id (JSON array format)
        $userNames = [];
        if ($meeting->user_id) {
            if (is_string($meeting->user_id) && str_starts_with($meeting->user_id, '[')) {
                $userIds = json_decode($meeting->user_id, true) ?: [];
            } else {
                $userIds = is_array($meeting->user_id) ? $meeting->user_id : explode(',', $meeting->user_id);
            }

            foreach ($userIds as $userId) {
                $user = User::find(trim($userId));
                if ($user) {
                    $userNames[] = $user->name;
                }
            }
        }
        $users = !empty($userNames) ? implode(', ', $userNames) : null;

        return [
            'meeting' => [
                'id' => $meeting->id,
                'caller' => $meeting->caller ?? null,
                'users' => $users,
                'meeting_type' => $meeting->meetingType ? $meeting->meetingType->name : null,
                'location' => $meeting->location ?? null,
                'subject' => $meeting->subject ?? null,
                'description' => $meeting->description ?? null,
                'created_at' => $meeting->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
