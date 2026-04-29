<?php

namespace Workdo\Webhook\Extractors;

class UserDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->user)) return [];

        $user = $event->user;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'created_at' => $user->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}