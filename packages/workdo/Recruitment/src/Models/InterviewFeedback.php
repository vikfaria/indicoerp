<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Recruitment\Models\Interview;
use App\Models\User;

class InterviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'interview_feedbacks';

    protected $fillable = [
        'technical_rating',
        'communication_rating',
        'cultural_fit_rating',
        'overall_rating',
        'strengths',
        'weaknesses',
        'comments',
        'recommendation',
        'interview_id',
        'interviewer_ids',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recommendation' => 'string',
            'interviewer_ids' => 'array'
        ];
    }

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class);
    }
}