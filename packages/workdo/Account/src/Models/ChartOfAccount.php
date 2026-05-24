<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Workdo\Account\Models\AccountType;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'level',
        'normal_balance',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_system_account',
        'is_movement_account',
        'pgc_class',
        'tax_type',
        'vat_code',
        'deductibility',
        'financial_statement_line',
        'modelo20_line',
        'saft_taxonomy_code',
        'cost_center_required',
        'accounting_framework',
        'description',
        'account_type_id',
        'parent_account_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'is_system_account' => 'boolean',
            'is_movement_account' => 'boolean',
            'cost_center_required' => 'boolean',
            'pgc_class' => 'integer',
        ];
    }


    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function account_type()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function parent_account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }

    /**
     * Check if this account can receive direct journal entries.
     * Only movement (analytic) accounts accept postings.
     */
    public function isMovementAccount(): bool
    {
        return (bool) ($this->is_movement_account ?? true);
    }

    /**
     * Get the PGC class name for display.
     */
    public function getPgcClassName(): ?string
    {
        return match ($this->pgc_class) {
            0 => 'Contas de Ordem',
            1 => 'Meios Financeiros Líquidos',
            2 => 'Contas a Receber e a Pagar',
            3 => 'Inventários e Activos Biológicos',
            4 => 'Investimentos',
            5 => 'Capital, Reservas e Resultados Transitados',
            6 => 'Gastos e Perdas',
            7 => 'Rendimentos e Ganhos',
            8 => 'Resultados',
            default => null,
        };
    }
}