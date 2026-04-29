<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class EmployeeDocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_name',
        'description',
        'is_required',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean'
        ];
    }




}