<?php

namespace App\Services;

use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

/**
 * Fixed asset depreciation service.
 * Supports straight-line depreciation with PGC-MZ journal entries.
 * PGC: D 64 (Depreciações), C 48 (Amortizações acumuladas)
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

    private function createDepreciationJournalEntry(FixedAsset $asset, float $amount, string $date): ?JournalEntry
    {
        $expenseCode = $asset->pgc_expense_account ?? '64';
        $accumCode = $asset->pgc_depreciation_account ?? '48';

        $expenseAccount = ChartOfAccount::where('account_code', $expenseCode)->where('created_by', $asset->company_id)->first();
        $accumAccount = ChartOfAccount::where('account_code', $accumCode)->where('created_by', $asset->company_id)->first();

        if (!$expenseAccount || !$accumAccount) return null;

        $desc = "Depreciação - {$asset->name} ({$asset->asset_code})";

        $entry = JournalEntry::create([
            'journal_date' => $date, 'entry_type' => 'automatic', 'reference_type' => 'depreciation',
            'reference_id' => $asset->id, 'description' => $desc,
            'total_debit' => $amount, 'total_credit' => $amount,
            'status' => 'posted', 'created_by' => $asset->company_id,
        ]);

        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $expenseAccount->id, 'description' => $desc, 'debit_amount' => $amount, 'credit_amount' => 0, 'created_by' => $asset->company_id]);
        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $accumAccount->id, 'description' => "Amortização acumulada - {$asset->asset_code}", 'debit_amount' => 0, 'credit_amount' => $amount, 'created_by' => $asset->company_id]);

        return $entry;
    }
}
