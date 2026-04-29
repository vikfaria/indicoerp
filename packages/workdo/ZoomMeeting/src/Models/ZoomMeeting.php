<?php

namespace Workdo\ZoomMeeting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class ZoomMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'meeting_id',
        'meeting_password',
        'start_url',
        'join_url',
        'start_time',
        'duration',
        'host_video',
        'participant_video',
        'waiting_room',
        'recording',
        'status',
        'participants',
        'host_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'host_video' => 'boolean',
            'participant_video' => 'boolean',
            'waiting_room' => 'boolean',
            'recording' => 'boolean',
            'participants' => 'array'
        ];
    }

    // Accessor for consistent relationship display
    public function getNameAttribute()
    {
        return $this->title;
    }

    public function host()
    {
        return $this->belongsTo(User::class);
    }
}