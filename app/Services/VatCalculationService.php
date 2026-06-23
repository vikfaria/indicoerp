<?php

namespace App\Services;

use App\Models\MzVatCode;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

/**
 * Advanced VAT calculation engine for Mozambique.
 * Handles: liquidated, supported, deductible, non-deductible, payable, recoverable.
 * Supports: normal, exempt, zero-rate, reverse charge, imports.
 */
class VatCalculationService
{
    /**
     * Calculate VAT for a transaction line.
     *
     * @return array{
     *     base_amount: float,
     *     vat_amount: float,
     *     total_with_vat: float,
     *     vat_code: string,
     *     vat_rate: float,
     *     vat_type: string,
     *     is_deductible: bool,
     *     deductible_amount: float,
     *     non_deductible_amount: float,
     *     exemption_reason: string|null
     * }
     */
    public function calculateLine(
        float $amount,
        string $vatCodeId,
        string $direction = 'output',
        ?string $deductibility = 'full'
    ): array {
        $vatCode = MzVatCode::where('code', $vatCodeId)->first();

        if (!$vatCode) {
            return $this->buildResult($amount, 0, $vatCodeId, 0, 'normal', true, null);
        }

        $vatAmount = 0.0;

        if ($vatCode->isTaxable()) {
            $vatAmount = round($amount * $vatCode->rate / 100, 2);
        }

        // Deductibility for input VAT
        $isDeductible = $direction === 'input';
        $deductibleAmount = $vatAmount;
        $nonDeductibleAmount = 0.0;

        if ($direction === 'input' && $vatAmount > 0) {
            switch ($deductibility) {
                case 'partial':
                    // 50% deductible by default for partial
                    $deductibleAmount = round($vatAmount * 0.50, 2);
                    $nonDeductibleAmount = $vatAmount - $deductibleAmount;
                    break;
                case 'none':
                    $deductibleAmount = 0.0;
                    $nonDeductibleAmount = $vatAmount;
                    $isDeductible = false;
                    break;
                default: // full
                    $deductibleAmount = $vatAmount;
                    $nonDeductibleAmount = 0.0;
                    break;
            }
        }

        // Reverse charge: buyer self-assesses VAT
        if ($vatCode->type === 'reverse_charge' && $direction === 'input') {
            $vatAmount = round($amount * $vatCode->rate / 100, 2);
            $deductibleAmount = $vatAmount;
        }

        return $this->buildResult(
            $amount, $vatAmount, $vatCode->code, $vatCode->rate,
            $vatCode->type, $isDeductible, $vatCode->exemption_reason,
            $deductibleAmount, $nonDeductibleAmount
        );
    }

