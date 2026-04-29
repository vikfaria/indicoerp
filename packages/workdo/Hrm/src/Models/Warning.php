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
            'document' => 'string'
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