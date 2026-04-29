<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'type',
        'amount',
        'creator_id',
        'created_by',
    ];

    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }
}