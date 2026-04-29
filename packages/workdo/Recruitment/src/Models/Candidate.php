<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\InterviewFeedback;
use Workdo\Recruitment\Models\Interview;
use Workdo\Recruitment\Models\CandidateAssessment;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'dob',
        'country',
        'state',
        'city',
        'current_company',
        'current_position',
        'experience_years',
        'current_salary',
        'expected_salary',
        'notice_period',
        'skills',
        'education',
        'portfolio_url',
        'linkedin_url',
        'profile_path',
        'resume_path',
        'cover_letter_path',
        'status',
        'application_date',
        'custom_question',
        'tracking_id',
        'job_id',
        'user_id',
        'source_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'current_salary' => 'decimal:2',
            'expected_salary' => 'decimal:2',
            'status' => 'string',
            'application_date' => 'date',
            'dob' => 'date'
        ];
    }

    public function job_posting()
    {
        return $this->belongsTo(JobPosting::class, 'job_id', 'id');
    }

    public function candidate_source()
    {
        return $this->belongsTo(CandidateSources::class, 'source_id', 'id');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'candidate_id', 'id');
    }

    public function interviewFeedbacks()
    {
        return $this->hasManyThrough(InterviewFeedback::class, Interview::class, 'candidate_id', 'interview_id', 'id', 'id');
    }

    public function candidateAssessments()
    {
        return $this->hasMany(CandidateAssessment::class, 'candidate_id', 'id');
    }

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public static function generateTrackingId($user_id = null)
    {
        $user_id = $user_id ?? creatorId();
        $prefix = 'TRK';
        $currentYear = date('Y');

        $lastCandidate = self::where('created_by', $user_id)
            ->where('tracking_id', 'LIKE', $prefix . $user_id . $currentYear . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCandidate) {
            $lastNumber = (int) substr($lastCandidate->tracking_id, strlen($prefix . $user_id . $currentYear));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $user_id . $currentYear . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($candidate) {
            if (empty($candidate->tracking_id)) {
                $candidate->tracking_id = static::generateTrackingId($candidate->created_by);
            }
        });
    }
}
