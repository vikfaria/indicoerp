<?php

namespace Workdo\Goal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalTracking extends Model
{
    use HasFactory;

    protected $table = 'goal_tracking';

    protected $fillable = [
        'goal_id',
        'tracking_date',
        'previous_amount',
        'contribution_amount',
        'current_amount',
        'progress_percentage',
        'days_remaining',
        'projected_completion_date',
        'on_track_status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_amount' => 'decimal:2',
            'contribution_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'progress_percentage' => 'decimal:2',
            'tracking_date' => 'date',
            'projected_completion_date' => 'date',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }
}