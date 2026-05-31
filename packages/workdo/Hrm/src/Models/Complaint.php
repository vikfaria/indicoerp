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
        'is_confidential',
        'is_harassment_report',
        'confidential_channel',
        'confidentiality_level',
        'document',
        'resolved_by',
        'handling_owner_id',
        'resolution_date',
        'investigation_started_at',
        'investigation_closed_at',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'complaint_date' => 'date',
        'resolution_date' => 'date',
        'investigation_started_at' => 'date',
        'investigation_closed_at' => 'date',
        'is_confidential' => 'boolean',
        'is_harassment_report' => 'boolean',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }

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

    public function handlingOwner()
    {
        return $this->belongsTo(User::class, 'handling_owner_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
