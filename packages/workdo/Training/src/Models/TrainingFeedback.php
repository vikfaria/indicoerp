<?php

namespace Workdo\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TrainingFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_task_id',
        'user_id',
        'rating',
        'comments',
        'creator_id',
        'created_by',
    ];

    public function trainingTask()
    {
        return $this->belongsTo(TrainingTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}