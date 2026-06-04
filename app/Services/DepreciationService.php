<?php

namespace App\Services;

use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

/**
 * Fixed asset depreciation service.
 * Supports straight-line depreciation with PGC-MZ journal entries.
 * PGC: D 5430 (Gastos de Depreciação), C 1610/1710 (Depreciações acumuladas)
 */
class DepreciationService
{
    /**
     * Run depreciation for all active assets of a company for a given period.
     */
    public function runMonthlyDepreciation(int $companyId, string $year, int $month): array
    {
        $assets = FixedAsset::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        $processed = 0;
        $skipped = 0;
        $totalAmount = 0;

        foreach ($assets as $asset) {
            // Skip if already depreciated for this period
            $exists = DepreciationEntry::where('fixed_asset_id', $asset->id)
                ->where('fiscal_year', $year)
                ->where('period_number', $month)
                ->exists();

            if ($exists || $asset->isFullyDepreciated()) {
                $skipped++;
                continue;
            }

            $amount = $this->depreciateAsset($asset, $year, $month);
            if ($amount > 0) {
                $processed++;
                $totalAmount += $amount;
            }
        }

        return [
            'period' => "{$year}/{$month}",
            'processed' => $processed,
            'skipped' => $skipped,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Depreciate a single asset for a period.
     */
    public function depreciateAsset(FixedAsset $asset, string $year, int $month): float
    {
        $monthlyAmount = $asset->getMonthlyDepreciation();

        if ($monthlyAmount <= 0) return 0;

        // Cap at remaining depreciable amount
        $remaining = $asset->acquisition_cost - $asset->residual_value - $asset->accumulated_depreciation;
        $amount = min($monthlyAmount, $remaining);

        if ($amount <= 0) return 0;

        return DB::transaction(function () use ($asset, $year, $month, $amount) {
            $depDate = sprintf('%s-%02d-01', $year, $month);
            $newAccumulated = $asset->accumulated_depreciation + $amount;
            $newNbv = $asset->acquisition_cost - $newAccumulated;

            // Record depreciation entry
            $depEntry = DepreciationEntry::create([
                'fixed_asset_id' => $asset->id,
                'depreciation_date' => $depDate,
                'fiscal_year' => $year,
                'period_number' => $month,
                'depreciation_amount' => $amount,
                'accumulated_after' => $newAccumulated,
                'net_book_value_after' => $newNbv,
                'created_by' => $asset->company_id,
            ]);

            // Create journal entry
            $journalEntry = $this->createDepreciationJournalEntry($asset, $amount, $depDate);
            if ($journalEntry) {
                $depEntry->update(['journal_entry_id' => $journalEntry->id]);
            }

            // Update asset
            $asset->update([
                'accumulated_depreciation' => $newAccumulated,
                'net_book_value' => $newNbv,
                'last_depreciation_date' => $depDate,
                'status' => $newNbv <= $asset->residual_value ? 'fully_depreciated' : 'active',
            ]);

            return $amount;
        });
    }

    /**
     * Get depreciation schedule (future projection) for an asset.
     */
    public function getSchedule(FixedAsset $asset): array
    {
        $monthlyAmount = $asset->getMonthlyDepreciation();
        $schedule = [];
        $accumulated = (float) $asset->accumulated_depreciation;
        $depreciableBase = $asset->acquisition_cost - $asset->residual_value;
        $remainingMonths = $asset->useful_life_months;

        $startDate = $asset->last_depreciation_date
            ? $asset->last_depreciation_date->copy()->addMonth()
            : $asset->acquisition_date->copy();

        $period = 0;
        while ($accumulated < $depreciableBase && $period < $remainingMonths) {
            $amount = min($monthlyAmount, $depreciableBase - $accumulated);
            $accumulated += $amount;

            $schedule[] = [
                'period' => $startDate->copy()->addMonths($period)->format('Y-m'),
                'depreciation' => round($amount, 2),
                'accumulated' => round($accumulated, 2),
                'net_book_value' => round($asset->acquisition_cost - $accumulated, 2),
            ];
            $period++;
        }

        return $schedule;
    }

    /**
     * Get asset register summary.
     */
    public function getAssetRegister(int $companyId): array
    {
        $assets = FixedAsset::where('company_id', $companyId)->orderBy('asset_code')->get();

        $summary = [
            'total_acquisition_cost' => $assets->sum('acquisition_cost'),
            'total_accumulated_depreciation' => $assets->sum('accumulated_depreciation'),
            'total_net_book_value' => $assets->sum('net_book_value'),
            'active_count' => $assets->where('status', 'active')->count(),
            'fully_depreciated_count' => $assets->where('status', 'fully_depreciated')->count(),
            'disposed_count' => $assets->where('status', 'disposed')->count(),
        ];

        $byCategory = $assets->groupBy('category')->map(function ($group, $category) {
            return [
                'category' => $category,
                'count' => $group->count(),
                'acquisition_cost' => $group->sum('acquisition_cost'),
                'accumulated_depreciation' => $group->sum('accumulated_depreciation'),
                'net_book_value' => $group->sum('net_book_value'),
            ];
        })->values();

        return [
            'assets' => $assets->toArray(),
            'summary' => $summary,
            'by_category' => $byCategory->toArray(),
        ];
    }

    /**
     * Dispose a fixed asset and post the gain/loss journal entry.
     *
     * Revaluation and impairment remain outside the automated scope and
     * require manual accounting validation.
     */
    public function disposeAsset(FixedAsset $asset, string $disposalDate, float $proceeds = 0): array
    {
        if ($asset->status === 'disposed') {
            throw new \RuntimeException('O activo já foi baixado.');
        }

        $companyId = (int) $asset->company_id;
        $cost = round((float) $asset->acquisition_cost, 2);
        $bookValue = round((float) $asset->net_book_value, 2);
        $accumulated = round((float) $asset->accumulated_depreciation, 2);
        $proceeds = round(max(0, $proceeds), 2);
        $gain = round(max(0, $proceeds - $bookValue), 2);
        $loss = round(max(0, $bookValue - $proceeds), 2);

        return DB::transaction(function () use ($asset, $disposalDate, $proceeds, $cost, $bookValue, $accumulated, $gain, $loss, $companyId): array {
            $journalDate = Carbon::parse($disposalDate)->toDateString();

            $assetAccount = $this->resolveFixedAssetAccount($asset);
            $accumulatedAccount = $this->resolveAccumulatedDepreciationAccount($asset);
            $gainAccount = $this->resolveAccountByCode($companyId, '4300');
            $lossAccount = $this->resolveAccountByCode($companyId, '5800');
            $proceedsAccount = $proceeds > 0 ? $this->resolveAccountByCode($companyId, '1010') : null;

            if (!$assetAccount || !$accumulatedAccount || !$gainAccount || !$lossAccount || ($proceeds > 0 && !$proceedsAccount)) {
                throw new \RuntimeException('Não foi possível localizar as contas PGC necessárias para a baixa do activo.');
            }

            $total = round($cost + $gain, 2);

            $entry = JournalEntry::create([
                'journal_date' => $journalDate,
                'entry_type' => 'automatic',
                'reference_type' => 'fixed_asset_disposal',
                'reference_id' => $asset->id,
                'description' => "Baixa do activo {$asset->asset_code}",
                'total_debit' => $total,
                'total_credit' => $total,
                'status' => 'posted',
                'creator_id' => auth()->id(),
                'created_by' => $companyId,
            ]);

            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accumulatedAccount->id,
                'description' => "Baixa da depreciação acumulada - {$asset->asset_code}",
                'debit_amount' => $accumulated,
                'credit_amount' => 0,
                'creator_id' => auth()->id(),
                'created_by' => $companyId,
            ]);

            if ($proceeds > 0 && $proceedsAccount) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $proceedsAccount->id,
                    'description' => "Proveitos da alienação - {$asset->asset_code}",
                    'debit_amount' => $proceeds,
                    'credit_amount' => 0,
                    'creator_id' => auth()->id(),
                    'created_by' => $companyId,
                ]);
            }

