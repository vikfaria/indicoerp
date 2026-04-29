<?php

namespace Workdo\Webhook\Extractors;

use App\Models\User;

class CleaningTeamDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->cleaningTeam)) return [];

        $cleaningTeam = $event->cleaningTeam;

        // Handle multiple user_id (JSON array string)
        $userNames = [];
        if ($cleaningTeam->user_id) {
            $userIds = is_array($cleaningTeam->user_id) ? $cleaningTeam->user_id : json_decode($cleaningTeam->user_id, true);
            if (is_array($userIds)) {
                foreach ($userIds as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $userNames[] = $user->name;
                    }
                }
            }
        }
        $users = !empty($userNames) ? implode(', ', $userNames) : null;
        $status = $cleaningTeam->status == 'true' ? 'Active' : 'Inactive';

        return [
            'cleaning_team' => [
                'id' => $cleaningTeam->id,
                'name' => $cleaningTeam->name,
                'users' => $users,
                'status' => $status,
                'created_at' => $cleaningTeam->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
