<?php

namespace Workdo\Webhook\Extractors;

use App\Models\User;

class FileSharingFileDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->file)) return [];

        $file = $event->file;

        // Handle multiple user_id (JSON array or comma-separated string)
        $userNames = [];
        if ($file->user_id) {
            // Check if it's a JSON array string
            if (is_string($file->user_id) && str_starts_with($file->user_id, '[')) {
                $userIds = json_decode($file->user_id, true) ?: [];
            } else {
                // Handle comma-separated string
                $userIds = is_array($file->user_id) ? $file->user_id : explode(',', $file->user_id);
            }

            foreach ($userIds as $userId) {
                $user = User::find(trim($userId));
                if ($user) {
                    $userNames[] = $user->name;
                }
            }
        }
        $users = !empty($userNames) ? implode(', ', $userNames) : null;

        // Map auto_destroy and password protection status
        $autoDestroy = $file->auto_destroy === 'on' ? 'Enabled' : 'Disabled';
        $passwordProtected = $file->is_pass_enable ? 'Yes' : 'No';

        return [
            'file_sharing_file' => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'filesharing_type' => $file->filesharing_type,
                'email' => $file->email,
                'users' => $users,
                'auto_destroy' => $autoDestroy,
                'password_protected' => $passwordProtected,
                'description' => $file->description,
                'created_at' => $file->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
