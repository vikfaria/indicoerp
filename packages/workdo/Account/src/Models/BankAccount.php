<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Hrm\Models\Branch;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'account_name',
        'bank_name',
        'branch_name',
        'branch_id',
        'account_type',
        'payment_gateway',
        'opening_balance',
        'current_balance',
        'iban',
        'swift_code',
        'routing_number',
        'is_active',
        'is_electronic_money_account',
        'electronic_money_entity',
        'electronic_money_level',
        'electronic_money_daily_limit_mzn',
        'electronic_money_monthly_limit_mzn',
        'electronic_money_limit_exempt_for_enterprise',
        'electronic_money_account_purpose',
        'gl_account_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'is_electronic_money_account' => 'boolean',
            'electronic_money_daily_limit_mzn' => 'decimal:2',
            'electronic_money_monthly_limit_mzn' => 'decimal:2',
            'electronic_money_limit_exempt_for_enterprise' => 'boolean',
        ];
    }



    public function glAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function gl_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
