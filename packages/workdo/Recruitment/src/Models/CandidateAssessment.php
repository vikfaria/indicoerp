<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\Candidate;
use App\Models\User;

class CandidateAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_name',
        'score',
        'max_score',
        'pass_fail_status',
        'comments',
        'assessment_date',
        'candidate_id',
        'conducted_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pass_fail_status' => 'string'
        ];
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by', 'id');
    }
}