<?php

namespace App\Services;

use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use Illuminate\Database\Eloquent\Builder;
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
    public function getMonthlyDeclaration(int $companyId, string $year, int $month, array $filters = []): array
    {
        $transactionsQuery = WithholdingTaxTransaction::query()
            ->where('company_id', $companyId)
            ->where('fiscal_year', $year)
            ->where('fiscal_month', $month)
            ->with('rule');

        $transactionsQuery = $this->applyDeclarationFilters($transactionsQuery, $filters);

        $transactions = $transactionsQuery
            ->orderBy('transaction_date')
            ->orderBy('id')
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

        $detailedMap = $transactions->map(function (WithholdingTaxTransaction $transaction): array {
            $rule = $transaction->rule;
            $incomeType = (string) ($transaction->income_type_snapshot ?: $rule?->income_type ?: 'other');
            $beneficiaryCountry = $transaction->beneficiary_country;
            $residencyStatus = $transaction->beneficiary_residency_status;

            return [
                'id' => (int) $transaction->id,
                'transaction_date' => optional($transaction->transaction_date)?->toDateString(),
                'document_reference' => $transaction->document_reference,
                'source_reference_type' => $transaction->source_reference_type,
                'source_reference_id' => $transaction->source_reference_id,
                'vendor_id' => $transaction->vendor_id ? (int) $transaction->vendor_id : null,
                'beneficiary' => $transaction->vendor_name,
                'beneficiary_tax_number' => $transaction->vendor_nuit,
                'beneficiary_country' => $beneficiaryCountry,
                'beneficiary_residency_status' => $residencyStatus,
                'income_type' => $incomeType,
                'rule_code' => $rule?->code,
                'rule_name' => $rule?->name,
                'withholding_treatment' => $transaction->withholding_treatment,
                'rate' => (float) $transaction->withholding_rate,
                'gross_amount' => (float) $transaction->gross_amount,
                'withholding_amount' => (float) $transaction->withholding_amount,
                'net_amount' => (float) $transaction->net_amount,
                'status' => $transaction->status,
                'declaration_reference' => $transaction->declaration_reference,
                'declared_at' => optional($transaction->declared_at)?->toDateTimeString(),
                'state_payment_reference' => $transaction->state_payment_reference,
                'paid_at' => optional($transaction->paid_at)?->toDateTimeString(),
                'adt_applied' => (bool) $transaction->adt_applied,
                'adt_certificate_reference' => $transaction->adt_certificate_reference,
                'fiscal_compliance_reference' => $transaction->fiscal_compliance_reference,
                'financial_approval_reference' => $transaction->financial_approval_reference,
                'fx_authorization_reference' => $transaction->fx_authorization_reference,
            ];
        })->values();

        $historyByVendor = $detailedMap
            ->groupBy(fn (array $line): string => sprintf(
                '%s|%s|%s',
                (string) ($line['beneficiary_tax_number'] ?? ''),
                (string) ($line['beneficiary'] ?? ''),
                (string) ($line['income_type'] ?? '')
            ))
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'beneficiary' => $first['beneficiary'] ?? null,
                    'beneficiary_tax_number' => $first['beneficiary_tax_number'] ?? null,
                    'beneficiary_country' => $first['beneficiary_country'] ?? null,
                    'beneficiary_residency_status' => $first['beneficiary_residency_status'] ?? null,
                    'income_type' => $first['income_type'] ?? null,
                    'transactions' => count($group),
                    'gross_amount' => (float) collect($group)->sum('gross_amount'),
                    'withholding_amount' => (float) collect($group)->sum('withholding_amount'),
                    'net_amount' => (float) collect($group)->sum('net_amount'),
                ];
            })
            ->values();

        $statusSummary = $detailedMap
            ->groupBy(fn (array $line): string => (string) ($line['status'] ?? 'pending'))
            ->map(function ($group, string $status): array {
                return [
                    'status' => $status,
                    'transactions' => count($group),
                    'withholding_amount' => (float) collect($group)->sum('withholding_amount'),
                    'gross_amount' => (float) collect($group)->sum('gross_amount'),
                ];
            })
            ->values();

        $totalWithholding = (float) $transactions->sum('withholding_amount');
        $paidWithholding = (float) $transactions->where('status', 'paid')->sum('withholding_amount');
        $declaredWithholding = (float) $transactions->where('status', 'declared')->sum('withholding_amount');

        return [
            'period' => ['year' => $year, 'month' => $month],
            'transactions' => $transactions->toArray(),
            'detailed_map' => $detailedMap->all(),
            'history_by_vendor' => $historyByVendor->all(),
            'status_summary' => $statusSummary->all(),
            'summary' => $summary->toArray(),
            'totals' => [
                'gross' => $transactions->sum('gross_amount'),
                'withholding' => $totalWithholding,
                'net' => $transactions->sum('net_amount'),
                'withholding_paid' => $paidWithholding,
                'withholding_declared' => $declaredWithholding,
                'withholding_pending' => max(0, $totalWithholding - $paidWithholding),
            ],
            'filters' => [
                'vendor_id' => $filters['vendor_id'] ?? null,
                'vendor_nuit' => $filters['vendor_nuit'] ?? null,
                'income_type' => $filters['income_type'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
        ];
    }

    private function applyDeclarationFilters(Builder $query, array $filters): Builder
    {
        $vendorId = $filters['vendor_id'] ?? null;
        if ($vendorId !== null && $vendorId !== '') {
            $query->where('vendor_id', (int) $vendorId);
        }

        $vendorNuit = trim((string) ($filters['vendor_nuit'] ?? ''));
        if ($vendorNuit !== '') {
            $query->where('vendor_nuit', 'like', '%' . $vendorNuit . '%');
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['pending', 'declared', 'paid'], true)) {
            $query->where('status', $status);
        }

        $incomeType = trim((string) ($filters['income_type'] ?? ''));
        if ($incomeType !== '') {
            $query->where(function (Builder $incomeTypeQuery) use ($incomeType): void {
                $incomeTypeQuery->where('income_type_snapshot', $incomeType)
                    ->orWhereHas('rule', function (Builder $ruleQuery) use ($incomeType): void {
                        $ruleQuery->where('income_type', $incomeType);
                    });
            });
        }

        return $query;
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
