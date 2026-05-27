<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\WarningType;

class Warning extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'severity',
        'warning_date',
        'note_of_culpa_issued_at',
        'note_of_culpa_delivered_at',
        'worker_refused_note_of_culpa',
        'refusal_witness_one_name',
        'refusal_witness_two_name',
        'response_deadline_at',
        'decision_deadline_at',
        'disciplinary_sanction',
        'disciplinary_decision_at',
        'description',
        'document',
        'employee_id',
        'warning_by',
        'warning_type_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'severity' => 'string',
            'warning_date' => 'date',
            'note_of_culpa_issued_at' => 'date',
            'note_of_culpa_delivered_at' => 'date',
            'worker_refused_note_of_culpa' => 'boolean',
            'response_deadline_at' => 'date',
            'decision_deadline_at' => 'date',
            'disciplinary_decision_at' => 'date',
            'document' => 'string',
        ];
    }



    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function warningBy()
    {
        return $this->belongsTo(User::class,'warning_by','id');
    }

    public function warningType()
    {
        return $this->belongsTo(WarningType::class,);
    }
}
