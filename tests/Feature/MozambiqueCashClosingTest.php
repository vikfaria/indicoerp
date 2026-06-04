<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\MozCashClosing;
use Workdo\Account\Services\ReportService;

class MozambiqueCashClosingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_cash_closing_endpoint_records_snapshot_and_allows_reopen(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports', 'view-tax-summary']);
        $this->actingAs($company);

        $cashAccount = $this->makeCashAccount($company);
        $closingDate = now()->toDateString();

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => $closingDate,
            'transaction_type' => 'credit',
            'reference_number' => 'CASH-001',
            'description' => 'Cash receipt',
            'amount' => 500,
            'running_balance' => 1500,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => $closingDate,
            'transaction_type' => 'debit',
            'reference_number' => 'CASH-002',
            'description' => 'Cash expense',
            'amount' => 200,
            'running_balance' => 1300,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $closingDate,
            'counted_balance_mzn' => 1290,
            'close_reason' => 'Fecho diário do caixa.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Cash closing completed successfully.');
        $response->assertJsonPath('data.latest_closed_until', $closingDate);
        $response->assertJsonCount(1, 'data.closings');
        $response->assertJsonPath('data.closings.0.bank_account_id', $cashAccount->id);
        $response->assertJsonPath('data.closings.0.status', 'closed');
        $response->assertJsonPath('data.closings.0.opening_balance_mzn', 1000);
        $response->assertJsonPath('data.closings.0.cash_in_mzn', 500);
        $response->assertJsonPath('data.closings.0.cash_out_mzn', 200);
        $response->assertJsonPath('data.closings.0.expected_balance_mzn', 1300);
        $response->assertJsonPath('data.closings.0.counted_balance_mzn', 1290);
        $response->assertJsonPath('data.closings.0.variance_mzn', -10);

        $closing = MozCashClosing::query()->firstOrFail();
        $this->assertSame('closed', $closing->status);
        $this->assertSame(1000.0, (float) $closing->opening_balance_mzn);
        $this->assertSame(500.0, (float) $closing->cash_in_mzn);
        $this->assertSame(200.0, (float) $closing->cash_out_mzn);
        $this->assertSame(1300.0, (float) $closing->expected_balance_mzn);
        $this->assertSame(1290.0, (float) $closing->counted_balance_mzn);
        $this->assertSame(-10.0, (float) $closing->variance_mzn);
        $this->assertSame('Fecho diário do caixa.', $closing->close_reason);

        $reopenResponse = $this->actingAs($company)->post(route('account.reports.cash-closings.reopen', $closing), [
            'reopen_reason' => 'Correção operacional.',
        ]);

        $reopenResponse->assertOk();
        $reopenResponse->assertJsonPath('message', 'Cash closing reopened successfully.');

        $closing->refresh();
        $this->assertSame('reopened', $closing->status);
        $this->assertSame('Correção operacional.', $closing->reopen_reason);
        $this->assertNotNull($closing->reopened_at);
        $this->assertSame($company->id, (int) $closing->reopened_by);
    }

    public function test_cash_closing_endpoint_rejects_future_dates(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports', 'view-tax-summary']);
        $this->actingAs($company);

        $cashAccount = $this->makeCashAccount($company);
        $futureDate = now()->addDay()->toDateString();

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'credit',
            'reference_number' => 'CASH-FUT-001',
            'description' => 'Cash receipt',
            'amount' => 100,
            'running_balance' => 1100,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $futureDate,
            'counted_balance_mzn' => 1100,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['closing_date']);
    }

    public function test_cash_closing_endpoint_rejects_duplicate_close_for_same_account_and_date(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports', 'view-tax-summary']);
        $this->actingAs($company);

        $cashAccount = $this->makeCashAccount($company);
        $closingDate = now()->toDateString();

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => $closingDate,
            'transaction_type' => 'credit',
            'reference_number' => 'CASH-DUP-001',
            'description' => 'Cash receipt',
            'amount' => 100,
            'running_balance' => 1100,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $closingDate,
            'counted_balance_mzn' => 1100,
        ])->assertOk();

        $duplicateResponse = $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $closingDate,
            'counted_balance_mzn' => 1100,
        ]);

        $duplicateResponse->assertStatus(422);
        $duplicateResponse->assertJsonPath('message', 'There is already a closed cash closing for this account and date.');
    }

    public function test_cash_closing_endpoint_rejects_non_cash_account(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        $bankAccount = $this->makeBankAccount($company, 'current');

        $response = $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $bankAccount->id,
            'closing_date' => now()->toDateString(),
            'counted_balance_mzn' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Selected account is not configured as a cashbox or petty cash account.');
    }

    public function test_financial_dashboard_flags_missing_cash_closing_until_day_is_closed(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports', 'view-tax-summary']);
        $this->actingAs($company);

        $cashAccount = $this->makeCashAccount($company);
        $closingDate = now()->toDateString();

        $dashboardBefore = app(ReportService::class)->getMozambiqueFinancialComplianceDashboard([
            'from_date' => $closingDate,
            'to_date' => $closingDate,
            'due_soon_days' => 7,
        ]);

        $this->assertTrue(
            collect((array) data_get($dashboardBefore, 'active_indicators', []))
                ->pluck('code')
                ->contains('cash_closing_missing_today')
        );
        $this->assertSame(1, (int) data_get($dashboardBefore, 'summary.active_indicators', 0));
        $this->assertCount(1, (array) data_get($dashboardBefore, 'details.cash_closings.cash_accounts', []));

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => $closingDate,
            'transaction_type' => 'credit',
            'reference_number' => 'CASH-010',
            'description' => 'Cash receipt',
            'amount' => 500,
            'running_balance' => 1500,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $closingDate,
            'counted_balance_mzn' => 1500,
        ])->assertOk();

        $dashboardAfter = app(ReportService::class)->getMozambiqueFinancialComplianceDashboard([
            'from_date' => $closingDate,
            'to_date' => $closingDate,
            'due_soon_days' => 7,
        ]);

        $this->assertFalse(
            collect((array) data_get($dashboardAfter, 'active_indicators', []))
                ->pluck('code')
                ->contains('cash_closing_missing_today')
        );
        $this->assertSame(0, (int) data_get($dashboardAfter, 'summary.active_indicators', 0));
    }

    public function test_cash_closing_export_returns_csv_with_closings(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        $cashAccount = $this->makeCashAccount($company);
        $closingDate = now()->toDateString();

        BankTransaction::query()->create([
            'bank_account_id' => $cashAccount->id,
            'transaction_date' => $closingDate,
            'transaction_type' => 'credit',
            'reference_number' => 'CASH-020',
            'description' => 'Cash receipt',
            'amount' => 250,
            'running_balance' => 1250,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('account.reports.cash-closings.close'), [
            'bank_account_id' => $cashAccount->id,
            'closing_date' => $closingDate,
            'counted_balance_mzn' => 1250,
        ])->assertOk();

        $response = $this->actingAs($company)->get(route('account.reports.cash-closings.export'));

        $response->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringStartsWith(
            'attachment; filename="mozambique-cash-closings-',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('"summary","closings_count","1"', $response->getContent());
        $this->assertStringContainsString('"cash_accounts","' . $cashAccount->id . '"', $response->getContent());
        $this->assertStringContainsString('"closings","' . $closingDate . '"', $response->getContent());
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

    private function makeBankAccount(User $company, string $accountType = 'current'): BankAccount
    {
        return BankAccount::query()->create([
            'account_number' => $accountType === 'cashbox' ? 'CASH-001' : 'BANK-001',
            'account_name' => $accountType === 'cashbox' ? 'Caixa Principal' : 'Conta Bancária',
            'bank_name' => $accountType === 'cashbox' ? 'Caixa' : 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => $accountType,
            'opening_balance' => 1000,
            'current_balance' => 1300,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeCashAccount(User $company): BankAccount
    {
        return $this->makeBankAccount($company, 'cashbox');
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
