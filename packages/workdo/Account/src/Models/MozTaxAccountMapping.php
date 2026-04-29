<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MozTaxAccountMapping extends Model
{
    protected $fillable = [
        'vat_output_account_id',
        'vat_input_account_id',
        'withholding_payable_account_id',
        'withholding_receivable_account_id',
        'irpc_expense_account_id',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function vatOutputAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_output_account_id');
    }

    public function vatInputAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_input_account_id');
    }

    public function withholdingPayableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'withholding_payable_account_id');
    }

    public function withholdingReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'withholding_receivable_account_id');
    }

    public function irpcExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'irpc_expense_account_id');
    }
}

