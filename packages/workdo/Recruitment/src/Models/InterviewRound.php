<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\JobPosting;

class InterviewRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sequence_number',
        'description',
        'status',
        'job_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string'
        ];
    }

    public function job_posting()
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }
}