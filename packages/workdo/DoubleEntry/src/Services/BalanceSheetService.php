<?php

namespace Workdo\DoubleEntry\Services;

use App\Models\AccountingPeriod;
use Workdo\DoubleEntry\Models\BalanceSheet;
use Workdo\DoubleEntry\Models\BalanceSheetItem;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\OpeningBalance;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    public function calculateAllAccountBalances($asOfDate)
    {
        return DB::select("
            SELECT
                coa.id,
                coa.account_code,
                coa.account_name,
                coa.normal_balance,
                coa.opening_balance,
                CASE
                    WHEN coa.normal_balance = 'debit' THEN
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0)
                    ELSE
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0)
                END as current_balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_items jei ON coa.id = jei.account_id
            LEFT JOIN journal_entries je ON jei.journal_entry_id = je.id
            WHERE coa.is_active = 1
              AND coa.created_by = ?
            GROUP BY coa.id, coa.account_code, coa.account_name, coa.normal_balance, coa.opening_balance
            ORDER BY coa.account_code ASC
        ", [$asOfDate, $asOfDate, $asOfDate, $asOfDate, creatorId()]);
    }

    public function getAccountSection($accountCode)
    {
        $code = (string) $accountCode;

        // --- PGC-MZ classes 0-9 (new format) ---
        $firstDigit = substr($code, 0, 1);

        // Class 1: Meios Financeiros Líquidos → Current Assets
        if ($firstDigit === '1' && strlen($code) <= 4 && !is_numeric(substr($code, 1, 3)) || 
            ($firstDigit === '1' && strlen($code) <= 3)) {
            return ['section_type' => 'assets', 'sub_section' => 'current_assets'];
        }

        // Class 2: Contas a Receber e a Pagar
        if ($firstDigit === '2' && strlen($code) <= 4) {
            $secondDigit = substr($code, 1, 1);
            // 21x = receivables (debit), 28x deferrals
            if (in_array($secondDigit, ['1', '7', '8'])) {
                return ['section_type' => 'assets', 'sub_section' => 'current_assets'];
            }
            // 22x-26x, 29x = payables (credit)
            return ['section_type' => 'liabilities', 'sub_section' => 'current_liabilities'];
        }

        // Class 3: Inventários → Current Assets
        if ($firstDigit === '3' && strlen($code) <= 3) {
            return ['section_type' => 'assets', 'sub_section' => 'current_assets'];
        }

        // Class 4: Investimentos → Non-Current Assets
        if ($firstDigit === '4' && strlen($code) <= 4) {
            return ['section_type' => 'assets', 'sub_section' => 'fixed_assets'];
        }

        // Class 5: Capital Próprio → Equity
        if ($firstDigit === '5' && strlen($code) <= 3) {
            return ['section_type' => 'equity', 'sub_section' => 'equity'];
        }

        // Class 6: Gastos → excluded from balance sheet (P&L)
        if ($firstDigit === '6' && strlen($code) <= 3) {
            return ['section_type' => 'other', 'sub_section' => 'expenses'];
        }

        // Class 7: Rendimentos → excluded from balance sheet (P&L)
        if ($firstDigit === '7' && strlen($code) <= 3) {
            return ['section_type' => 'other', 'sub_section' => 'income'];
        }

        // Class 8: Resultados → excluded from balance sheet (P&L)
        if ($firstDigit === '8' && strlen($code) <= 3) {
            return ['section_type' => 'other', 'sub_section' => 'results'];
        }

        // Class 0: Contas de Ordem → off-balance
        if ($firstDigit === '0') {
            return ['section_type' => 'other', 'sub_section' => 'off_balance'];
        }

        // --- Legacy 1000-5999 format (backward compatibility) ---
        $numericCode = intval($code);

        if ($numericCode >= 1000 && $numericCode <= 1399) {
            return ['section_type' => 'assets', 'sub_section' => 'current_assets'];
        } elseif ($numericCode >= 1400 && $numericCode <= 1599) {
            return ['section_type' => 'assets', 'sub_section' => 'other_assets'];
        } elseif ($numericCode >= 1600 && $numericCode <= 1999) {
            return ['section_type' => 'assets', 'sub_section' => 'fixed_assets'];
        } elseif ($numericCode >= 2000 && $numericCode <= 2499) {
            return ['section_type' => 'liabilities', 'sub_section' => 'current_liabilities'];
        } elseif ($numericCode >= 2500 && $numericCode <= 2999) {
            return ['section_type' => 'liabilities', 'sub_section' => 'long_term_liabilities'];
        } elseif ($numericCode >= 3000 && $numericCode <= 3999) {
            return ['section_type' => 'equity', 'sub_section' => 'equity'];
        } elseif ($numericCode >= 4000 && $numericCode <= 5999) {
            return ['section_type' => 'other', 'sub_section' => 'other'];
        }

        return ['section_type' => 'other', 'sub_section' => 'other'];
    }

    public function generateBalanceSheet($date, $financialYear)
    {
        // 1. Create main balance sheet record
        $balanceSheet = BalanceSheet::create([
            'balance_sheet_date' => $date,
            'financial_year' => $financialYear,
            'status' => 'draft',
            'creator_id' => Auth::id(),
            'created_by' => creatorId()
        ]);

        // 2. Get all accounts with balances as of the specified date
        $accounts = $this->calculateAllAccountBalances($date);

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        // 3. Calculate net income from revenue/expense accounts as of date
        $netIncome = $this->calculateNetIncome($date);

        // 4. Get Retained Earnings account
        $retainedEarningsAccount = $this->getOrCreateRetainedEarningsAccount();
        $retainedEarningsId = $retainedEarningsAccount ? $retainedEarningsAccount->id : null;

        // 5. Create balance sheet items for each account
        foreach($accounts as $account) {
            if (abs($account->current_balance) > 0.01) {
                $sectionInfo = $this->getAccountSection($account->account_code);

                // Skip revenue/expense accounts and Retained Earnings (will add separately)
                if ($sectionInfo['section_type'] !== 'other' && $account->id != $retainedEarningsId) {
                    $amount = $account->current_balance;

                    BalanceSheetItem::create([
                        'balance_sheet_id' => $balanceSheet->id,
                        'account_id' => $account->id,
                        'section_type' => $sectionInfo['section_type'],
                        'sub_section' => $sectionInfo['sub_section'],
                        'amount' => $amount,
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId()
                    ]);

                    if ($sectionInfo['section_type'] == 'assets') {
                        $totalAssets += $amount;
                    } elseif ($sectionInfo['section_type'] == 'liabilities') {
                        $totalLiabilities += $amount;
                    } elseif ($sectionInfo['section_type'] == 'equity') {
                        $totalEquity += $amount;
                    }
                }
            }
        }

        // 6. Add Retained Earnings with net income
        if ($retainedEarningsAccount) {
            // Get calculated balance from accounts array
            $retainedEarningsCalculatedBalance = 0;
            foreach($accounts as $acc) {
                if ($acc->id == $retainedEarningsAccount->id) {
                    $retainedEarningsCalculatedBalance = $acc->current_balance;
                    break;
                }
            }

            $retainedEarningsBalance = $retainedEarningsCalculatedBalance + $netIncome;

            if (abs($retainedEarningsBalance) > 0.01) {
                BalanceSheetItem::create([
                    'balance_sheet_id' => $balanceSheet->id,
                    'account_id' => $retainedEarningsAccount->id,
                    'section_type' => 'equity',
                    'sub_section' => 'equity',
                    'amount' => $retainedEarningsBalance,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);

                $totalEquity += $retainedEarningsBalance;
            }
        }

        // 7. Update balance sheet totals
        $balanceSheet->update([
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'is_balanced' => (abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01)
        ]);

        return $balanceSheet->id;
    }

    public function calculateNetIncome($asOfDate)
    {
        // Support both PGC-MZ (classes 6/7) and legacy (4000-5999)
        $revenueExpenseAccounts = DB::select("
            SELECT
                coa.account_code,
                coa.pgc_class,
                CASE
                    WHEN coa.normal_balance = 'debit' THEN
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0)
                    ELSE
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0)
                END as current_balance
            FROM chart_of_accounts coa
            LEFT JOIN opening_balances ob ON coa.id = ob.account_id
                AND ob.created_by = coa.created_by
                AND ob.id = (SELECT MAX(id) FROM opening_balances WHERE account_id = coa.id AND created_by = coa.created_by)
            LEFT JOIN journal_entry_items jei ON coa.id = jei.account_id
            LEFT JOIN journal_entries je ON jei.journal_entry_id = je.id
            WHERE (
                (coa.pgc_class IN (6, 7))
                OR (coa.account_code >= '4000' AND coa.account_code <= '5999')
            )
              AND coa.is_active = 1
              AND coa.created_by = ?
            GROUP BY coa.id, coa.account_code, coa.account_name, coa.normal_balance, coa.opening_balance, coa.pgc_class, ob.effective_date
        ", [$asOfDate, $asOfDate, $asOfDate, $asOfDate, creatorId()]);

        $netIncome = 0;
        foreach($revenueExpenseAccounts as $account) {
            $pgcClass = $account->pgc_class ?? null;

            // PGC-MZ: Class 7 = income, Class 6 = expenses
            if ($pgcClass === 7) {
                $netIncome += $account->current_balance;
            } elseif ($pgcClass === 6) {
                $netIncome -= $account->current_balance;
            }
            // Legacy: 4xxx = revenue, 5xxx = expenses
            elseif ($account->account_code >= '4000' && $account->account_code <= '4999') {
                $netIncome += $account->current_balance;
            } elseif ($account->account_code >= '5000' && $account->account_code <= '5999') {
                $netIncome -= $account->current_balance;
            }
        }

        return $netIncome;
    }

    public function getOrCreateRetainedEarningsAccount()
    {
        // PGC-MZ: Resultados transitados = code 56
        $account = ChartOfAccount::where('account_code', '56')
            ->where('created_by', creatorId())
            ->first();

        if ($account) {
            return $account;
        }

        // Legacy: Retained Earnings = code 3200
        return ChartOfAccount::where('account_code', '3200')
            ->where('created_by', creatorId())
            ->first();
    }

    public function validateBalanceSheet($balanceSheetId)
    {
        $balanceSheet = BalanceSheet::find($balanceSheetId);
        if (!$balanceSheet) {
            return false;
        }

        $isBalanced = abs($balanceSheet->total_assets - ($balanceSheet->total_liabilities + $balanceSheet->total_equity)) < 0.01;

        $balanceSheet->update(['is_balanced' => $isBalanced]);

        return $isBalanced;
    }

    public function performYearEndClose($financialYear, $closingDate)
    {
        // Check if year-end close already performed
        $nextYear = (string)((int)$financialYear + 1);
        $existingOpeningBalances = OpeningBalance::where('financial_year', $nextYear)
            ->where('created_by', creatorId())
            ->exists();

        if ($existingOpeningBalances) {
            throw new \Exception("Year-end close for {$financialYear} has already been performed.");
        }

        DB::transaction(function () use ($financialYear, $closingDate) {
            // 1. Create closing journal entries for revenue/expense accounts
            $this->createClosingJournalEntries($financialYear, $closingDate);

            // 2. Get all account balances (after closing entries) - use closing date
            $accounts = $this->calculateAllAccountBalances($closingDate);

            // 3. Create opening balances for next year
            $nextYear = (string)((int)$financialYear + 1);
            $nextYearStartDate = date('Y-m-d', strtotime($closingDate . ' +1 day'));

            foreach($accounts as $account) {
                $sectionInfo = $this->getAccountSection($account->account_code);

                // Only create opening balances for balance sheet accounts
                if ($sectionInfo['section_type'] !== 'other') {
                    $openingBalance = $account->current_balance;

                    if (abs($openingBalance) > 0.01) {
                        OpeningBalance::updateOrCreate(
                            [
                                'account_id' => $account->id,
                                'financial_year' => $nextYear,
                                'created_by' => creatorId()
                            ],
                            [
                                'opening_balance' => $openingBalance,
                                'balance_type' => ($openingBalance >= 0 && $account->normal_balance === 'debit') || ($openingBalance < 0 && $account->normal_balance === 'credit') ? 'debit' : 'credit',
                                'effective_date' => $nextYearStartDate,
                                'creator_id' => Auth::id()
                            ]
                        );
                    }
                }
            }

            // 4. Update chart_of_accounts opening_balance
            foreach($accounts as $account) {
                ChartOfAccount::where('id', $account->id)
                    ->update(['opening_balance' => $account->current_balance]);
            }

            AccountingPeriod::query()
                ->where('company_id', creatorId())
                ->where('fiscal_year', $financialYear)
                ->update([
                    'status' => 'closed',
                    'closed_by' => Auth::id(),
                    'closed_at' => now(),
                ]);
        });
    }

    private function createClosingJournalEntries($financialYear, $closingDate)
    {
        // Get retained earnings account — PGC-MZ code 56, fallback to legacy 3200
        $retainedEarningsAccount = $this->getOrCreateRetainedEarningsAccount();
        if (!$retainedEarningsAccount) {
            throw new \Exception('Conta de Resultados Transitados (56 ou 3200) não encontrada');
        }

        // Get revenue and expense accounts as of closing date
        $revenueExpenseAccounts = $this->calculateNetIncomeAccounts($closingDate);

        $totalRevenue = 0;
        $totalExpense = 0;
        $journalItems = [];

        foreach($revenueExpenseAccounts as $account) {
            if (abs($account->current_balance) > 0.01) {
                $pgcClass = $account->pgc_class ?? null;
                $accountCode = intval($account->account_code);

                $isIncome = ($pgcClass === 7) || ($pgcClass === null && $accountCode >= 4000 && $accountCode <= 4999);
                $isExpense = ($pgcClass === 6) || ($pgcClass === null && $accountCode >= 5000 && $accountCode <= 5999);

                if ($isIncome) {
                    // Revenue/income accounts - debit to close
                    $totalRevenue += $account->current_balance;
                    $journalItems[] = [
                        'account_id' => $account->id,
                        'description' => 'Encerramento de conta de rendimento',
                        'debit_amount' => $account->current_balance,
                        'credit_amount' => 0
                    ];
                } elseif ($isExpense) {
                    // Expense accounts - credit to close
                    $totalExpense += $account->current_balance;
                    $journalItems[] = [
                        'account_id' => $account->id,
                        'description' => 'Encerramento de conta de gasto',
                        'debit_amount' => 0,
                        'credit_amount' => $account->current_balance
                    ];
                }
            }
        }

        if (!empty($journalItems)) {
            $netIncome = $totalRevenue - $totalExpense;

            // Create closing journal entry
            $totalDebits = $totalRevenue + ($netIncome < 0 ? abs($netIncome) : 0);
            $totalCredits = $totalExpense + ($netIncome >= 0 ? $netIncome : 0);

            $journalEntry = JournalEntry::create([
                'journal_date' => $closingDate,
                'entry_type' => 'automatic',
                'reference_type' => 'year_end_close',
                'reference_id' => null,
                'description' => 'Year-end closing entries for ' . $financialYear,
                'total_debit' => $totalDebits,
                'total_credit' => $totalCredits,
                'status' => 'posted',
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            // Add all revenue/expense closing entries
            foreach($journalItems as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $item['account_id'],
                    'description' => $item['description'],
                    'debit_amount' => $item['debit_amount'],
                    'credit_amount' => $item['credit_amount'],
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId()
                ]);
            }

            // Transfer net income to retained earnings
            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $retainedEarningsAccount->id,
                'description' => 'Transfer net income to retained earnings',
                'debit_amount' => $netIncome >= 0 ? 0 : abs($netIncome),
                'credit_amount' => $netIncome >= 0 ? $netIncome : 0,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            // Update account balances after creating closing entries
            $this->updateClosingAccountBalances($journalEntry);
        }
    }

    private function calculateNetIncomeAccounts($asOfDate)
    {
        return DB::select("
            SELECT
                coa.id,
                coa.account_code,
                coa.account_name,
                coa.normal_balance,
                coa.current_balance,
                coa.pgc_class
            FROM chart_of_accounts coa
            WHERE (
                (coa.pgc_class IN (6, 7))
                OR (coa.account_code >= '4000' AND coa.account_code <= '5999')
            )
              AND coa.is_active = 1
              AND coa.created_by = ?
            ORDER BY coa.account_code ASC
        ", [creatorId()]);
    }

    public function finalizeBalanceSheet($balanceSheetId)
    {
        $balanceSheet = BalanceSheet::find($balanceSheetId);
        if (!$balanceSheet) {
            throw new \Exception("Balance sheet not found");
        }

        if (!$this->validateBalanceSheet($balanceSheetId)) {
            throw new \Exception("Balance sheet is not balanced. Cannot finalize.");
        }

        $balanceSheet->update(['status' => 'finalized']);

        return $balanceSheet;
    }

    private function updateClosingAccountBalances($journalEntry)
    {
        $journalEntry->load('items.account');

        foreach($journalEntry->items as $item) {
            $account = $item->account;
            $debitAmount = $item->debit_amount;
            $creditAmount = $item->credit_amount;

            if ($account->normal_balance === 'debit') {
                $account->current_balance += ($debitAmount - $creditAmount);
            } else {
                $account->current_balance += ($creditAmount - $debitAmount);
            }

            $account->save();
        }
    }
}
