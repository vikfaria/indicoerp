<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Hrm\Models\AnnouncementCategory;
use Workdo\Hrm\Models\Department;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'priority',
        'status',
        'announcement_category_id',
        'creator_id',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date'
        ];
    }

    // Accessor for consistent relationship display
    public function getNameAttribute()
    {
        return $this->title;
    }

    public function announcementCategory()
    {
        return $this->belongsTo(AnnouncementCategory::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_departments')
            ->withPivot('creator_id', 'created_by')
            ->withTimestamps();
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}