<?php

namespace Workdo\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'response_data',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}