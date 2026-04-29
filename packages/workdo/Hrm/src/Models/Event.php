<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\Department;
use App\Models\User;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_type_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_type_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_by' => 'integer'
        ];
    }

    // Accessor for consistent relationship display
    public function getNameAttribute()
    {
        return $this->title;
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'event_departments')
            ->withPivot('creator_id', 'created_by')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}