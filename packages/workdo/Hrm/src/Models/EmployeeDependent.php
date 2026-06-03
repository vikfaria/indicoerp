<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDependent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'full_name',
        'relationship',
        'date_of_birth',
        'document_number',
        'is_student',
        'is_tax_eligible',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'valid_until',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'date_of_birth' => 'date',
            'is_student' => 'boolean',
            'is_tax_eligible' => 'boolean',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'valid_until' => 'date',
            'creator_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }
}
