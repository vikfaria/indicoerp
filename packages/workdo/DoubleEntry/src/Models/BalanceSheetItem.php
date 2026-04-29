<?php

namespace Workdo\DoubleEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Account\Models\ChartOfAccount;

class BalanceSheetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance_sheet_id',
        'account_id',
        'section_type',
        'sub_section',
        'amount',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function balanceSheet()
    {
        return $this->belongsTo(BalanceSheet::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
