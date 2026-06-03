<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalComplianceAlert extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';

    protected $fillable = [
        'company_id',
        'alert_key',
        'label',
        'severity',
        'count',
        'status',
        'times_triggered',
        'first_detected_at',
        'last_detected_at',
        'resolved_at',
        'last_snapshot_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'count' => 'integer',
            'times_triggered' => 'integer',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_snapshot_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
