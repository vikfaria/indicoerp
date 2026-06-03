<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'clock_in',
        'clock_out',
        'break_hour',
        'total_hour',
        'overtime_hours',
        'overtime_amount',
        'status',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'source_channel',
        'source_device_id',
        'source_device_label',
        'source_reference',
        'is_justified',
        'absence_category',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'shift_id' => 'integer',
            'date' => 'date',
            'break_hour' => 'decimal:2',
            'total_hour' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'is_justified' => 'boolean',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }


    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Process complete attendance - calculate everything automatically.
     */

}
