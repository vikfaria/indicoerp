<?php

namespace Workdo\Webhook\Extractors;

use App\Models\User;

class InternalknowledgeBookDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->internalknowledgeBook)) return [];

        $internalknowledgeBook = $event->internalknowledgeBook;

        // Handle multiple users (JSON array string)
        $userNames = [];
        if ($internalknowledgeBook->users) {
            $userIds = is_array($internalknowledgeBook->users) ? $internalknowledgeBook->users : json_decode($internalknowledgeBook->users, true);
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

        return [
            'internalknowledge_book' => [
                'id' => $internalknowledgeBook->id,
                'title' => $internalknowledgeBook->title,
                'user' => $users,
                'slug' => $internalknowledgeBook->slug,
                'created_at' => $internalknowledgeBook->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
