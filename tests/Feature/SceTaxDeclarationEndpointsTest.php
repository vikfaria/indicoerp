<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SceTaxDeclarationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_withholding_declaration_endpoint_returns_period_totals(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $rule = WithholdingTaxRule::create([
            'code' => 'IRPC-SRV-T',
            'name' => 'Retenção serviços teste',
            'income_type' => 'services',
            'rate' => 10.00,
            'applies_to' => 'both',
            'is_final_tax' => false,
            'is_active' => true,
        ]);

        WithholdingTaxTransaction::create([
            'company_id' => $company->id,
            'withholding_rule_id' => $rule->id,
            'vendor_name' => 'Fornecedor Teste',
            'vendor_nuit' => '400123456',
            'transaction_date' => '2026-05-12',
            'document_reference' => 'FT-2026-0001',
            'gross_amount' => 1000,
            'withholding_rate' => 10,
            'withholding_amount' => 100,
            'net_amount' => 900,
            'fiscal_year' => '2026',
            'fiscal_month' => 5,
            'status' => 'pending',
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('sce.tax.withholding.declaration', [
            'year' => '2026',
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertJsonPath('period.year', '2026');
        $response->assertJsonPath('period.month', 5);
        $response->assertJsonPath('totals.gross', 1000);
        $response->assertJsonPath('totals.withholding', 100);
        $response->assertJsonPath('totals.net', 900);
    }

    public function test_model20_and_annual_declaration_endpoints_return_structured_data(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $accountCategoryId = DB::table('account_categories')->insertGetId([
            'name' => 'Activos',
            'code' => 'A',
            'type' => 'assets',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountTypeId = DB::table('account_types')->insertGetId([
            'category_id' => $accountCategoryId,
            'name' => 'Caixa',
            'code' => 'CX',
            'normal_balance' => 'debit',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = DB::table('chart_of_accounts')->insertGetId([
            'account_code' => '1111',
            'account_name' => 'Caixa MZN',
            'account_type_id' => $accountTypeId,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'pgc_class' => 1,
            'modelo20_line' => 'M20-001',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalEntryId = DB::table('journal_entries')->insertGetId([
            'journal_number' => 'JE-2026-001',
            'journal_date' => '2026-03-10',
            'entry_type' => 'manual',
            'reference_type' => 'test',
            'description' => 'Movimento teste Modelo 20',
            'total_debit' => 500,
            'total_credit' => 500,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_items')->insert([
            'journal_entry_id' => $journalEntryId,
            'account_id' => $accountId,
            'description' => 'Linha débito teste',
            'debit_amount' => 500,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $model20Response = $this->actingAs($company)->get(route('sce.tax.modelo20', ['year' => '2026']));
        $model20Response->assertOk();
        $model20Response->assertJsonPath('fiscal_year', '2026');
        $model20Response->assertJsonPath('lines.0.model20_line', 'M20-001');

        $annualResponse = $this->actingAs($company)->get(route('sce.tax.annual-declaration', ['year' => '2026']));
        $annualResponse->assertOk();
        $annualResponse->assertJsonPath('fiscal_year', '2026');
        $annualResponse->assertJsonStructure([
            'vat',
            'irpc',
            'withholding',
            'model20',
        ]);
    }

    public function test_view_tax_summary_permission_can_access_tax_pages(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->actingAs($company)->get(route('sce.tax.vat-map'))->assertOk();
        $this->actingAs($company)->get(route('sce.tax.irpc'))->assertOk();
        $this->actingAs($company)->get(route('sce.tax.withholding'))->assertOk();
        $this->actingAs($company)->get(route('sce.tax.withholding.declaration.page'))->assertOk();
        $this->actingAs($company)->get(route('sce.tax.modelo20.page'))->assertOk();
        $this->actingAs($company)->get(route('sce.tax.annual-declaration.page'))->assertOk();
    }

    public function test_without_tax_permissions_user_cannot_access_tax_pages(): void
    {
        $company = $this->makeCompany();

        $this->actingAs($company)->get(route('sce.tax.vat-map'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.tax.irpc'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.tax.withholding'))->assertForbidden();
    }

    public function test_view_only_user_cannot_run_tax_management_actions(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $rule = WithholdingTaxRule::create([
            'code' => 'IRPC-SRV-MANAGE',
            'name' => 'Retenção serviços gestão',
            'income_type' => 'services',
            'rate' => 10.00,
            'applies_to' => 'both',
            'is_final_tax' => false,
            'is_active' => true,
        ]);

        $this->actingAs($company)
            ->from(route('sce.tax.irpc'))
            ->post(route('sce.tax.irpc.adjustment'), [
                'fiscal_year' => '2026',
                'type' => 'add_back',
                'category' => 'fines_penalties',
                'description' => 'Ajuste bloqueado para perfil consulta',
                'amount' => 150,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('tax_adjustments', 0);

        $this->actingAs($company)
            ->from(route('sce.tax.withholding'))
            ->post(route('sce.tax.withholding.store'), [
                'rule_code' => $rule->code,
                'gross_amount' => 1200,
                'transaction_date' => '2026-05-20',
                'vendor_name' => 'Fornecedor Bloqueado',
                'vendor_nuit' => '400123456',
                'document_reference' => 'FT-BLOCK-001',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('withholding_tax_transactions', 0);
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
    }
}
