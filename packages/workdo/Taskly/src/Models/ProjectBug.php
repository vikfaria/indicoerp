<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Taskly\Models\BugStage;
use App\Models\User;

class ProjectBug extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'priority',
        'assigned_to',
        'description',
        'stage_id',
        'creator_id',
        'created_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function bugStage()
    {
        return $this->belongsTo(BugStage::class, 'stage_id');
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
        return $this->hasMany(BugComment::class, 'bug_id');
    }
}
