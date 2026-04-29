<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Account\Models\AccountCategory;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'normal_balance',
        'description',
        'is_active',
        'is_system_type',
        'category_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'is_active' => 'boolean',
            'is_system_type' => 'boolean'
        ];
    }



    public function category()
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function chartOfAccounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'account_type_id');
    }
}
