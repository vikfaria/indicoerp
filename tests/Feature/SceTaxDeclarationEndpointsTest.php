<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AuditTrail;
use App\Models\FiscalExportHistory;
use App\Models\TaxAdjustment;
use App\Models\User;
use App\Models\WithholdingTaxTreatyRate;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'beneficiary_country' => 'MZ',
            'beneficiary_residency_status' => 'resident',
            'income_type_snapshot' => 'services',
            'transaction_date' => '2026-05-12',
            'document_reference' => 'FT-2026-0001',
            'source_reference_type' => 'vendor_payment',
            'source_reference_id' => 999,
            'gross_amount' => 1000,
            'withholding_rate' => 10,
            'withholding_treatment' => 'withheld',
            'adt_applied' => false,
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
        $response->assertJsonPath('detailed_map.0.beneficiary', 'Fornecedor Teste');
        $response->assertJsonPath('detailed_map.0.beneficiary_tax_number', '400123456');
        $response->assertJsonPath('detailed_map.0.income_type', 'services');
        $response->assertJsonPath('detailed_map.0.withholding_treatment', 'withheld');
        $response->assertJsonPath('history_by_vendor.0.beneficiary_tax_number', '400123456');
        $response->assertJsonPath('history_by_vendor.0.transactions', 1);
    }

    public function test_manage_user_can_update_withholding_settlement_status_by_filters(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $rule = WithholdingTaxRule::create([
            'code' => 'IRPC-SRV-SETTLE',
            'name' => 'Retenção serviços settlement',
            'income_type' => 'services',
            'rate' => 10.00,
            'applies_to' => 'both',
            'is_final_tax' => false,
            'is_active' => true,
        ]);

        $txA = WithholdingTaxTransaction::create([
            'company_id' => $company->id,
            'withholding_rule_id' => $rule->id,
            'vendor_name' => 'Fornecedor A',
            'vendor_nuit' => '400123456',
            'beneficiary_country' => 'MZ',
            'beneficiary_residency_status' => 'resident',
            'income_type_snapshot' => 'services',
            'transaction_date' => '2026-05-15',
            'document_reference' => 'FT-SET-001',
            'gross_amount' => 2000,
            'withholding_rate' => 10,
            'withholding_treatment' => 'withheld',
            'withholding_amount' => 200,
            'net_amount' => 1800,
            'fiscal_year' => '2026',
            'fiscal_month' => 5,
            'status' => 'pending',
            'created_by' => $company->id,
        ]);

        $txB = WithholdingTaxTransaction::create([
            'company_id' => $company->id,
            'withholding_rule_id' => $rule->id,
            'vendor_name' => 'Fornecedor B',
            'vendor_nuit' => '400654321',
            'beneficiary_country' => 'PT',
            'beneficiary_residency_status' => 'non_resident',
            'income_type_snapshot' => 'services',
            'transaction_date' => '2026-05-18',
            'document_reference' => 'FT-SET-002',
            'gross_amount' => 1000,
            'withholding_rate' => 10,
            'withholding_treatment' => 'withheld',
            'withholding_amount' => 100,
            'net_amount' => 900,
            'fiscal_year' => '2026',
            'fiscal_month' => 5,
            'status' => 'pending',
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)
            ->post(route('sce.tax.withholding.declaration.settlement'), [
                'year' => '2026',
                'month' => 5,
                'status' => 'pending',
                'action' => 'mark_declared',
                'declaration_reference' => 'DEC-2026-05',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('withholding_tax_transactions', [
            'id' => $txA->id,
            'status' => 'declared',
            'declaration_reference' => 'DEC-2026-05',
        ]);

        $this->assertDatabaseHas('withholding_tax_transactions', [
            'id' => $txB->id,
            'status' => 'declared',
            'declaration_reference' => 'DEC-2026-05',
        ]);

        $this->actingAs($company)
            ->post(route('sce.tax.withholding.declaration.settlement'), [
                'year' => '2026',
                'month' => 5,
                'vendor_nuit' => '400123456',
                'action' => 'mark_paid',
                'state_payment_reference' => 'PAG-2026-05-001',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('withholding_tax_transactions', [
            'id' => $txA->id,
            'status' => 'paid',
            'state_payment_reference' => 'PAG-2026-05-001',
        ]);

        $this->assertDatabaseHas('withholding_tax_transactions', [
            'id' => $txB->id,
            'status' => 'declared',
        ]);
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

    public function test_vat_map_export_returns_csv_and_logs_history(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $accountCategoryId = DB::table('account_categories')->insertGetId([
            'name' => 'Passivo',
            'code' => 'P',
            'type' => 'liabilities',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountTypeId = DB::table('account_types')->insertGetId([
            'category_id' => $accountCategoryId,
            'name' => 'IVA',
            'code' => 'IVA',
            'normal_balance' => 'credit',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outputVatAccountId = DB::table('chart_of_accounts')->insertGetId([
            'account_code' => '2433',
            'account_name' => 'IVA liquidado',
            'account_type_id' => $accountTypeId,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'credit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'pgc_class' => 2,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inputVatAccountId = DB::table('chart_of_accounts')->insertGetId([
            'account_code' => '2432',
            'account_name' => 'IVA dedutível',
            'account_type_id' => $accountTypeId,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'pgc_class' => 2,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalEntryId = DB::table('journal_entries')->insertGetId([
            'journal_number' => 'JE-VAT-001',
            'journal_date' => '2026-05-10',
            'entry_type' => 'manual',
            'reference_type' => 'test',
            'description' => 'IVA liquidado teste',
            'total_debit' => 0,
            'total_credit' => 120,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_items')->insert([
            'journal_entry_id' => $journalEntryId,
            'account_id' => $outputVatAccountId,
            'description' => 'IVA liquidado teste',
            'debit_amount' => 0,
            'credit_amount' => 120,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalEntryId2 = DB::table('journal_entries')->insertGetId([
            'journal_number' => 'JE-VAT-002',
            'journal_date' => '2026-05-15',
            'entry_type' => 'manual',
            'reference_type' => 'test',
            'description' => 'IVA dedutível teste',
            'total_debit' => 40,
            'total_credit' => 0,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_items')->insert([
            'journal_entry_id' => $journalEntryId2,
            'account_id' => $inputVatAccountId,
            'description' => 'IVA dedutível teste',
            'debit_amount' => 40,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($company)->get(route('sce.tax.vat-map.export', [
            'year' => '2026',
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="vat-map-2026-05.csv"');

        $csv = $response->getContent();
        $this->assertIsString($csv);
        $this->assertStringContainsString('"totals","output_vat","120.00"', $csv);
        $this->assertStringContainsString('"totals","deductible_vat","40.00"', $csv);
        $this->assertStringContainsString('"totals","vat_payable","80.00"', $csv);

        $history = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'vat_map_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('vat-map-2026-05.csv', $history->file_name);
        $this->assertSame(hash('sha256', $csv), $history->file_hash);
        $this->assertNotEmpty($history->file_path);
        $this->assertTrue(Storage::disk('local')->exists($history->file_path));
        $this->assertSame('text/csv', data_get($history->metadata, 'content_type'));
        $this->assertSame('sce.tax.vat-map.export', data_get($history->metadata, 'source'));

        if ($history->file_path) {
            Storage::disk('local')->delete($history->file_path);
        }
    }

    public function test_irpc_and_annual_declaration_exports_return_csv_and_log_history(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        TaxAdjustment::query()->create([
            'company_id' => $company->id,
            'fiscal_year' => '2026',
            'type' => 'add_back',
            'category' => 'fines_penalties',
            'description' => 'Ajuste de teste para exportação IRPC',
            'amount' => 250,
            'legal_basis' => 'Art. 34 CIRPC',
            'created_by' => $company->id,
        ]);

        $irpcResponse = $this->actingAs($company)->get(route('sce.tax.irpc.export', ['year' => '2026']));

        $irpcResponse->assertOk();
        $irpcResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $irpcResponse->assertHeader('Content-Disposition', 'attachment; filename="irpc-guide-2026.csv"');

        $irpcCsv = $irpcResponse->getContent();
        $this->assertIsString($irpcCsv);
        $this->assertStringContainsString('"calculation","accounting_result"', $irpcCsv);
        $this->assertStringContainsString('"adjustments","type"', $irpcCsv);

        $irpcHistory = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'irpc_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($irpcHistory);
        $this->assertSame('irpc-guide-2026.csv', $irpcHistory->file_name);
        $this->assertSame(hash('sha256', $irpcCsv), $irpcHistory->file_hash);
        $this->assertNotEmpty($irpcHistory->file_path);
        $this->assertTrue(Storage::disk('local')->exists($irpcHistory->file_path));
        $this->assertSame('text/csv', data_get($irpcHistory->metadata, 'content_type'));
        $this->assertSame('sce.tax.irpc.export', data_get($irpcHistory->metadata, 'source'));

        $annualResponse = $this->actingAs($company)->get(route('sce.tax.annual-declaration.export', ['year' => '2026']));

        $annualResponse->assertOk();
        $annualResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $annualResponse->assertHeader('Content-Disposition', 'attachment; filename="annual-declaration-2026.csv"');

        $annualCsv = $annualResponse->getContent();
        $this->assertIsString($annualCsv);
        $this->assertStringContainsString('"vat","output_vat"', $annualCsv);
        $this->assertStringContainsString('"irpc","accounting_result"', $annualCsv);

        $annualHistory = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'annual_declaration_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($annualHistory);
        $this->assertSame('annual-declaration-2026.csv', $annualHistory->file_name);
        $this->assertSame(hash('sha256', $annualCsv), $annualHistory->file_hash);
        $this->assertNotEmpty($annualHistory->file_path);
        $this->assertTrue(Storage::disk('local')->exists($annualHistory->file_path));
        $this->assertSame('text/csv', data_get($annualHistory->metadata, 'content_type'));
        $this->assertSame('sce.tax.annual-declaration.export', data_get($annualHistory->metadata, 'source'));

        if ($irpcHistory->file_path) {
            Storage::disk('local')->delete($irpcHistory->file_path);
        }

        if ($annualHistory->file_path) {
            Storage::disk('local')->delete($annualHistory->file_path);
        }
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

        $this->actingAs($company)
            ->from(route('sce.tax.withholding.declaration.page'))
            ->post(route('sce.tax.withholding.declaration.settlement'), [
                'year' => '2026',
                'month' => 5,
                'action' => 'mark_declared',
                'declaration_reference' => 'DEC-2026-05',
            ])
            ->assertSessionHas('error');
    }

    public function test_manage_user_can_manage_withholding_treaty_rates_with_company_scope_and_audit(): void
    {
        $company = $this->makeCompany();
        $otherCompany = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary', 'manage-account-reports']);

        WithholdingTaxTreatyRate::query()->create([
            'code' => 'AO-SERVICES',
            'country_code' => 'AO',
            'country_name' => 'Angola',
            'income_type' => 'services',
            'standard_rate' => 20,
            'treaty_rate' => 15,
            'requires_residency_certificate' => true,
            'legal_basis' => 'ADT AO-MZ',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'is_active' => true,
            'created_by' => $otherCompany->id,
        ]);

        $createResponse = $this->actingAs($company)->postJson(route('sce.tax.withholding.treaty-rates.store'), [
            'country_code' => 'pt',
            'country_name' => 'Portugal',
            'income_type' => 'services',
            'standard_rate' => 20,
            'treaty_rate' => 10,
            'requires_residency_certificate' => true,
            'legal_basis' => 'ADT Portugal-MZ',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'is_active' => true,
        ]);

        $createResponse->assertCreated();
        $rateId = (int) $createResponse->json('data.id');
        $this->assertGreaterThan(0, $rateId);

        $this->assertDatabaseHas('withholding_tax_treaty_rates', [
            'id' => $rateId,
            'country_code' => 'PT',
            'country_name' => 'Portugal',
            'income_type' => 'services',
            'created_by' => $company->id,
            'is_active' => 1,
        ]);

        $listResponse = $this->actingAs($company)->getJson(route('sce.tax.withholding.treaty-rates.index', [
            'country' => 'Portugal',
        ]));

        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'rows');
        $listResponse->assertJsonPath('rows.0.id', $rateId);
        $listResponse->assertJsonPath('rows.0.country_code', 'PT');

        $this->actingAs($company)->putJson(route('sce.tax.withholding.treaty-rates.update', $rateId), [
            'treaty_rate' => 8,
            'standard_rate' => 20,
            'legal_basis' => 'ADT Portugal-MZ (revisto)',
        ])->assertOk();

        $this->assertDatabaseHas('withholding_tax_treaty_rates', [
            'id' => $rateId,
            'treaty_rate' => 8,
            'legal_basis' => 'ADT Portugal-MZ (revisto)',
            'is_active' => 1,
        ]);

        $this->actingAs($company)
            ->deleteJson(route('sce.tax.withholding.treaty-rates.deactivate', $rateId))
            ->assertOk();

        $this->assertDatabaseHas('withholding_tax_treaty_rates', [
            'id' => $rateId,
            'is_active' => 0,
        ]);

        $auditRows = AuditTrail::query()
            ->where('auditable_type', WithholdingTaxTreatyRate::class)
            ->where('auditable_id', $rateId)
            ->whereIn('event', ['created', 'updated'])
            ->get();

        $this->assertGreaterThanOrEqual(2, $auditRows->count());
    }

    public function test_view_user_can_compare_treaty_rate_but_cannot_manage_treaty_table(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        WithholdingTaxTreatyRate::query()->create([
            'code' => 'PT-SERVICES',
            'country_code' => 'PT',
            'country_name' => 'Portugal',
            'income_type' => 'services',
            'standard_rate' => 20,
            'treaty_rate' => 10,
            'requires_residency_certificate' => true,
            'legal_basis' => 'ADT Portugal-MZ',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $compareResponse = $this->actingAs($company)->getJson(route('sce.tax.withholding.treaty-rates.compare', [
            'country' => 'Portugal',
            'income_type' => 'services',
            'standard_rate' => 20,
            'as_of_date' => '2026-05-01',
        ]));

        $compareResponse->assertOk();
        $compareResponse->assertJsonPath('found_treaty_rate', true);
        $compareResponse->assertJsonPath('source', 'treaty');
        $compareResponse->assertJsonPath('recommended_rate', 10);
        $compareResponse->assertJsonPath('requires_residency_certificate', true);

        $this->actingAs($company)->postJson(route('sce.tax.withholding.treaty-rates.store'), [
            'country_code' => 'PT',
            'income_type' => 'services',
            'treaty_rate' => 10,
        ])->assertForbidden();
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
