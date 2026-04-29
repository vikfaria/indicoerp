<?php

namespace Workdo\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\Employee;

class PerformanceEmployeeReview extends Model
{
    use HasFactory;

    protected $table = 'performance_employee_reviews';

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'review_cycle_id',
        'review_date',
        'completion_date',
        'rating',
        'pros',
        'cons',
        'status',
        'creator_id',
        'created_by',
    ];

    protected $appends = ['average_rating'];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'completion_date' => 'date',
            'rating' => 'array',
        ];
    }

    public function getAverageRatingAttribute()
    {
        if (!$this->rating) return null;
        
        $ratings = is_string($this->rating) ? json_decode($this->rating, true) : $this->rating;
        if (!is_array($ratings)) return null;
        
        $ratedValues = array_filter($ratings, fn($r) => $r > 0);
        return !empty($ratedValues) 
            ? round(array_sum($ratedValues) / count($ratedValues), 1) 
            : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewCycle()
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'review_cycle_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}