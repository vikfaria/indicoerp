<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecruitmentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'created_by',
    ];
}