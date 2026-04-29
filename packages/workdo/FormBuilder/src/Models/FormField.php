<?php

namespace Workdo\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'label',
        'type',
        'required',
        'placeholder',
        'options',
        'order',
        'creator_id',
        'created_by',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
    ];

    public static function getFieldTypes()
    {
        return [
            'text' => 'Text',
            'email' => 'Email',
            'number' => 'Number',
            'tel' => 'Phone',
            'url' => 'URL',
            'password' => 'Password',
            'textarea' => 'Textarea',
            'select' => 'Select',
            'radio' => 'Radio',
            'checkbox' => 'Checkbox',
            'date' => 'Date',
            'time' => 'Time',
        ];
    }
}