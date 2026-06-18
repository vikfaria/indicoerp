<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use App\Services\AssistantActivation\PlanFeatureResolver;
use App\Services\AssistantActivation\PlanLimitResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Pos\Models\Pos;
use Workdo\ProductService\Models\ProductServiceItem;

class AssistantActivationPlanFeatureResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(Module::class)->moduleCacheForget();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_it_resolves_active_locked_addon_and_hidden_feature_states(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('ProductService');
        $this->enableModule('DoubleEntry');

        $activeCompany = $this->makeCompany(91001, $plan);
        $lockedCompany = $this->makeCompany(91002, $plan);
        $addonCompany = $this->makeCompany(91003, $plan);

        $this->grantPermissions($activeCompany, ['create-sales-invoices']);
        $this->grantPermissions($lockedCompany, ['create-sales-invoices']);
        $this->grantPermissions($addonCompany, ['manage-double-entry']);

        $this->activateModuleForCompany($activeCompany, 'Account');
        $this->activateModuleForCompany($lockedCompany, 'Account');
        $this->activateModuleForCompany($addonCompany, 'Account');

        $this->prepareFiscalSetup($activeCompany, includeAccountingPeriod: true);
        $this->prepareFiscalSetup($lockedCompany, includeAccountingPeriod: false);

        $resolver = app(PlanFeatureResolver::class);

        $active = $resolver->resolve('billing.invoice.create', $activeCompany);
        $locked = $resolver->resolve('billing.invoice.create', $lockedCompany);
        $addon = $resolver->resolve('accounting.double_entry.post', $addonCompany);
        $hidden = $resolver->resolve('unknown.feature.key', $activeCompany);

        $this->assertSame('active', $active['state']);
        $this->assertSame([], $active['missing_permissions']);
        $this->assertSame([], $active['missing_config_keys']);
        $this->assertSame('active', $active['subscription_state']);
        $this->assertContains('active', array_column($active['modules'], 'state'));

        $activeModules = collect($active['modules'])->keyBy('module');
        $this->assertContains('billing.invoice.create', $activeModules['Account']['feature_keys']);
        $this->assertSame(['account'], $activeModules['Account']['catalog_module_keys']);
        $this->assertContains('billing.invoice.create', $activeModules['ProductService']['feature_keys']);
        $this->assertContains('billing.product.manage', $activeModules['ProductService']['feature_keys']);

        $this->assertSame('locked', $locked['state']);
        $this->assertSame(['accounting_period'], $locked['missing_config_keys']);
        $this->assertContains('config_missing:accounting_period', $locked['reasons']);
        $this->assertSame('active', $locked['subscription_state']);

        $this->assertSame('addon', $addon['state']);
        $this->assertContains('DoubleEntry', $addon['addon_modules']);
        $this->assertContains('addon_required', $addon['reasons']);

        $this->assertSame('hidden', $hidden['state']);
        $this->assertContains('feature_unknown', $hidden['reasons']);
    }

    public function test_it_requires_tax_profile_for_accounting_posting(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'DoubleEntry'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('DoubleEntry');

        $company = $this->makeCompany(91004, $plan);

        $this->grantPermissions($company, ['manage-double-entry']);
        $this->activateModuleForCompany($company, 'Account');
        $this->activateModuleForCompany($company, 'DoubleEntry');

        $this->prepareFiscalSetup($company, includeAccountingPeriod: true, includeTaxProfile: false);
        $this->makeChartAccount($company, '1110', 'Caixa principal', 'debit');

        $resolution = app(PlanFeatureResolver::class)->resolve('accounting.double_entry.post', $company);

        $this->assertSame('locked', $resolution['state']);
        $this->assertSame(['tax_profile'], $resolution['missing_config_keys']);
        $this->assertContains('config_missing:tax_profile', $resolution['reasons']);

        $taxProfileCheck = collect($resolution['config_checks'])->firstWhere('key', 'tax_profile');
        $this->assertNotNull($taxProfileCheck);
        $this->assertSame(
            ['vat_output_account_id', 'vat_input_account_id'],
            $taxProfileCheck['details']['missing_items']
        );
    }

    public function test_it_requires_payroll_contributions_for_hr_payroll_processing(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Hrm'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        $this->enableModule('Hrm');

        $company = $this->makeCompany(91005, $plan);

        $this->grantPermissions($company, ['manage-payrolls']);
        $this->activateModuleForCompany($company, 'Hrm');
        $this->prepareHrPayrollSetup($company);

        $resolver = app(PlanFeatureResolver::class);

        $locked = $resolver->resolve('hr.payroll.run', $company);

        $this->assertSame('locked', $locked['state']);
        $this->assertContains('payroll_contributions', $locked['missing_config_keys']);
        $this->assertContains('config_missing:payroll_contributions', $locked['reasons']);

        $this->preparePayrollContributions($company);

        $active = $resolver->resolve('hr.payroll.run', $company);

        $this->assertSame('active', $active['state']);
        $this->assertSame([], $active['missing_config_keys']);
    }

    public function test_it_blocks_features_when_subscription_is_expired(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('ProductService');

        $company = $this->makeCompany(91007, $plan);
        $company->update([
            'plan_expire_date' => now()->subDay()->toDateString(),
        ]);

        $this->grantPermissions($company, ['create-sales-invoices']);
        $this->activateModuleForCompany($company, 'Account');
        $this->activateModuleForCompany($company, 'ProductService');
        $this->prepareFiscalSetup($company, includeAccountingPeriod: true);

        $resolution = app(PlanFeatureResolver::class)->resolve('billing.invoice.create', $company);

        $this->assertSame('locked', $resolution['state']);
        $this->assertSame('expired', $resolution['subscription_state']);
        $this->assertContains('subscription_expired', $resolution['reasons']);
        $this->assertSame([], $resolution['missing_permissions']);
        $this->assertSame([], $resolution['missing_config_keys']);
    }

    public function test_it_blocks_features_when_required_permission_is_missing(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('ProductService');

        $company = $this->makeCompany(91008, $plan);

        $this->activateModuleForCompany($company, 'Account');
        $this->activateModuleForCompany($company, 'ProductService');
        $this->prepareFiscalSetup($company, includeAccountingPeriod: true);

        $resolution = app(PlanFeatureResolver::class)->resolve('billing.invoice.create', $company);

        $this->assertSame('locked', $resolution['state']);
        $this->assertSame('active', $resolution['subscription_state']);
        $this->assertSame(['create-sales-invoices'], $resolution['missing_permissions']);
        $this->assertContains('permission_missing', $resolution['reasons']);
        $this->assertContains('permission_missing:create-sales-invoices', $resolution['reasons']);
        $this->assertSame([], $resolution['missing_config_keys']);
    }

    public function test_it_resolves_inventory_and_pos_features_and_invalidates_cache_on_stock_changes(): void
    {
        $plan = Plan::create([
            'name' => 'Inventory Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['ProductService', 'Pos'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        $this->enableModule('ProductService');
        $this->enableModule('Pos');

        $company = $this->makeCompany(91006, $plan);

        $this->grantPermissions($company, [
            'manage-warehouses',
            'create-warehouses',
            'manage-stock',
            'create-stock',
            'manage-transfers',
            'create-transfers',
            'manage-pos',
            'manage-pos-orders',
            'create-pos',
        ]);

        $this->activateModuleForCompany($company, 'ProductService');
        $this->activateModuleForCompany($company, 'Pos');

        $cacheService = app(AssistantActivationCacheService::class);
        $resolver = app(PlanFeatureResolver::class);

        $warehouseFeature = $resolver->resolve('inventory.warehouse.manage', $company);
        $this->assertSame('active', $warehouseFeature['state']);
        $this->assertSame([], $warehouseFeature['missing_modules']);

        $stockFeatureKey1 = $cacheService->featureCacheKey('inventory.stock.manage', $company);
        $stockFeatureLocked = $resolver->resolve('inventory.stock.manage', $company);

        $this->assertSame('locked', $stockFeatureLocked['state']);
        $this->assertContains('config_missing:warehouses', $stockFeatureLocked['reasons']);
        $this->assertTrue(Cache::has($stockFeatureKey1));

        $warehouse = $this->createInventoryWarehouse($company);

        $stockFeatureKey2 = $cacheService->featureCacheKey('inventory.stock.manage', $company);
        $this->assertNotSame($stockFeatureKey1, $stockFeatureKey2);

        $stockFeatureActive = $resolver->resolve('inventory.stock.manage', $company);
        $this->assertSame('active', $stockFeatureActive['state']);
        $this->assertSame([], $stockFeatureActive['missing_config_keys']);

        $transferFeatureKey1 = $cacheService->featureCacheKey('inventory.transfer.manage', $company);
        $transferFeatureLocked = $resolver->resolve('inventory.transfer.manage', $company);
        $this->assertSame('locked', $transferFeatureLocked['state']);
        $this->assertContains('config_missing:initial_stock', $transferFeatureLocked['reasons']);
        $this->assertTrue(Cache::has($transferFeatureKey1));

        $posFeatureKey1 = $cacheService->featureCacheKey('inventory.pos.manage', $company);
        $posFeatureLocked = $resolver->resolve('inventory.pos.manage', $company);
        $this->assertSame('locked', $posFeatureLocked['state']);
        $this->assertContains('config_missing:initial_stock', $posFeatureLocked['reasons']);
        $this->assertTrue(Cache::has($posFeatureKey1));

        $product = $this->createInventoryProduct($company);
        $movement = $this->createInventoryStockMovement($company, $product);
        $layer = $this->createInventoryCostLayer($company, $product, $movement);
        $posSale = $this->createInventoryPosSale($company, $warehouse);

        $transferFeatureKey2 = $cacheService->featureCacheKey('inventory.transfer.manage', $company);
        $posFeatureKey2 = $cacheService->featureCacheKey('inventory.pos.manage', $company);

        $this->assertNotSame($transferFeatureKey1, $transferFeatureKey2);
        $this->assertNotSame($posFeatureKey1, $posFeatureKey2);

        $transferFeature = $resolver->resolve('inventory.transfer.manage', $company);
        $posFeature = $resolver->resolve('inventory.pos.manage', $company);

        $this->assertSame('active', $transferFeature['state']);
        $this->assertSame([], $transferFeature['missing_config_keys']);
        $this->assertSame('active', $posFeature['state']);
        $this->assertSame([], $posFeature['missing_config_keys']);
        $this->assertSame($warehouse->id, $posSale->warehouse_id);
        $this->assertSame($movement->id, $layer->stock_movement_id);
    }

    public function test_it_uses_versioned_cache_keys_and_invalidates_on_plan_module_and_company_changes(): void
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('ProductService');

        $company = $this->makeCompany(91011, $plan);
        $this->grantPermissions($company, ['create-sales-invoices']);
        $this->activateModuleForCompany($company, 'Account');
        $this->prepareFiscalSetup($company, includeAccountingPeriod: true);

        $cacheService = app(AssistantActivationCacheService::class);
        $featureResolver = app(PlanFeatureResolver::class);
        $limitResolver = app(PlanLimitResolver::class);

        $featureKey1 = $cacheService->featureCacheKey('billing.invoice.create', $company);
        $featureResolution1 = $featureResolver->resolve('billing.invoice.create', $company);

        $this->assertSame('active', $featureResolution1['state']);
        $this->assertTrue(Cache::has($featureKey1));
        $this->assertSame('active', Cache::get($featureKey1)['state']);

        $plan->name = 'Professional Plan v2';
        $plan->save();

        $featureKey2 = $cacheService->featureCacheKey('billing.invoice.create', $company);
        $this->assertNotSame($featureKey1, $featureKey2);

        $addon = AddOn::where('module', 'Account')->firstOrFail();
        $addon->update([
            'monthly_price' => 15,
        ]);

        $featureKey3 = $cacheService->featureCacheKey('billing.invoice.create', $company);
        $this->assertNotSame($featureKey2, $featureKey3);

        $limitKey1 = $cacheService->limitCacheKey('users', $company, CarbonImmutable::parse('2026-06-07'));
        $limitResolution1 = $limitResolver->resolve('users', $company, CarbonImmutable::parse('2026-06-07'));

        $this->assertSame(0, $limitResolution1['current_usage']);
        $this->assertTrue(Cache::has($limitKey1));

        User::forceCreate([
            'id' => 91012,
            'name' => 'Utilizador Extra',
            'email' => 'extra91012@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'is_disable' => false,
            'is_enable_login' => true,
        ]);

        $limitKey2 = $cacheService->limitCacheKey('users', $company, CarbonImmutable::parse('2026-06-07'));
        $limitResolution2 = $limitResolver->resolve('users', $company, CarbonImmutable::parse('2026-06-07'));

        $this->assertNotSame($limitKey1, $limitKey2);
        $this->assertSame(1, $limitResolution2['current_usage']);
    }

    private function makeCompany(int $id, Plan $plan): User
    {
        return User::forceCreate([
            'id' => $id,
            'name' => 'Empresa ' . $id,
            'email' => 'company' . $id . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
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

            if (! $user->hasPermissionTo($permission)) {
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
        Cache::forget('user:activated_modules:guest:admin');
    }

    private function activateModuleForCompany(User $company, string $module): void
    {
        UserActiveModule::updateOrCreate(
            [
                'user_id' => $company->id,
                'module' => $module,
            ],
            []
        );

        Cache::forget('user:activated_modules:user:' . $company->id);
    }

    private function prepareFiscalSetup(User $company, bool $includeAccountingPeriod, bool $includeTaxProfile = true): void
    {
        CompanyFiscalProfile::updateOrCreate(
            ['company_id' => $company->id],
            [
                'nuit' => '400123456',
                'legal_name' => 'Empresa ' . $company->id . ', Lda',
                'fiscal_regime' => 'normal',
                'accounting_framework' => 'pgc_nirf',
                'fiscal_year_start_month' => 1,
                'is_active' => true,
                'created_by' => $company->id,
            ]
        );

        if ($includeAccountingPeriod) {
            AccountingPeriod::generateForYear($company->id, '2026');
        }

        $this->ensureDocumentSeries($company);

        if ($includeTaxProfile) {
            $this->ensureTaxProfile($company);
        }
    }

    private function prepareHrPayrollSetup(User $company): void
    {
        Employee::create([
            'employee_id' => 'EMP-' . $company->id,
            'gender' => 'Male',
            'date_of_joining' => now()->toDateString(),
            'employment_type' => 'permanent',
            'country' => 'Mozambique',
            'user_id' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Payroll::create([
            'title' => 'Payroll Teste',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Payroll de teste',
            'total_gross_pay' => 1500,
            'total_deductions' => 150,
            'total_net_pay' => 1350,
            'total_irps' => 0,
            'total_inss_employee' => 0,
            'total_inss_employer' => 0,
            'employee_count' => 1,
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'bank_account_id' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function createInventoryWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Armazém ' . $company->id,
            'address' => 'Rua do Armazém, 1',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '+258840000002',
            'email' => 'warehouse' . $company->id . '@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function createInventoryProduct(User $company): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Produto Inventário ' . $company->id,
            'sku' => 'SKU-INVENTORY-' . $company->id,
            'description' => 'Produto usado no teste de inventário',
            'sale_price' => 120.00,
            'purchase_price' => 80.00,
            'unit' => 'un',
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function createInventoryStockMovement(User $company, ProductServiceItem $product): StockMovement
    {
        return StockMovement::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'movement_date' => now()->toDateString(),
            'quantity' => 10,
            'unit_cost' => 80,
            'total_cost' => 800,
            'running_quantity' => 10,
            'running_value' => 800,
            'reference_type' => 'manual',
            'reference_id' => null,
            'warehouse_code' => 'WH-' . $company->id,
            'notes' => 'Stock inicial para validação de FIFO',
            'journal_entry_id' => null,
            'created_by' => $company->id,
        ]);
    }

    private function createInventoryCostLayer(User $company, ProductServiceItem $product, StockMovement $movement): StockCostLayer
    {
        return StockCostLayer::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'stock_movement_id' => $movement->id,
            'original_quantity' => 10,
            'remaining_quantity' => 10,
            'unit_cost' => 80,
            'entry_date' => now()->toDateString(),
            'is_exhausted' => false,
            'created_by' => $company->id,
        ]);
    }

    private function createInventoryPosSale(User $company, Warehouse $warehouse): Pos
    {
        return Pos::create([
            'sale_number' => 'POS-' . $company->id . '-001',
            'document_type' => 'POS',
            'document_series' => 'A',
            'document_sequence' => 1,
            'establishment_id' => $warehouse->id,
            'customer_id' => null,
            'warehouse_id' => $warehouse->id,
            'pos_date' => now()->toDateString(),
            'status' => 'completed',
            'fiscal_submission_status' => 'not_required',
            'fiscal_submission_reference' => null,
            'fiscal_submitted_at' => null,
            'fiscal_validated_at' => null,
            'fiscal_validation_message' => null,
            'is_cancelled' => false,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancellation_reference' => null,
            'rectification_reference' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function preparePayrollContributions(User $company): void
    {
        $irpsTable = MozIrpsTable::create([
            'name' => 'Tabela IRPS Teste',
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozIrpsBracket::create([
            'irps_table_id' => $irpsTable->id,
            'range_from' => 0,
            'range_to' => null,
            'fixed_amount' => 0,
            'rate_percent' => 10,
            'sequence' => 1,
        ]);

        MozInssRate::create([
            'employee_rate' => 3.0000,
            'employer_rate' => 4.0000,
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);
    }

    private function ensureDocumentSeries(User $company): void
    {
        $salesType = FiscalDocumentType::query()
            ->where('code', 'FT')
            ->firstOrFail();

        FiscalDocumentSeries::updateOrCreate(
            [
                'company_id' => $company->id,
                'fiscal_document_type_id' => $salesType->id,
                'series_code' => 'A',
            ],
            [
                'assigned_user_id' => $company->id,
                'terminal_code' => 'T1',
                'fiscal_regime_code' => 'normal',
                'fiscal_year' => '2026',
                'last_sequence' => 0,
                'is_active' => true,
                'valid_from' => now()->startOfMonth()->toDateString(),
                'valid_to' => now()->addYear()->toDateString(),
                'created_by' => $company->id,
            ]
        );
    }

    private function ensureTaxProfile(User $company): void
    {
        $vatOutput = $this->makeChartAccount($company, '2431', 'IVA liquidado', 'credit');
        $vatInput = $this->makeChartAccount($company, '2432', 'IVA dedutível', 'debit');

        MozTaxAccountMapping::updateOrCreate(
            [
                'created_by' => $company->id,
                'is_active' => true,
                'effective_from' => now()->toDateString(),
            ],
            [
                'vat_output_account_id' => $vatOutput->id,
                'vat_input_account_id' => $vatInput->id,
                'withholding_payable_account_id' => null,
                'withholding_receivable_account_id' => null,
                'irpc_expense_account_id' => null,
                'effective_to' => null,
                'notes' => 'Tax profile for assistant activation tests',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]
        );
    }

    private function makeChartAccount(User $company, string $code, string $name, string $balance): ChartOfAccount
    {
        $category = AccountCategory::create([
            'name' => 'Categoria ' . $code,
            'code' => 'CAT-' . $code,
            'type' => $balance === 'credit' ? 'liabilities' : 'assets',
            'description' => 'Categoria de teste',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $accountType = AccountType::create([
            'category_id' => $category->id,
            'name' => 'Tipo ' . $code,
            'code' => 'TYP-' . $code,
            'normal_balance' => $balance,
            'description' => 'Tipo de teste',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type_id' => $accountType->id,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => $balance,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}
