<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'file_name',
        'file_path',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}