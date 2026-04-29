<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'created_by',
        'creator_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function getAllByCompany($companyId)
    {
        return static::where('created_by', $companyId)
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function set($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key, 'created_by' => creatorId()],
            ['value' => $value]
        );
    }

    public static function defaultdata($company_id)
    {
        $defaultSettings = [
            'logo_dark' => 'logo.png',
            'favicon' => 'favicon.png',
            'title_text' => 'Support Ticket System',
            'footer_text' => '© ' . date('Y') . ' Support System. All rights reserved.'
        ];

        foreach ($defaultSettings as $key => $value) {
            static::firstOrCreate(
                ['key' => $key, 'created_by' => $company_id],
                ['value' => $value, 'creator_id' => auth()->id()]
            );
        }
    }
}