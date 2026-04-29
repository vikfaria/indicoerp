<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    protected $table = 'support_ticket_quick_links';

    protected $fillable = [
        'title',
        'icon',
        'link',
        'order',
        'creator_id',
        'created_by'
    ];

    public static function defaultdata($company_id)
    {
        $defaultLinks = [
            ['title' => 'User Guides', 'icon' => 'BookOpen', 'link' => '#', 'order' => 1],
            ['title' => 'Video Tutorials', 'icon' => 'Video', 'link' => '#', 'order' => 2],
            ['title' => 'Tips & Tricks', 'icon' => 'Lightbulb', 'link' => '#', 'order' => 3],
            ['title' => 'API Documentation', 'icon' => 'Code', 'link' => '#', 'order' => 4],
            ['title' => 'Community Forums', 'icon' => 'MessageCircle', 'link' => '#', 'order' => 5],
        ];

        foreach ($defaultLinks as $link) {
            self::firstOrCreate(
                [
                    'title' => $link['title'],
                    'created_by' => $company_id
                ],
                [
                    'icon' => $link['icon'],
                    'link' => $link['link'],
                    'order' => $link['order'],
                    'creator_id' => 1,
                    'created_by' => $company_id
                ]
            );
        }
    }
}