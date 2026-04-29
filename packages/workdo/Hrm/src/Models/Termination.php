<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\TerminationType;

class Termination extends Model
{
    use HasFactory;

    protected $fillable = [
        'notice_date',
        'termination_date',
        'reason',
        'description',
        'document',
        'employee_id',
        'termination_type_id',
        'status',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'termination_date' => 'date',
            'document' => 'string'
        ];
    }



    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}