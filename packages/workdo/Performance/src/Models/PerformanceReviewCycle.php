<?php

namespace Workdo\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class PerformanceReviewCycle extends Model
{
    use HasFactory;

    protected $table = 'performance_review_cycles';

    protected $fillable = [
        'name',
        'frequency',
        'description',
        'status',
        'creator_id',
        'created_by',
    ];

    public function employeeReviews()
    {
        return $this->hasMany(PerformanceEmployeeReview::class, 'review_cycle_id');
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