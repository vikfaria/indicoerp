<?php

namespace Workdo\Retainer\Models;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Retainer extends Model
{
    protected $fillable = [
        'retainer_number',
        'retainer_date',
        'due_date',
        'customer_id',
        'warehouse_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'balance_amount',
        'status',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'retainer_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(RetainerPaymentAllocation::class, 'retainer_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $retainer): void {
            if (empty($retainer->retainer_number)) {
                $retainer->retainer_number = static::generateRetainerNumber();
            }

            if ($retainer->balance_amount === null) {
                $retainer->balance_amount = $retainer->total_amount ?? 0;
            }

            if (empty($retainer->status)) {
                $retainer->status = 'draft';
            }
        });
    }

    public static function generateRetainerNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastRetainer = static::where('retainer_number', 'like', "RT-{$year}-{$month}-%")
            ->where('created_by', creatorId())
            ->orderBy('retainer_number', 'desc')
            ->first();

        $nextNumber = $lastRetainer ? ((int) substr($lastRetainer->retainer_number, -3) + 1) : 1;

        return "RT-{$year}-{$month}-" . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
