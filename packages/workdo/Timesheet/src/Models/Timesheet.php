<?php

namespace Workdo\Timesheet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Timesheet extends Model
{
    use HasFactory;

    protected $table = 'timesheets';

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'date',
        'hours',
        'minutes',
        'notes',
        'type',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'integer',
            'minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo('\Workdo\Taskly\Models\Project');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo('\Workdo\Taskly\Models\ProjectTask');
    }
}