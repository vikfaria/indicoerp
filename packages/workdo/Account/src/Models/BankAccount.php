<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Account\Models\ChartOfAccount;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'account_name',
        'bank_name',
        'branch_name',
        'account_type',
        'payment_gateway',
        'opening_balance',
        'current_balance',
        'iban',
        'swift_code',
        'routing_number',
        'is_active',
        'gl_account_id',
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



    public function glAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function gl_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }
}