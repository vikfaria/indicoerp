<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyClosingChecklist extends Model
{
    protected $fillable = [
        'accounting_period_id',
        'company_id',
        'check_key',
        'check_name',
        'status',
        'completed_by',
        'completed_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Generate the standard closing checklist for a period.
     */
    public static function generateForPeriod(int $periodId, int $companyId): void
    {
        $checks = [
            ['key' => 'vat_reconciliation', 'name' => 'Reconciliação de IVA'],
            ['key' => 'bank_reconciliation', 'name' => 'Reconciliação bancária'],
            ['key' => 'accounts_receivable', 'name' => 'Revisão de contas a receber'],
            ['key' => 'accounts_payable', 'name' => 'Revisão de contas a pagar'],
            ['key' => 'stock_reconciliation', 'name' => 'Reconciliação de inventário'],
            ['key' => 'payroll_posting', 'name' => 'Lançamento de salários'],
            ['key' => 'depreciation', 'name' => 'Depreciações e amortizações'],
            ['key' => 'withholdings', 'name' => 'Retenções na fonte'],
            ['key' => 'provisions', 'name' => 'Provisões e acréscimos'],
            ['key' => 'intercompany', 'name' => 'Operações interempresa'],
            ['key' => 'trial_balance', 'name' => 'Verificação de balancete'],
        ];

        foreach ($checks as $check) {
            static::firstOrCreate(
                [
                    'accounting_period_id' => $periodId,
                    'check_key' => $check['key'],
                ],
                [
                    'company_id' => $companyId,
                    'check_name' => $check['name'],
                    'status' => 'pending',
                    'created_by' => $companyId,
                ]
            );
        }
    }

    /**
     * Check if all items in a period's checklist are completed/skipped/not_applicable.
     */
    public static function isChecklistComplete(int $periodId): bool
    {
        return !static::where('accounting_period_id', $periodId)
            ->where('status', 'pending')
            ->exists();
    }
}
