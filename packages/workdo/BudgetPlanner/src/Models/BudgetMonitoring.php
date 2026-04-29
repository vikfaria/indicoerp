<?php

namespace Workdo\BudgetPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BudgetMonitoring extends Model
{
    protected $fillable = [
        'budget_id',
        'monitoring_date',
        'total_allocated',
        'total_spent',
        'total_remaining',
        'variance_amount',
        'variance_percentage',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_date' => 'date',
            'total_allocated' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_remaining' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'variance_percentage' => 'decimal:2',
        ];
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

}
