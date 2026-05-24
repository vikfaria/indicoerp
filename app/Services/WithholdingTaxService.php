<?php

namespace App\Services;

use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\Account\Models\ChartOfAccount;

/**
 * Withholding tax service for Mozambique.
 * Handles: calculating withholdings, creating journal entries, generating monthly declarations.
 */
class WithholdingTaxService
{
    /**
     * Calculate withholding for a vendor payment.
     */
    public function calculateWithholding(
        float $grossAmount,
        string $ruleCode,
        ?bool $isResident = true
    ): array {
        $rule = WithholdingTaxRule::where('code', $ruleCode)->where('is_active', true)->first();

        if (!$rule) {
            return [
                'gross_amount' => $grossAmount,
                'withholding_amount' => 0,
                'net_amount' => $grossAmount,
                'rate' => 0,
                'rule' => null,
                'error' => 'Regra de retenção não encontrada.',
            ];
        }

        // Check if rule applies to residency status
        if ($rule->applies_to !== 'both') {
            $required = $isResident ? 'resident' : 'non_resident';
            if ($rule->applies_to !== $required) {
                return [
                    'gross_amount' => $grossAmount,
                    'withholding_amount' => 0,
                    'net_amount' => $grossAmount,
                    'rate' => 0,
                    'rule' => $rule->toArray(),
                    'error' => 'Regra não aplicável ao estatuto de residência do fornecedor.',
                ];
            }
        }

        $withholdingAmount = round($grossAmount * $rule->rate / 100, 2);
        $netAmount = $grossAmount - $withholdingAmount;

        return [
            'gross_amount' => round($grossAmount, 2),
            'withholding_amount' => $withholdingAmount,
            'net_amount' => round($netAmount, 2),
            'rate' => (float) $rule->rate,
            'rule' => $rule->toArray(),
            'is_final_tax' => $rule->is_final_tax,
            'error' => null,
        ];
    }

    /**
     * Record a withholding transaction and create journal entries.
     */
    public function recordWithholding(
        int $companyId,
        string $ruleCode,
        float $grossAmount,
        string $transactionDate,
        ?int $vendorId = null,
        ?string $vendorNuit = null,
        ?string $vendorName = null,
        ?string $documentReference = null
    ): WithholdingTaxTransaction {
        $calc = $this->calculateWithholding($grossAmount, $ruleCode);

        if ($calc['error']) {
            throw new \RuntimeException($calc['error']);
        }

        $rule = WithholdingTaxRule::where('code', $ruleCode)->firstOrFail();
        $year = date('Y', strtotime($transactionDate));
        $month = (int) date('m', strtotime($transactionDate));

        return DB::transaction(function () use (
            $companyId, $rule, $calc, $transactionDate, $vendorId,
            $vendorNuit, $vendorName, $documentReference, $year, $month
        ) {
            // Create journal entry
            $journalEntry = $this->createWithholdingJournalEntry(
                $companyId, $rule, $calc, $transactionDate, $vendorName
            );

            // Record transaction
            return WithholdingTaxTransaction::create([
                'company_id' => $companyId,
                'withholding_rule_id' => $rule->id,
                'vendor_id' => $vendorId,
                'vendor_nuit' => $vendorNuit,
                'vendor_name' => $vendorName,
                'transaction_date' => $transactionDate,
                'document_reference' => $documentReference,
                'gross_amount' => $calc['gross_amount'],
                'withholding_rate' => $calc['rate'],
                'withholding_amount' => $calc['withholding_amount'],
                'net_amount' => $calc['net_amount'],
                'fiscal_year' => $year,
                'fiscal_month' => $month,
                'status' => 'pending',
                'journal_entry_id' => $journalEntry?->id,
                'created_by' => $companyId,
            ]);
        });
    }

    /**
     * Get monthly withholding summary for a declaration.
     */
    public function getMonthlyDeclaration(int $companyId, string $year, int $month): array
    {
        $transactions = WithholdingTaxTransaction::where('company_id', $companyId)
            ->where('fiscal_year', $year)
            ->where('fiscal_month', $month)
            ->with('rule')
            ->orderBy('transaction_date')
            ->get();

        $summary = $transactions->groupBy('withholding_rule_id')->map(function ($group) {
            $rule = $group->first()->rule;
            return [
                'rule_code' => $rule->code,
                'rule_name' => $rule->name,
                'income_type' => $rule->income_type,
                'rate' => $rule->rate,
                'transaction_count' => $group->count(),
                'total_gross' => $group->sum('gross_amount'),
                'total_withholding' => $group->sum('withholding_amount'),
                'total_net' => $group->sum('net_amount'),
            ];
        })->values();

        return [
            'period' => ['year' => $year, 'month' => $month],
            'transactions' => $transactions->toArray(),
            'summary' => $summary->toArray(),
            'totals' => [
                'gross' => $transactions->sum('gross_amount'),
                'withholding' => $transactions->sum('withholding_amount'),
                'net' => $transactions->sum('net_amount'),
            ],
        ];
    }

    /**
     * Create journal entry for a withholding.
     */
    private function createWithholdingJournalEntry(
        int $companyId,
        WithholdingTaxRule $rule,
        array $calc,
        string $date,
        ?string $vendorName
    ): ?JournalEntry {
        $debitAccount = ChartOfAccount::where('account_code', $rule->pgc_debit_account)
            ->where('created_by', $companyId)->first();
        $creditAccount = ChartOfAccount::where('account_code', $rule->pgc_credit_account)
            ->where('created_by', $companyId)->first();
        $supplierAccount = ChartOfAccount::where('account_code', '221')
            ->where('created_by', $companyId)->first();

        if (!$debitAccount || !$creditAccount) {
            return null;
        }

        $description = "Retenção na fonte - {$rule->name}" . ($vendorName ? " - {$vendorName}" : '');

        $entry = JournalEntry::create([
            'journal_date' => $date,
            'entry_type' => 'automatic',
            'reference_type' => 'withholding',
            'description' => $description,
            'total_debit' => $calc['gross_amount'],
            'total_credit' => $calc['gross_amount'],
            'status' => 'posted',
            'created_by' => $companyId,
        ]);

        // Debit: expense account (full gross amount)
        JournalEntryItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccount->id,
            'description' => $description,
            'debit_amount' => $calc['gross_amount'],
            'credit_amount' => 0,
            'created_by' => $companyId,
        ]);

        // Credit: withholding payable (retention amount)
        JournalEntryItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => "IRPC retido - {$calc['rate']}%",
            'debit_amount' => 0,
            'credit_amount' => $calc['withholding_amount'],
            'created_by' => $companyId,
        ]);

        // Credit: supplier (net amount)
        if ($supplierAccount) {
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $supplierAccount->id,
                'description' => "A pagar ao fornecedor (líquido de retenção)",
                'debit_amount' => 0,
                'credit_amount' => $calc['net_amount'],
                'created_by' => $companyId,
            ]);
        }

        return $entry;
    }
}
