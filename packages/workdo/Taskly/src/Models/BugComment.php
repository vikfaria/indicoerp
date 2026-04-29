<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BugComment extends Model
{
    protected $fillable = [
        'bug_id',
        'user_id',
        'comment',
    ];

    public function bug()
    {
        return $this->belongsTo(ProjectBug::class, 'bug_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
