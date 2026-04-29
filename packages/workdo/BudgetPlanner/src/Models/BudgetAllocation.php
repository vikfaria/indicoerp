<?php

namespace Workdo\BudgetPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Account\Models\ChartOfAccount;
use App\Models\User;

class BudgetAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'allocated_amount',
        'spent_amount',
        'remaining_amount',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2'
        ];
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

}
