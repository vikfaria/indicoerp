<?php

namespace Workdo\DoubleEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BalanceSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance_sheet_date',
        'financial_year',
        'total_assets',
        'total_liabilities',
        'total_equity',
        'is_balanced',
        'status',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'balance_sheet_date' => 'date',
        'total_assets' => 'decimal:2',
        'total_liabilities' => 'decimal:2',
        'total_equity' => 'decimal:2',
        'is_balanced' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(BalanceSheetItem::class);
    }

    public function notes()
    {
        return $this->hasMany(BalanceSheetNote::class);
    }
}