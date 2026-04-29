<?php

namespace Workdo\Taskly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'client_id',
    ];

    protected $casts = [
        //
    ];
}
