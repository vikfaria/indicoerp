<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Services\JournalService;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollAccountingJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_journal_posts_full_salary_and_statutory_breakdown(): void
    {
        [$company, $entry] = $this->bootstrapPayrollEntry();

        $this->actingAs($company);

        $journal = app(JournalService::class)->createPayrollJournal($entry);

        $this->assertNotNull($journal);
        $this->assertSame('payroll', $journal->reference_type);
        $this->assertSame($entry->id, $journal->reference_id);
        $this->assertSame(33280.0, (float) $journal->total_debit);
        $this->assertSame(33280.0, (float) $journal->total_credit);

        $journal->load('items.account');

        $sumByAccount = function (string $code, string $column) use ($journal): float {
            return round(
                (float) $journal->items
                    ->filter(fn ($item): bool => (string) $item->account?->account_code === $code)
                    ->sum($column),
                2
            );
        };

        $this->assertSame(32000.0, $sumByAccount('5200', 'debit_amount'));
        $this->assertSame(1280.0, $sumByAccount('5210', 'debit_amount'));
        $this->assertSame(27840.0, $sumByAccount('1030', 'credit_amount'));
        $this->assertSame(1200.0, $sumByAccount('2200', 'credit_amount'));
        $this->assertSame(4240.0, $sumByAccount('2400', 'credit_amount'));
    }

    public function test_payroll_journal_posts_employee_loan_recovery_on_separate_advance_account(): void
    {
        [$company, $entry] = $this->bootstrapPayrollEntry(1000);

        $this->actingAs($company);

        $journal = app(JournalService::class)->createPayrollJournal($entry);

        $this->assertNotNull($journal);
        $this->assertSame('payroll', $journal->reference_type);
        $this->assertSame($entry->id, $journal->reference_id);
        $this->assertSame(33280.0, (float) $journal->total_debit);
        $this->assertSame(33280.0, (float) $journal->total_credit);

        $journal->load('items.account');

        $sumByAccount = function (string $code, string $column) use ($journal): float {
            return round(
                (float) $journal->items
                    ->filter(fn ($item): bool => (string) $item->account?->account_code === $code)
                    ->sum($column),
                2
            );
        };

        $this->assertSame(32000.0, $sumByAccount('5200', 'debit_amount'));
        $this->assertSame(1280.0, $sumByAccount('5210', 'debit_amount'));
        $this->assertSame(26840.0, $sumByAccount('1030', 'credit_amount'));
        $this->assertSame(1200.0, $sumByAccount('2200', 'credit_amount'));
        $this->assertSame(4240.0, $sumByAccount('2400', 'credit_amount'));
        $this->assertSame(1000.0, $sumByAccount('1320', 'credit_amount'));
    }

    public function test_payroll_journal_creation_is_idempotent_for_same_payslip(): void
    {
        [$company, $entry] = $this->bootstrapPayrollEntry();

        $this->actingAs($company);

        $service = app(JournalService::class);
        $first = $service->createPayrollJournal($entry);
        $second = $service->createPayrollJournal($entry);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where('reference_type', 'payroll')
                ->where('reference_id', $entry->id)
                ->count()
        );
    }

    private function bootstrapPayrollEntry(float $totalLoans = 0): array
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        AccountUtility::defaultdata($company->id);

        $employeeUser = User::factory()->create([
            'name' => 'Funcionario Contabilidade',
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-ACC-001',
            'user_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankGlAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        $bankAccount = BankAccount::query()->create([
            'account_number' => '000123456789',
            'account_name' => 'Conta Salarios',
            'bank_name' => 'Banco MZ',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'gl_account_id' => $bankGlAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Maio 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 32000,
            'total_deductions' => 4160 + $totalLoans,
            'total_net_pay' => 27840 - $totalLoans,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'bank_account_id' => $bankAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'gross_pay' => 32000,
            'net_pay' => 27840 - $totalLoans,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 4160 + $totalLoans,
            'total_loans' => $totalLoans,
            'working_days' => 22,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$company, $entry];
    }
}
