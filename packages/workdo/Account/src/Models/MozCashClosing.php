<?php

namespace Workdo\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MozCashClosing extends Model
{
    protected $table = 'mz_cash_closings';

    protected $fillable = [
        'bank_account_id',
        'closing_date',
        'status',
        'opening_balance_mzn',
        'cash_in_mzn',
        'cash_out_mzn',
        'expected_balance_mzn',
        'counted_balance_mzn',
        'variance_mzn',
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
            'closing_date' => 'date',
            'opening_balance_mzn' => 'decimal:2',
            'cash_in_mzn' => 'decimal:2',
            'cash_out_mzn' => 'decimal:2',
            'expected_balance_mzn' => 'decimal:2',
            'counted_balance_mzn' => 'decimal:2',
            'variance_mzn' => 'decimal:2',
            'snapshot' => 'array',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
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