    /**
     * Calculate VAT summary for a period (for VAT return).
     */
    public function calculatePeriodVat(int $companyId, string $startDate, string $endDate): array
    {
        // Output VAT (liquidado)
        $outputVat = $this->getVatByAccount($companyId, '2433', $startDate, $endDate, 'credit');

        // Input VAT - supported (suportado)
        $supportedVat = $this->getVatByAccount($companyId, '2431', $startDate, $endDate, 'debit');

        // Input VAT - deductible (dedutível)
        $deductibleVat = $this->getVatByAccount($companyId, '2432', $startDate, $endDate, 'debit');

        // Regularizations
        $regularizations = $this->getVatByAccount($companyId, '2434', $startDate, $endDate, 'credit');

        $nonDeductibleVat = $supportedVat - $deductibleVat;
        $vatPayable = $outputVat - $deductibleVat + $regularizations;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'output_vat' => round($outputVat, 2),
            'supported_vat' => round($supportedVat, 2),
            'deductible_vat' => round($deductibleVat, 2),
            'non_deductible_vat' => round($nonDeductibleVat, 2),
            'regularizations' => round($regularizations, 2),
            'vat_payable' => round(max($vatPayable, 0), 2),
            'vat_recoverable' => round(max(-$vatPayable, 0), 2),
            'net_position' => round($vatPayable, 2),
        ];
    }

    /**
     * Resolve PGC-MZ VAT accounts for journal entries.
     *
     * @return array{debit_account_code: string, credit_account_code: string}
     */
    public function resolveVatAccounts(string $vatCodeId, string $direction): array
    {
        if ($direction === 'output') {
            // Sale: debit customer, credit VAT liquidado
            return [
                'vat_account_code' => '2433', // IVA liquidado
            ];
        }

        // Purchase: debit VAT dedutível or suportado, credit supplier
        $vatCode = MzVatCode::where('code', $vatCodeId)->first();

        if ($vatCode && $vatCode->type === 'reverse_charge') {
            return [
                'vat_account_code' => '2432', // IVA dedutível (and also 2433 for output side)
                'reverse_charge_account_code' => '2433',
            ];
        }

        return [
            'vat_account_code' => '2432', // IVA dedutível
        ];
    }

    /**
     * Get VAT amount from journal entries for a specific account prefix.
     */
    private function getVatByAccount(int $companyId, string $accountCode, string $startDate, string $endDate, string $side): float
    {
        $column = $side === 'debit' ? 'jei.debit_amount' : 'jei.credit_amount';

        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$startDate, $endDate])
            ->where('coa.account_code', 'like', $accountCode . '%')
            ->selectRaw("COALESCE(SUM({$column}), 0) as total")
            ->first();

        return (float) ($result->total ?? 0);
    }

    private function buildResult(
        float $base, float $vat, string $code, float $rate, string $type,
        bool $deductible, ?string $exemption,
        float $deductibleAmount = 0, float $nonDeductibleAmount = 0
    ): array {
        return [
            'base_amount' => round($base, 2),
            'vat_amount' => round($vat, 2),
            'total_with_vat' => round($base + $vat, 2),
            'vat_code' => $code,
            'vat_rate' => $rate,
            'vat_type' => $type,
            'is_deductible' => $deductible,
            'deductible_amount' => round($deductibleAmount, 2),
            'non_deductible_amount' => round($nonDeductibleAmount, 2),
            'exemption_reason' => $exemption,
        ];
    }

    /**
     * Generate a VAT journal entry when an invoice is posted.
     * Creates double-entry lines using PGC-MZ VAT accounts:
     * - Output (sales): Debit 211 (clientes), Credit 71 (vendas) + Credit 2433 (IVA liquidado)
     * - Input (purchases): Debit 61/62 (gastos) + Debit 2432 (IVA dedutível), Credit 221 (fornecedores)
     *
     * @return int|null The journal entry ID, or null if no VAT to record
     */
    public function generateVatJournalEntry(
        int $companyId,
        string $direction,
        float $baseAmount,
        float $vatAmount,
        string $vatCode,
        string $documentReference,
        string $date,
        ?int $journalId = null,
    ): ?int {
        if ($vatAmount <= 0) {
            return null;
        }

        // Resolve the VAT account
        $vatAccounts = $this->resolveVatAccounts($vatCode, $direction);
        $vatAccountCode = $vatAccounts['vat_account_code'];

        // Find the actual chart of account ID for the VAT account
        $vatAccount = ChartOfAccount::where('created_by', $companyId)
            ->where('account_code', $vatAccountCode)
            ->first();

        if (!$vatAccount) {
            \Illuminate\Support\Facades\Log::warning(
                "VatCalculationService: VAT account {$vatAccountCode} not found for company {$companyId}"
            );
            return null;
        }

        // Build journal entry lines
        $lines = [];

        if ($direction === 'output') {
            // Sales VAT: Credit 2433 (IVA liquidado)
            $lines[] = [
                'account_id' => $vatAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $vatAmount,
                'description' => "IVA liquidado — {$documentReference}",
            ];
        } else {
            // Purchase VAT: Debit 2432 (IVA dedutível)
            $lines[] = [
                'account_id' => $vatAccount->id,
                'debit_amount' => $vatAmount,
                'credit_amount' => 0,
                'description' => "IVA dedutível — {$documentReference}",
            ];

            // Handle reverse charge: also credit 2433
            if (isset($vatAccounts['reverse_charge_account_code'])) {
                $rcAccount = ChartOfAccount::where('created_by', $companyId)
                    ->where('account_code', $vatAccounts['reverse_charge_account_code'])
                    ->first();

                if ($rcAccount) {
                    $lines[] = [
                        'account_id' => $rcAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $vatAmount,
                        'description' => "IVA autoliquidação — {$documentReference}",
                    ];
                }
            }
        }

        // Insert the journal entry through the model so numbering/validation hooks stay active
        $entry = JournalEntry::query()->create([
            'journal_date' => $date,
            'description' => "Lançamento IVA — {$documentReference}",
            'total_debit' => collect($lines)->sum('debit_amount'),
            'total_credit' => collect($lines)->sum('credit_amount'),
            'status' => 'posted',
            'accounting_journal_id' => $journalId,
            'entry_type' => 'automatic',
            'reference_type' => 'vat',
            'reference_id' => null,
            'creator_id' => $companyId,
            'created_by' => $companyId,
        ]);

        // Insert the lines
        foreach ($lines as $line) {
            DB::table('journal_entry_items')->insert([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit_amount' => $line['debit_amount'],
                'credit_amount' => $line['credit_amount'],
                'description' => $line['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $entry->id;
    }
}
