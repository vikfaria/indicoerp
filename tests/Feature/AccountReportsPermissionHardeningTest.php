<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\AddOn;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\ReportService;

class AccountReportsPermissionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private int $companySequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(Module::class)->moduleCacheForget();
        $this->enableModule('Account');
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
        $response->assertJsonPath('feature_gate.key', 'treasury.bank_accounts.manage');
        $response->assertJsonPath('feature_gate.state', 'locked');
        $response->assertJsonPath('feature_gate.reasons.0', 'permission_missing');
        $response->assertJsonPath('feature_gate.permissions.missing.0', 'manage-bank-accounts');

        $financeUser = $this->makeCompany();
        $this->grantPermissions($financeUser, ['create-vendor-payments', 'manage-bank-accounts']);
        $this->makeBankAccount($financeUser);

        $allowedResponse = $this->actingAs($financeUser)->getJson(route('account.bank-accounts.api.list'));

        $allowedResponse->assertOk();
        $allowedResponse->assertJsonCount(1);
        $allowedResponse->assertJsonPath('0.account_name', 'Conta Bancária');
    }

    public function test_bank_accounts_index_self_heals_company_access_permissions_before_rendering(): void
    {
        $company = $this->makeCompany();

        foreach ([
            'manage-bank-accounts',
            'manage-any-bank-accounts',
            'manage-own-bank-accounts',
        ] as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );
        }

        $response = $this->actingAs($company)->get(route('account.bank-accounts.index'), $this->inertiaHeaders());

        $response->assertOk();
        $response->assertHeader('X-Inertia', 'true');
        $response->assertJsonPath('component', 'Account/BankAccounts/Index');

        $companyRole = Role::query()
            ->where('name', 'company')
            ->where('created_by', $company->id)
            ->firstOrFail();

        $this->assertTrue($companyRole->hasPermissionTo('manage-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-any-bank-accounts'));
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
        $company = User::forceCreate([
            'id' => 95000 + (++$this->companySequence),
            'name' => 'Empresa ' . (95000 + $this->companySequence),
            'email' => 'company' . (95000 + $this->companySequence) . '@example.com',
            'password' => 'password',
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        UserActiveModule::updateOrCreate(
            ['user_id' => $company->id, 'module' => 'Account'],
            ['module' => 'Account']
        );

        return $company;
    }

    private function enableModule(string $module): void
    {
        AddOn::updateOrCreate(
            ['module' => $module],
            [
                'name' => $module,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => true,
                'for_admin' => false,
                'package_name' => $module,
                'priority' => 10,
            ]
        );

        app(Module::class)->moduleCacheForget($module);
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
