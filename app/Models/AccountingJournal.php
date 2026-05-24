<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;

class AccountingJournal extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'default_debit_account_id',
        'default_credit_account_id',
        'requires_attachment',
        'is_active',
        'numbering_prefix',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function defaultDebitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_debit_account_id');
    }

    public function defaultCreditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_credit_account_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'accounting_journal_id');
    }

    /**
     * Get the numbering prefix for this journal.
     * Falls back to journal code if no prefix is set.
     */
    public function getPrefix(): string
    {
        return $this->numbering_prefix ?: $this->code;
    }

    /**
     * Seed default journals for a company.
     */
    public static function seedDefaults(int $companyId): void
    {
        $defaults = [
            ['code' => 'CX', 'name' => 'Diário de Caixa', 'type' => 'cash', 'prefix' => 'CX'],
            ['code' => 'BK', 'name' => 'Diário de Bancos', 'type' => 'bank', 'prefix' => 'BK'],
            ['code' => 'VD', 'name' => 'Diário de Vendas', 'type' => 'sales', 'prefix' => 'VD'],
            ['code' => 'CP', 'name' => 'Diário de Compras', 'type' => 'purchases', 'prefix' => 'CP'],
            ['code' => 'SL', 'name' => 'Diário de Salários', 'type' => 'salaries', 'prefix' => 'SL'],
            ['code' => 'RG', 'name' => 'Diário de Regularizações', 'type' => 'adjustments', 'prefix' => 'RG'],
            ['code' => 'AB', 'name' => 'Diário de Abertura', 'type' => 'opening', 'prefix' => 'AB'],
            ['code' => 'FC', 'name' => 'Diário de Fecho', 'type' => 'closing', 'prefix' => 'FC'],
            ['code' => 'AF', 'name' => 'Diário de Activos Fixos', 'type' => 'fixed_assets', 'prefix' => 'AF'],
            ['code' => 'FS', 'name' => 'Diário Fiscal', 'type' => 'fiscal', 'prefix' => 'FS'],
            ['code' => 'DV', 'name' => 'Diário Diversos', 'type' => 'general', 'prefix' => 'DV'],
        ];

        foreach ($defaults as $journal) {
            static::firstOrCreate(
                ['company_id' => $companyId, 'code' => $journal['code']],
                [
                    'name' => $journal['name'],
                    'type' => $journal['type'],
                    'numbering_prefix' => $journal['prefix'],
                    'is_active' => true,
                    'created_by' => $companyId,
                ]
            );
        }
    }
}
