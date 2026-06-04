<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\Hrm\Events\CreateLoan;
use Workdo\Hrm\Models\Employee;

class CreateLoanListener
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly BankTransactionsService $bankTransactionsService,
    ) {
    }

    public function handle(CreateLoan $event): void
    {
        $loan = $event->loan->loadMissing('employee');
        $companyId = (int) ($loan->created_by ?: creatorId());

        $employee = Employee::query()
            ->where('user_id', $loan->employee_id)
            ->where('created_by', $companyId)
            ->first();

        $bankAccount = BankAccount::query()
            ->with('glAccount')
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereNotNull('gl_account_id')
            ->orderBy('id')
            ->first();

        if (!$bankAccount?->glAccount) {
            throw new \Exception('A bank account with a GL account is required to disburse employee advances.');
        }

        $basicSalary = (float) ($employee?->basic_salary ?? 0);
        $disbursementAmount = $loan->type === 'percentage' && $basicSalary > 0
            ? round($basicSalary * (float) $loan->amount / 100, 2)
            : round((float) $loan->amount, 2);

        if ($disbursementAmount <= 0) {
            return;
        }

        $this->journalService->createEmployeeLoanJournal($loan, $bankAccount, $disbursementAmount);
        $this->bankTransactionsService->createEmployeeLoanDisbursement($loan, $bankAccount, $disbursementAmount);
    }
}
