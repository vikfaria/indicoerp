<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'cost',
        'start_date',
        'end_date',
        'summary',
        'status',
        'progress',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}