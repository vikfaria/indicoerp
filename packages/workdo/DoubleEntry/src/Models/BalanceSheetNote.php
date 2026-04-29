<?php

namespace Workdo\DoubleEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BalanceSheetNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance_sheet_id',
        'note_number',
        'note_title',
        'note_content',
        'creator_id',
        'created_by',
    ];

    public function balanceSheet()
    {
        return $this->belongsTo(BalanceSheet::class);
    }
}