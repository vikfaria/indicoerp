<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\OnboardingChecklist;
use App\Models\User;

class CandidateOnboarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'checklist_id',
        'start_date',
        'buddy_employee_id',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function checklist()
    {
        return $this->belongsTo(OnboardingChecklist::class);
    }

    public function buddy()
    {
        return $this->belongsTo(User::class, 'buddy_employee_id');
    }
}