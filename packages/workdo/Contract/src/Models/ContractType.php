<?php

namespace Workdo\Contract\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ContractType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean'
        ];
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'type_id');
    }
}
