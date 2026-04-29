<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Workdo\Account\Models\AccountType;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'level',
        'normal_balance',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_system_account',
        'description',
        'account_type_id',
        'parent_account_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean'
        ];
    }


    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function account_type()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function parent_account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }
}