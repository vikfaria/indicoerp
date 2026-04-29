<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class OnboardingChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => 'boolean'
        ];
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class, 'checklist_id');
    }
}