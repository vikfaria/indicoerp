<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\HrmDocument;

class Acknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_id',
        'status',
        'acknowledgment_note',
        'acknowledged_at',
        'assigned_by',
        'employee_id',
        'document_id',
        'assigned_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'document_id' => 'integer',
            'acknowledged_at' => 'datetime',
            'assigned_by' => 'integer'
        ];
    }



    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function document()
    {
        return $this->belongsTo(HrmDocument::class, 'document_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by','id');
    }
}