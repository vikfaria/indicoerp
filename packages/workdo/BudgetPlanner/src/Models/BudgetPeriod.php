<?php

namespace Workdo\BudgetPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class BudgetPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_name',
        'financial_year',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date'
        ];
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'period_id');
    }
}
