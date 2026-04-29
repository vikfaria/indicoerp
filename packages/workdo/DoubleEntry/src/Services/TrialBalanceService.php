<?php

namespace Workdo\DoubleEntry\Services;

use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function generateTrialBalance($fromDate, $toDate)
    {
        $accounts = DB::select("
            SELECT
                coa.id,
                coa.account_code,
                coa.account_name,
                coa.normal_balance,
                CASE
                    WHEN coa.normal_balance = 'debit' THEN
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date >= ? AND je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date >= ? AND je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0)
                    ELSE
                        COALESCE(coa.opening_balance, 0) +
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date >= ? AND je.journal_date <= ? AND je.status = 'posted' THEN jei.credit_amount ELSE 0 END), 0) -
                        COALESCE(SUM(CASE WHEN (ob.effective_date IS NULL OR je.journal_date >= ob.effective_date) AND je.journal_date >= ? AND je.journal_date <= ? AND je.status = 'posted' THEN jei.debit_amount ELSE 0 END), 0)
                END as balance
            FROM chart_of_accounts coa
            LEFT JOIN opening_balances ob ON coa.id = ob.account_id
                AND ob.created_by = coa.created_by
                AND ob.id = (SELECT MAX(id) FROM opening_balances WHERE account_id = coa.id AND created_by = coa.created_by)
            LEFT JOIN journal_entry_items jei ON coa.id = jei.account_id
            LEFT JOIN journal_entries je ON jei.journal_entry_id = je.id
            WHERE coa.is_active = 1
              AND coa.created_by = ?
            GROUP BY coa.id, coa.account_code, coa.account_name, coa.normal_balance, coa.opening_balance, ob.effective_date
            ORDER BY coa.account_code ASC
        ", [$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate, creatorId()]);

        $totalDebit = 0;
        $totalCredit = 0;
        $accountsList = [];

        foreach($accounts as $account) {
            $balance = (float)$account->balance;

            if (abs($balance) > 0.01) {
                $debit = 0;
                $credit = 0;

                if ($balance > 0) {
                    if ($account->normal_balance === 'debit') {
                        $debit = $balance;
                        $totalDebit += $debit;
                    } else {
                        $credit = $balance;
                        $totalCredit += $credit;
                    }
                } else {
                    // Negative balance goes to opposite side
                    if ($account->normal_balance === 'debit') {
                        $credit = abs($balance);
                        $totalCredit += $credit;
                    } else {
                        $debit = abs($balance);
                        $totalDebit += $debit;
                    }
                }

                $accountsList[] = [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit
                ];
            }
        }

        return [
            'accounts' => $accountsList,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ];
    }
}
