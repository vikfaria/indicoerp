<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Taskly\Models\TaskStage;
use App\Models\User;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'title',
        'priority',
        'assigned_to',
        'duration',
        'description',
        'stage_id',
        'creator_id',
        'created_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function taskStage()
    {
        return $this->belongsTo(TaskStage::class, 'stage_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUsers()
    {
        $userIds = $this->assigned_to ? explode(',', $this->assigned_to) : [];
        return User::whereIn('id', $userIds)->get();
    }

    public function getAssignedUserIdsAttribute()
    {
        return $this->assigned_to ? explode(',', $this->assigned_to) : [];
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(TaskSubtask::class, 'task_id');
    }
}
