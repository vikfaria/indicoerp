<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Revenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'revenue_number',
        'revenue_date',
        'category_id',
        'bank_account_id',
        'chart_of_account_id',
        'amount',
        'description',
        'reference_number',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'revenue_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(RevenueCategories::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($revenue) {
            if (empty($revenue->revenue_number)) {
                $revenue->revenue_number = static::generateRevenueNumber($revenue->created_by);
            }
        });
    }

    public static function generateRevenueNumber($createdBy = null): string
    {
        $year = date('Y');
        $month = date('m');
        
        if ($createdBy) {
            $userId = $createdBy;
        } elseif (auth()->check()) {
            $userId = creatorId();
        } else {
            $userId = 1;
        }
        
        $lastRevenue = static::where('revenue_number', 'like', "REV-{$year}-{$month}-%")
            ->where('created_by', $userId)
            ->orderBy('revenue_number', 'desc')
            ->first();

        if ($lastRevenue) {
            $lastNumber = (int) substr($lastRevenue->revenue_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "REV-{$year}-{$month}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
