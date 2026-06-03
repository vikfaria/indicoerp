<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalExportHistory extends Model
{
    protected $fillable = [
        'company_id',
        'export_type',
        'period_start',
        'period_end',
        'generated_by',
        'file_name',
        'file_hash',
        'file_path',
        'status',
        'submission_channel',
        'submission_reference',
        'submitted_at',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'submitted_at' => 'datetime',
        'metadata' => 'array',
    ];
}