            if ($loss > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lossAccount->id,
                    'description' => "Perda na baixa - {$asset->asset_code}",
                    'debit_amount' => $loss,
                    'credit_amount' => 0,
                    'creator_id' => auth()->id(),
                    'created_by' => $companyId,
                ]);
            }

            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'description' => "Custo histórico do activo - {$asset->asset_code}",
                'debit_amount' => 0,
                'credit_amount' => $cost,
                'creator_id' => auth()->id(),
                'created_by' => $companyId,
            ]);

            if ($gain > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $gainAccount->id,
                    'description' => "Ganho na baixa - {$asset->asset_code}",
                    'debit_amount' => 0,
                    'credit_amount' => $gain,
                    'creator_id' => auth()->id(),
                    'created_by' => $companyId,
                ]);
            }

            $this->updateAccountBalances($entry);

            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $journalDate,
                'disposal_proceeds' => $proceeds,
                'net_book_value' => 0,
            ]);

            return [
                'journal_entry' => $entry,
                'book_value' => $bookValue,
                'gain_or_loss' => $gain > 0 ? $gain : ($loss > 0 ? -$loss : 0.0),
                'proceeds' => $proceeds,
            ];
        });
    }

    private function createDepreciationJournalEntry(FixedAsset $asset, float $amount, string $date): ?JournalEntry
    {
        $expenseAccount = $this->resolveFixedAssetExpenseAccount($asset);
        $accumAccount = $this->resolveAccumulatedDepreciationAccount($asset);

        if (!$expenseAccount || !$accumAccount) return null;

        $desc = "Depreciação - {$asset->name} ({$asset->asset_code})";
        $creatorId = auth()->id() ?? (int) $asset->company_id;

        $entry = JournalEntry::create([
            'journal_date' => $date,
            'entry_type' => 'automatic',
            'reference_type' => 'depreciation',
            'reference_id' => $asset->id,
            'description' => $desc,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'creator_id' => $creatorId,
            'created_by' => $asset->company_id,
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseAccount->id,
            'description' => $desc,
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'creator_id' => $creatorId,
            'created_by' => $asset->company_id,
        ]);
        JournalEntryItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $accumAccount->id,
            'description' => "Amortização acumulada - {$asset->asset_code}",
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'creator_id' => $creatorId,
            'created_by' => $asset->company_id,
        ]);

        $this->updateAccountBalances($entry);
        return $entry;
    }

    private function resolveAccountByCode(int $companyId, string $accountCode): ?ChartOfAccount
    {
        return ChartOfAccount::query()
            ->where('account_code', $accountCode)
            ->where('created_by', $companyId)
            ->where('is_movement_account', true)
            ->first();
    }

    private function resolveFixedAssetAccount(FixedAsset $asset): ?ChartOfAccount
    {
        $fallbackCode = $asset->category === 'investment_property' ? '1700' : '1600';
        return $this->resolveJournalAccount($asset, $asset->pgc_asset_account, $fallbackCode);
    }

    private function resolveAccumulatedDepreciationAccount(FixedAsset $asset): ?ChartOfAccount
    {
        $fallbackCode = $asset->category === 'investment_property' ? '1710' : '1610';
        return $this->resolveJournalAccount($asset, $asset->pgc_depreciation_account, $fallbackCode);
    }

    private function resolveFixedAssetExpenseAccount(FixedAsset $asset): ?ChartOfAccount
    {
        return $this->resolveJournalAccount($asset, $asset->pgc_expense_account, '5430');
    }

    private function resolveJournalAccount(FixedAsset $asset, ?string $preferredCode, string $fallbackCode): ?ChartOfAccount
    {
        $companyId = (int) $asset->company_id;

        foreach (array_values(array_filter(array_unique([$preferredCode, $fallbackCode]))) as $code) {
            $account = ChartOfAccount::query()
                ->where('account_code', $code)
                ->where('created_by', $companyId)
                ->where('is_movement_account', true)
                ->first();

            if ($account) {
                return $account;
            }
        }

        return null;
    }

    private function updateAccountBalances(JournalEntry $journalEntry): void
    {
        $journalEntry->loadMissing('items.account');

        foreach ($journalEntry->items as $item) {
            $account = $item->account;

            if (!$account) {
                continue;
            }

            $debitAmount = (float) $item->debit_amount;
            $creditAmount = (float) $item->credit_amount;

            if ($account->normal_balance === 'debit') {
                $account->current_balance += ($debitAmount - $creditAmount);
            } else {
                $account->current_balance += ($creditAmount - $debitAmount);
            }

            $account->save();
        }
    }
}
