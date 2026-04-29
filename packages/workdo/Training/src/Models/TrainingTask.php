<?php

namespace Workdo\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TrainingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'status',
        'due_date',
        'assigned_to',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function feedbacks()
    {
        return $this->hasMany(TrainingFeedback::class);
    }
}