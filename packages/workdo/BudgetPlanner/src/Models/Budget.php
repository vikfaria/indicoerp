<?php

namespace Workdo\BudgetPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_name',
        'period_id',
        'budget_type',
        'total_budget_amount',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_budget_amount' => 'decimal:2'
        ];
    }

    public function budgetPeriod()
    {
        return $this->belongsTo(BudgetPeriod::class, 'period_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function allocations()
    {
        return $this->hasMany(BudgetAllocation::class, 'budget_id');
    }
}
