<?php

namespace Workdo\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'code',
        'is_active',
        'default_layout',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    public function getFieldsCountAttribute()
    {
        return $this->fields()->count();
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function conversion()
    {
        return $this->hasOne(FormConversion::class);
    }

    public static function generateCode()
    {
        return str()->uuid()->toString() . '-' . time();
    }
}