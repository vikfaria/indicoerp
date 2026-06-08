<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\CompanyFiscalProfile;
use App\Models\AddOn;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;

class BankAccountElectronicMoneyExemptionTest extends TestCase
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

    public function test_bank_account_store_blocks_electronic_money_exemption_for_small_enterprise(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-bank-accounts', 'manage-bank-accounts']);
        $this->makeFiscalProfile($company, 'small');
        $branch = $this->makeBranch($company);
        $chartAccountId = $this->makeChartAccount($company);

        $response = $this->from(route('account.bank-accounts.index'))
            ->actingAs($company)
            ->post(route('account.bank-accounts.store'), $this->bankAccountPayload([
                'branch_id' => $branch->id,
                'gl_account_id' => $chartAccountId,
                'electronic_money_limit_exempt_for_enterprise' => true,
                'electronic_money_account_purpose' => 'Conta operacional de moeda electrónica',
            ]));

        $response->assertRedirect(route('account.bank-accounts.index'));
        $response->assertSessionHasErrors(['electronic_money_limit_exempt_for_enterprise']);
        $this->assertDatabaseMissing('bank_accounts', [
            'account_number' => 'EM-EXEMPT-STORE',
            'created_by' => $company->id,
        ]);
    }

    public function test_bank_account_store_allows_electronic_money_exemption_for_medium_enterprise(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-bank-accounts', 'manage-bank-accounts']);
        $this->makeFiscalProfile($company, 'medium');
        $branch = $this->makeBranch($company);
        $chartAccountId = $this->makeChartAccount($company);

        $response = $this->from(route('account.bank-accounts.index'))
            ->actingAs($company)
            ->post(route('account.bank-accounts.store'), $this->bankAccountPayload([
                'branch_id' => $branch->id,
                'gl_account_id' => $chartAccountId,
                'electronic_money_limit_exempt_for_enterprise' => true,
                'electronic_money_account_purpose' => 'Conta operacional de moeda electrónica',
            ]));

        $response->assertRedirect(route('account.bank-accounts.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bank_accounts', [
            'account_number' => 'EM-EXEMPT-STORE',
            'created_by' => $company->id,
            'electronic_money_limit_exempt_for_enterprise' => 1,
        ]);
    }

    public function test_bank_account_store_blocks_electronic_money_exemption_for_inactive_fiscal_profile(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-bank-accounts', 'manage-bank-accounts']);
        $this->makeFiscalProfile($company, 'medium', false);
        $branch = $this->makeBranch($company);
        $chartAccountId = $this->makeChartAccount($company);

        $response = $this->from(route('account.bank-accounts.index'))
            ->actingAs($company)
            ->post(route('account.bank-accounts.store'), $this->bankAccountPayload([
                'branch_id' => $branch->id,
                'gl_account_id' => $chartAccountId,
                'electronic_money_limit_exempt_for_enterprise' => true,
                'electronic_money_account_purpose' => 'Conta operacional de moeda electrónica',
            ]));

        $response->assertRedirect(route('account.bank-accounts.index'));
        $response->assertSessionHasErrors(['electronic_money_limit_exempt_for_enterprise']);
        $this->assertDatabaseMissing('bank_accounts', [
            'account_number' => 'EM-EXEMPT-STORE',
            'created_by' => $company->id,
        ]);
    }

    public function test_bank_account_update_blocks_electronic_money_exemption_for_small_enterprise(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-bank-accounts', 'manage-bank-accounts']);
        $this->makeFiscalProfile($company, 'small');
        $branch = $this->makeBranch($company);
        $chartAccountId = $this->makeChartAccount($company);
        $bankAccount = BankAccount::query()->create([
            'account_number' => 'EM-EXEMPT-UPDATE',
            'account_name' => 'Conta EM Base',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'branch_id' => $branch->id,
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 1000,
            'electronic_money_monthly_limit_mzn' => 2000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'electronic_money_account_purpose' => 'Conta operacional',
            'gl_account_id' => $chartAccountId,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.bank-accounts.index'))
            ->actingAs($company)
            ->put(route('account.bank-accounts.update', $bankAccount->id), $this->bankAccountPayload([
                'gl_account_id' => $chartAccountId,
                'electronic_money_limit_exempt_for_enterprise' => true,
                'electronic_money_account_purpose' => 'Conta operacional de moeda electrónica',
            ]));

        $response->assertRedirect(route('account.bank-accounts.index'));
        $response->assertSessionHasErrors(['electronic_money_limit_exempt_for_enterprise']);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'electronic_money_limit_exempt_for_enterprise' => 0,
        ]);
    }

    public function test_electronic_money_report_flags_legacy_exemption_misconfiguration(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->makeFiscalProfile($company, 'small');
        $chartAccountId = $this->makeChartAccount($company);
        $branch = $this->makeBranch($company);

        BankAccount::query()->create([
            'account_number' => 'EM-LEGACY-001',
            'account_name' => 'Conta EM Legada',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'branch_id' => $branch->id,
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 5000,
            'electronic_money_monthly_limit_mzn' => 20000,
            'electronic_money_limit_exempt_for_enterprise' => true,
            'electronic_money_account_purpose' => null,
            'gl_account_id' => $chartAccountId,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-electronic-money-compliance-report', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'refresh' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.enterprise_exemption_misconfigured', 1);
        $response->assertJsonFragment([
            'account_number' => 'EM-LEGACY-001',
            'requires_attention_reason' => 'company_not_medium_or_large',
        ]);
    }

    private function makeCompany(): User
    {
        $company = User::forceCreate([
            'id' => 93000 + (++$this->companySequence),
            'name' => 'Empresa ' . (93000 + $this->companySequence),
            'email' => 'company' . (93000 + $this->companySequence) . '@example.com',
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

    private function makeFiscalProfile(User $company, string $classification, bool $isActive = true): CompanyFiscalProfile
    {
        return CompanyFiscalProfile::query()->create([
            'company_id' => $company->id,
            'entity_classification' => $classification,
            'is_active' => $isActive,
            'created_by' => $company->id,
        ]);
    }

    private function makeChartAccount(User $company): int
    {
        $categoryId = (int) DB::table('account_categories')->insertGetId([
            'name' => 'Ativos',
            'code' => 'ATV-' . uniqid(),
            'type' => 'assets',
            'description' => 'Categoria teste para moeda electrónica',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountTypeId = (int) DB::table('account_types')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Caixa/Conta',
            'code' => 'EM-' . uniqid(),
            'normal_balance' => 'debit',
            'description' => 'Tipo teste para moeda electrónica',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('chart_of_accounts')->insertGetId([
            'account_code' => '1' . random_int(100, 999),
            'account_name' => 'Conta GL Moeda Electrónica',
            'account_type_id' => $accountTypeId,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'description' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function bankAccountPayload(array $overrides = []): array
    {
        return array_merge([
            'account_number' => 'EM-EXEMPT-STORE',
            'account_name' => 'Conta EM Exempt',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'branch_id' => null,
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'iban' => null,
            'swift_code' => null,
            'routing_number' => null,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 1000,
            'electronic_money_monthly_limit_mzn' => 2000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'electronic_money_account_purpose' => 'Conta operacional de moeda electrónica',
            'gl_account_id' => 1,
        ], $overrides);
    }

    private function makeBranch(User $company, string $branchName = 'Maputo'): \Workdo\Hrm\Models\Branch
    {
        return \Workdo\Hrm\Models\Branch::query()->create([
            'branch_name' => $branchName,
            'creator_id' => $company->id,
            'created_by' => $company->id,
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
}
