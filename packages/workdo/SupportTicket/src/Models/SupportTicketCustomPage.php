<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportTicketCustomPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'enable_page_footer',
        'contents',
        'description',
        'creator_id',
        'created_by',
    ];

    public static function defaultdata($company_id)
    {
        $pages = [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'enable_page_footer' => 'on',
                'contents' => '<p>We collect information you provide when submitting support tickets, including your name, email, and issue details. We use this information to provide technical support and resolve issues. We do not sell, trade, or share your personal information with third parties without your consent, except as required by law.</p>',
                'description' => 'Your data privacy and security are our priority.',
                'creator_id' => $company_id,
                'created_by' => $company_id,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'enable_page_footer' => 'on',
                'contents' => '<p>By submitting a support ticket, you agree to provide accurate information and follow our support guidelines. We aim to respond to all tickets within 24-48 hours during business days. Critical issues will be prioritized. All information provided in support tickets will be kept confidential and used only for support purposes.</p>',
                'description' => 'Terms and conditions for using our support ticket system.',
                'creator_id' => $company_id,
                'created_by' => $company_id,
            ]
        ];

        foreach ($pages as $pageData) {
            self::firstOrCreate(
                ['slug' => $pageData['slug'], 'created_by' => $company_id],
                $pageData
            );
        }
    }
}