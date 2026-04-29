<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\JobPosting;
use App\Models\User;
use Workdo\Hrm\Models\Department;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'offer_date',
        'position',
        'department_id',
        'salary',
        'bonus',
        'equity',
        'benefits',
        'start_date',
        'expiration_date',
        'offer_letter_path',
        'status',
        'response_date',
        'decline_reason',
        'converted_to_employee',
        'employee_id',
        'approved_by',
        'approval_status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'bonus' => 'decimal:2',
            'status' => 'string'
        ];
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}