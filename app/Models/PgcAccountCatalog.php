<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PgcAccountCatalog extends Model
{
    protected $fillable = [
        'framework',
        'version',
        'class_number',
        'account_code',
        'account_name',
        'parent_code',
        'level',
        'normal_balance',
        'is_movement_account',
        'tax_type',
        'financial_statement_line',
        'modelo20_line',
        'saft_taxonomy_code',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'class_number' => 'integer',
            'level' => 'integer',
            'is_movement_account' => 'boolean',
        ];
    }

    public function scopeFramework($query, string $framework)
    {
        return $query->where('framework', $framework);
    }

    public function scopeByClass($query, int $classNumber)
    {
        return $query->where('class_number', $classNumber);
    }

    public function scopeMovementAccounts($query)
    {
        return $query->where('is_movement_account', true);
    }
}
