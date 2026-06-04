<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AccountingPeriod;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\Account\Models\OpeningBalance;
use App\Models\MonthlyClosingChecklist;

class AccountingClosingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_monthly_closing_can_be_reopened_and_blocks_journals_while_closed(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account']);

        AccountUtility::defaultdata($company->id);
        AccountingPeriod::generateForYear($company->id, '2026');

        $this->actingAs($company)->post(route('sce.monthly-closing.start'), [
            'year' => '2026',
            'month' => 1,
        ])->assertSessionHasNoErrors();

        $period = AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->where('fiscal_year', '2026')
            ->where('period_number', 1)
            ->firstOrFail();

        $this->assertSame('closing', $period->status);

        $checklists = MonthlyClosingChecklist::query()
            ->where('accounting_period_id', $period->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(11, $checklists);

        foreach ($checklists as $check) {
            $this->actingAs($company)->post(route('sce.monthly-closing.complete-check', $check->id))
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($company)->post(route('sce.monthly-closing.finalize'), [
            'year' => '2026',
            'month' => 1,
        ])->assertSessionHasNoErrors();

        $period->refresh();
        $this->assertSame('closed', $period->status);
        $this->assertNotNull($period->closed_at);
        $this->assertIsArray($period->close_checklist);
        $this->assertSame(11, data_get($period->close_checklist, 'summary.total'));
        $this->assertSame(11, count((array) data_get($period->close_checklist, 'items')));

        $auditEvents = AuditTrail::query()
            ->where('auditable_type', AccountingPeriod::class)
            ->where('auditable_id', $period->id)
            ->orderBy('id')
            ->get();

        $this->assertTrue($auditEvents->contains(fn ($audit) => data_get($audit->new_values, 'status') === 'closed'));

        try {
            JournalEntry::query()->create([
                'journal_number' => 'MJ-CLOSED-001',
                'journal_date' => '2026-01-15',
                'entry_type' => 'manual',
                'reference_type' => 'manual',
                'reference_id' => null,
                'description' => 'Lançamento bloqueado por fecho mensal.',
                'total_debit' => 1000,
                'total_credit' => 1000,
                'status' => 'posted',
                'accounting_period_id' => $period->id,
                'fiscal_year' => '2026',
                'period_number' => 1,
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);

            $this->fail('Expected the closed accounting period to block journal entry creation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'The accounting period is closed',
                implode(' ', Arr::flatten($exception->errors()))
            );
        }

        $this->actingAs($company)->post(route('sce.monthly-closing.reopen'), [
            'year' => '2026',
            'month' => 1,
            'reopen_reason' => 'Ajuste contabilístico pós-fecho.',
        ])->assertSessionHasNoErrors();

        $period->refresh();
        $this->assertSame('open', $period->status);
        $this->assertSame('Ajuste contabilístico pós-fecho.', (string) $period->reopen_reason);
        $this->assertNotNull($period->reopened_at);

        $checklists = MonthlyClosingChecklist::query()
            ->where('accounting_period_id', $period->id)
            ->orderBy('id')
            ->get();

        $this->assertTrue($checklists->every(fn (MonthlyClosingChecklist $check): bool => $check->status === 'pending'));

        $cashAccount = $this->chartAccount($company->id, '1000');
        $capitalAccount = $this->chartAccount($company->id, '3100');

        $journal = JournalEntry::query()->create([
            'journal_number' => 'MJ-OPEN-001',
            'journal_date' => '2026-01-15',
            'entry_type' => 'manual',
            'reference_type' => 'manual',
            'reference_id' => null,
            'description' => 'Lançamento após reabertura.',
            'total_debit' => 1000,
            'total_credit' => 1000,
            'status' => 'posted',
            'accounting_period_id' => $period->id,
            'fiscal_year' => '2026',
            'period_number' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'description' => 'Débito caixa',
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $capitalAccount->id,
            'description' => 'Crédito capital',
            'debit_amount' => 0,
            'credit_amount' => 1000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journal->id,
            'status' => 'posted',
        ]);

        $this->assertTrue(
            AuditTrail::query()
                ->where('auditable_type', AccountingPeriod::class)
                ->where('auditable_id', $period->id)
                ->get()
                ->contains(fn ($audit) => (string) data_get($audit->new_values, 'reopen_reason') === 'Ajuste contabilístico pós-fecho.')
        );
    }

    public function test_year_end_close_closes_entire_fiscal_year_and_blocks_duplicate_close(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['year-end-close']);

        AccountUtility::defaultdata($company->id);
        AccountingPeriod::generateForYear($company->id, '2026');

        $this->postJournal($company, '2026-01-05', 'Capital contribution', [
            ['account_code' => '1000', 'debit' => 10000.00, 'credit' => 0.00, 'description' => 'Cash contribution'],
            ['account_code' => '3100', 'debit' => 0.00, 'credit' => 10000.00, 'description' => 'Share capital'],
        ]);

        $this->postJournal($company, '2026-06-10', 'Credit sale', [
            ['account_code' => '1100', 'debit' => 5000.00, 'credit' => 0.00, 'description' => 'Receivable'],
            ['account_code' => '4100', 'debit' => 0.00, 'credit' => 5000.00, 'description' => 'Revenue'],
        ]);

        $this->postJournal($company, '2026-07-10', 'Expense payment', [
            ['account_code' => '5200', 'debit' => 2000.00, 'credit' => 0.00, 'description' => 'Expense'],
            ['account_code' => '1000', 'debit' => 0.00, 'credit' => 2000.00, 'description' => 'Cash out'],
        ]);

        $this->actingAs($company)->post(route('double-entry.balance-sheets.year-end-close'), [
            'financial_year' => '2026',
            'closing_date' => '2026-12-31',
        ])->assertSessionHas('success', 'Year-end closing completed successfully.');

        $periods = AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->where('fiscal_year', '2026')
            ->get();

        $this->assertNotEmpty($periods);
        $this->assertTrue($periods->every(fn (AccountingPeriod $period): bool => $period->status === 'closed'));

        $cashOpeningBalance = OpeningBalance::query()
            ->where('created_by', $company->id)
            ->where('financial_year', '2027')
            ->where('account_id', $this->chartAccount($company->id, '1000')->id)
            ->first();

        $this->assertNotNull($cashOpeningBalance);
        $this->assertSame(8000.00, (float) $cashOpeningBalance->opening_balance);

        $retainedEarningsAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->whereIn('account_code', ['56', '3200'])
            ->firstOrFail();

        $this->actingAs($company)->post(route('double-entry.balance-sheets.year-end-close'), [
            'financial_year' => '2026',
            'closing_date' => '2026-12-31',
        ])->assertSessionHas('error');

        $this->assertTrue(
            AuditTrail::query()
                ->where('auditable_type', OpeningBalance::class)
                ->where('event', 'created')
                ->exists()
        );
    }

    private function postJournal(User $company, string $date, string $description, array $lines): JournalEntry
    {
        $totalDebit = round(array_sum(array_map(static fn (array $line): float => (float) ($line['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(static fn (array $line): float => (float) ($line['credit'] ?? 0), $lines)), 2);

        $journal = JournalEntry::query()->create([
            'journal_number' => 'MJ-' . str_replace('-', '', $date) . '-' . substr(md5($description), 0, 6),
            'journal_date' => $date,
            'entry_type' => 'manual',
            'reference_type' => 'manual',
            'reference_id' => null,
            'description' => $description,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        foreach ($lines as $line) {
            $account = $this->chartAccount($company->id, (string) $line['account_code']);

            JournalEntryItem::query()->create([
                'journal_entry_id' => $journal->id,
                'account_id' => $account->id,
                'description' => $line['description'] ?? $description,
                'debit_amount' => round((float) ($line['debit'] ?? 0), 2),
                'credit_amount' => round((float) ($line['credit'] ?? 0), 2),
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        return $journal;
    }

    private function chartAccount(int $companyId, string $code): ChartOfAccount
    {
        return ChartOfAccount::query()
            ->where('created_by', $companyId)
            ->where('account_code', $code)
            ->firstOrFail();
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
