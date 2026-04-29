<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class MozFiscalClosing extends Model
{
    protected $fillable = [
        'period_from',
        'period_to',
        'status',
        'close_reason',
        'reopen_reason',
        'snapshot',
        'closed_by',
        'reopened_by',
        'closed_at',
        'reopened_at',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'snapshot' => 'array',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}

