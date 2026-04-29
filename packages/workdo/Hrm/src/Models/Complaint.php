<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'against_employee_id',
        'complaint_type_id',
        'subject',
        'description',
        'complaint_date',
        'status',
        'document',
        'resolved_by',
        'resolution_date',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'complaint_date' => 'date',
        'resolution_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function againstEmployee()
    {
        return $this->belongsTo(User::class, 'against_employee_id');
    }

    public function complaintType()
    {
        return $this->belongsTo(ComplaintType::class, 'complaint_type_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}