<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class CustomerPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'payment_date',
        'customer_id',
        'bank_account_id',
        'payment_method',
        'mobile_money_provider',
        'mobile_money_number',
        'reference_number',
        'payment_amount',
        'currency_code',
        'exchange_rate',
        'foreign_amount',
        'amount_mzn',
        'fx_difference_amount',
        'is_export_receipt',
        'receipt_origin_country',
        'export_reference',
        'intermediary_bank',
        'repatriation_status',
        'repatriated_amount_mzn',
        'fx_compliance_reference',
        'gifim_alert_required',
        'gifim_alert_category',
        'gifim_alert_status',
        'gifim_reference',
        'gifim_reported_at',
        'gifim_reported_by',
        'gifim_submitted_document',
        'gifim_justification',
        'high_value_approval_reference',
        'status',
        'approval_required',
        'approval_status',
        'approval_risk_flags',
        'approval_requested_at',
        'approval_reference',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'notes',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'foreign_amount' => 'decimal:2',
        'amount_mzn' => 'decimal:2',
        'fx_difference_amount' => 'decimal:2',
        'is_export_receipt' => 'boolean',
        'repatriated_amount_mzn' => 'decimal:2',
        'gifim_alert_required' => 'boolean',
        'gifim_reported_at' => 'datetime',
        'approval_required' => 'boolean',
        'approval_risk_flags' => 'array',
        'approval_requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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
        return $this->hasMany(CustomerPaymentAllocation::class, 'payment_id');
    }

    public function creditNoteApplications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class, 'payment_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customerPayment) {
            if (empty($customerPayment->payment_number)) {
                $customerPayment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastPayment = static::where('payment_number', 'like', "CP-{$year}-{$month}-%")
            ->where('created_by', creatorId())
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "CP-{$year}-{$month}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
