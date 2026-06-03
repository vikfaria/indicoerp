<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\ReportService;

class AccountReportsPermissionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function test_reports_index_is_available_with_specific_report_permission(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-invoice-aging']);

        $response = $this->actingAs($company)->get(route('account.reports.index'), $this->inertiaHeaders());

        $response->assertOk();
        $response->assertHeader('X-Inertia', 'true');
        $response->assertJsonPath('component', 'Account/Reports/Index');
    }

    public function test_reports_index_denies_access_without_report_permissions(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route('account.reports.index'));

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Permission denied');
    }

    public function test_manage_account_reports_unlocks_sensitive_report_views_and_prints(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->bindReportServiceStub();

        $invoiceAgingResponse = $this->actingAs($company)->getJson(route('account.reports.invoice-aging', [
            'as_of_date' => '2026-05-31',
        ]));

        $invoiceAgingResponse->assertOk();
        $invoiceAgingResponse->assertJsonStructure(['aging_summary', 'customers', 'as_of_date']);

        $customerBalanceResponse = $this->actingAs($company)->getJson(route('account.reports.customer-balance', [
            'as_of_date' => '2026-05-31',
        ]));

        $customerBalanceResponse->assertOk();
        $customerBalanceResponse->assertJsonStructure(['customers', 'total_balance', 'as_of_date']);

        $printResponse = $this->actingAs($company)->get(
            route('account.reports.tax-summary.print', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-12-31',
            ]),
            $this->inertiaHeaders()
        );

        $printResponse->assertOk();
        $printResponse->assertHeader('X-Inertia', 'true');
        $printResponse->assertJsonPath('component', 'Account/Reports/Print/TaxSummary');
    }

    public function test_bank_account_api_list_requires_a_finance_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->getJson(route('account.bank-accounts.api.list'));

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Permission denied');

        $financeUser = $this->makeCompany();
        $this->grantPermissions($financeUser, ['create-vendor-payments']);
        $this->makeBankAccount($financeUser);

        $allowedResponse = $this->actingAs($financeUser)->getJson(route('account.bank-accounts.api.list'));

        $allowedResponse->assertOk();
        $allowedResponse->assertJsonCount(1);
        $allowedResponse->assertJsonPath('0.account_name', 'Conta Bancária');
    }

    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
        ];
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeBankAccount(User $company): BankAccount
    {
        return BankAccount::query()->create([
            'account_number' => 'BANK-001',
            'account_name' => 'Conta Bancária',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 1000,
            'current_balance' => 1300,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function bindReportServiceStub(): void
    {
        $reportService = \Mockery::mock(ReportService::class);
        $reportService->shouldReceive('getInvoiceAging')->andReturn([
            'aging_summary' => [
                'current' => 0,
                '1_30_days' => 0,
                '31_60_days' => 0,
                '61_90_days' => 0,
                'over_90_days' => 0,
                'total' => 0,
            ],
            'customers' => [],
            'as_of_date' => '2026-05-31',
        ]);
        $reportService->shouldReceive('getCustomerBalanceSummary')->andReturn([
            'customers' => [],
            'total_balance' => 0,
            'as_of_date' => '2026-05-31',
        ]);
        $reportService->shouldReceive('getTaxSummary')->andReturn([
            'from_date' => '2026-01-01',
            'to_date' => '2026-12-31',
            'tax_collected' => ['items' => [], 'total' => 0],
            'tax_paid' => ['items' => [], 'total' => 0],
            'net_tax_liability' => 0,
        ]);

        app()->instance(ReportService::class, $reportService);
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
