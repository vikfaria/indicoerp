<?php

namespace Workdo\Retainer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Workdo\Account\Models\BankAccount;

class RetainerPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'payment_date',
        'customer_id',
        'bank_account_id',
        'reference_number',
        'payment_amount',
        'status',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RetainerPaymentAllocation::class, 'payment_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payment): void {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }

            if (empty($payment->status)) {
                $payment->status = 'pending';
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastPayment = static::where('payment_number', 'like', "RP-{$year}-{$month}-%")
            ->where('created_by', creatorId())
            ->orderBy('payment_number', 'desc')
            ->first();

        $nextNumber = $lastPayment ? ((int) substr($lastPayment->payment_number, -3) + 1) : 1;

        return "RP-{$year}-{$month}-" . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
