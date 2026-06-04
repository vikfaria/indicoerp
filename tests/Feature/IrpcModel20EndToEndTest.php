<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\FiscalExportHistory;
use App\Models\IrpcConfiguration;
use App\Models\TaxAdjustment;
use App\Models\User;
use App\Services\IrpcCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

class IrpcModel20EndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_irpc_formula_model20_support_and_annual_declaration_exports_are_consistent(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        AccountUtility::defaultdata($company->id);

        $cashAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1000')
            ->firstOrFail();

        $revenueAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '4100')
            ->firstOrFail();

        $expenseAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '5200')
            ->firstOrFail();

        $taxExpenseType = AccountType::query()
            ->where('created_by', $company->id)
            ->where('code', 'TE')
            ->firstOrFail();

        $assetType = AccountType::query()
            ->where('created_by', $company->id)
            ->where('code', 'OA')
            ->firstOrFail();

        $taxPaymentAccount = ChartOfAccount::query()->create([
            'account_code' => '8501',
            'account_name' => 'IRPC do exercício',
            'account_type_id' => $taxExpenseType->id,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'pgc_class' => 8,
            'financial_statement_line' => 'tax_expense',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $withholdingAssetAccount = ChartOfAccount::query()->create([
            'account_code' => '2490',
            'account_name' => 'Retenção IRPC sofrida',
            'account_type_id' => $assetType->id,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'pgc_class' => 2,
            'financial_statement_line' => 'tax_receivables',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $revenueAccount->update(['modelo20_line' => 'M20-REVENUE']);
        $expenseAccount->update(['modelo20_line' => 'M20-EXPENSE']);

        IrpcConfiguration::query()->create([
            'company_id' => $company->id,
            'fiscal_year' => '2026',
            'standard_rate' => 25.00,
            'reduced_rate' => null,
            'regime' => 'normal',
            'payment_on_account_rate' => 80.00,
            'is_first_year' => false,
            'fiscal_incentives' => null,
            'created_by' => $company->id,
        ]);

        TaxAdjustment::query()->create([
            'company_id' => $company->id,
            'fiscal_year' => '2026',
            'type' => 'add_back',
            'category' => 'fines_penalties',
            'description' => 'Multa não dedutível',
            'amount' => 150.00,
            'legal_basis' => 'Art. 34 CIRPC',
            'created_by' => $company->id,
        ]);

        TaxAdjustment::query()->create([
            'company_id' => $company->id,
            'fiscal_year' => '2026',
            'type' => 'deduction',
            'category' => 'previous_losses',
            'description' => 'Prejuízo fiscal reportado',
            'amount' => 50.00,
            'legal_basis' => 'Art. 37 CIRPC',
            'created_by' => $company->id,
        ]);

        $this->postBalancedJournal(
            company: $company,
            journalNumber: 'IRPC-REV-2026-001',
            journalDate: '2026-06-15',
            referenceType: 'sales_invoice',
            description: 'Reconhecimento de receita para IRPC',
            lines: [
                ['account_id' => $cashAccount->id, 'description' => 'Cobrança da venda', 'debit' => 1000.00, 'credit' => 0.00],
                ['account_id' => $revenueAccount->id, 'description' => 'Receita do exercício', 'debit' => 0.00, 'credit' => 1000.00],
            ]
        );

        $this->postBalancedJournal(
            company: $company,
            journalNumber: 'IRPC-EXP-2026-001',
            journalDate: '2026-06-20',
            referenceType: 'manual',
            description: 'Reconhecimento de gasto para IRPC',
            lines: [
                ['account_id' => $expenseAccount->id, 'description' => 'Despesa fiscal', 'debit' => 200.00, 'credit' => 0.00],
                ['account_id' => $cashAccount->id, 'description' => 'Pagamento da despesa', 'debit' => 0.00, 'credit' => 200.00],
            ]
        );

        $this->postBalancedJournal(
            company: $company,
            journalNumber: 'IRPC-WHT-2026-001',
            journalDate: '2026-07-05',
            referenceType: 'manual',
            description: 'IRPC retido na fonte',
            lines: [
                ['account_id' => $withholdingAssetAccount->id, 'description' => 'IRPC retido', 'debit' => 50.00, 'credit' => 0.00],
                ['account_id' => $cashAccount->id, 'description' => 'Liquidação bancária', 'debit' => 0.00, 'credit' => 50.00],
            ]
        );

        $this->postBalancedJournal(
            company: $company,
            journalNumber: 'IRPC-PPC-2025-001',
            journalDate: '2025-12-30',
            referenceType: 'manual',
            description: 'Pagamento por conta do exercício anterior',
            lines: [
                ['account_id' => $taxPaymentAccount->id, 'description' => 'Pagamento por conta IRPC', 'debit' => 100.00, 'credit' => 0.00],
                ['account_id' => $cashAccount->id, 'description' => 'Saída de caixa', 'debit' => 0.00, 'credit' => 100.00],
            ]
        );

        $result = app(IrpcCalculationService::class)->calculate($company->id, '2026');

        $this->assertSame(800.00, (float) $result['accounting_result']);
        $this->assertSame(150.00, (float) $result['add_backs']);
        $this->assertSame(50.00, (float) $result['deductions']);
        $this->assertSame(900.00, (float) $result['taxable_income']);
        $this->assertSame(25.00, (float) $result['rate']);
        $this->assertSame(225.00, (float) $result['gross_tax']);
        $this->assertSame(80.00, (float) data_get($result, 'payments_on_account.total'));
        $this->assertSame(26.67, (float) data_get($result, 'payments_on_account.may'));
        $this->assertSame(26.67, (float) data_get($result, 'payments_on_account.july'));
        $this->assertSame(26.66, (float) data_get($result, 'payments_on_account.september'));
        $this->assertSame(50.00, (float) $result['withholdings_suffered']);
        $this->assertSame(95.00, (float) $result['net_tax_payable']);
        $this->assertSame(0.00, (float) $result['net_tax_recoverable']);
        $this->assertSame(95.00, (float) $result['net_payable']);

        $irpcResponse = $this->actingAs($company)->get(route('sce.tax.irpc.export', ['year' => '2026']));

        $irpcResponse->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $irpcResponse->headers->get('content-type'));
        $this->assertStringContainsString('"calculation","taxable_income","900.00"', $irpcResponse->getContent());
        $this->assertStringContainsString('"calculation","gross_tax","225.00"', $irpcResponse->getContent());
        $this->assertStringContainsString('"calculation","payments_on_account_total","80.00"', $irpcResponse->getContent());
        $this->assertStringContainsString('"calculation","withholdings_suffered","50.00"', $irpcResponse->getContent());
        $this->assertStringContainsString('"calculation","net_tax_payable","95.00"', $irpcResponse->getContent());

        $irpcHistory = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'irpc_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($irpcHistory);
        $this->assertSame('irpc-guide-2026.csv', $irpcHistory->file_name);
        $this->assertSame(hash('sha256', $irpcResponse->getContent()), $irpcHistory->file_hash);
        $this->assertTrue(Storage::disk('local')->exists($irpcHistory->file_path));
        $this->assertSame('sce.tax.irpc.export', data_get($irpcHistory->metadata, 'source'));

        $model20Response = $this->actingAs($company)->get(route('sce.tax.modelo20', ['year' => '2026']));

        $model20Response->assertOk();
        $model20Response->assertJsonPath('fiscal_year', '2026');
        $model20Response->assertJsonPath('lines.0.model20_line', 'M20-EXPENSE');
        $model20Response->assertJsonPath('lines.1.model20_line', 'M20-REVENUE');
        $model20Response->assertJsonPath('totals.debit', 200);
        $model20Response->assertJsonPath('totals.credit', 1000);
        $model20Response->assertJsonPath('totals.net', -800);
        $model20Response->assertJsonPath('totals.mapped_movements', 2);
        $model20Response->assertJsonPath('totals.unmapped_movements', 4);
        $this->assertNotEmpty(data_get($model20Response->json(), 'warnings'));

        $annualResponse = $this->actingAs($company)->get(route('sce.tax.annual-declaration', ['year' => '2026']));

        $annualResponse->assertOk();
        $annualResponse->assertJsonPath('fiscal_year', '2026');
        $annualResponse->assertJsonPath('irpc.taxable_income', 900);
        $annualResponse->assertJsonPath('irpc.net_tax_payable', 95);
        $annualResponse->assertJsonPath('model20.totals.mapped_movements', 2);
        $this->assertNotEmpty(data_get($annualResponse->json(), 'model20.warnings'));

        $annualExport = $this->actingAs($company)->get(route('sce.tax.annual-declaration.export', ['year' => '2026']));

        $annualExport->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $annualExport->headers->get('content-type'));
        $this->assertStringContainsString('"irpc","accounting_result","800.00"', $annualExport->getContent());
        $this->assertStringContainsString('"irpc","taxable_income","900.00"', $annualExport->getContent());
        $this->assertStringContainsString('"irpc","net_payable","95.00"', $annualExport->getContent());
        $this->assertStringContainsString('"model20","warnings"', $annualExport->getContent());
        $this->assertStringNotContainsString('"model20","warnings",""', $annualExport->getContent());

        $annualHistory = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'annual_declaration_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($annualHistory);
        $this->assertSame('annual-declaration-2026.csv', $annualHistory->file_name);
        $this->assertSame(hash('sha256', $annualExport->getContent()), $annualHistory->file_hash);
        $this->assertTrue(Storage::disk('local')->exists($annualHistory->file_path));
        $this->assertSame('sce.tax.annual-declaration.export', data_get($annualHistory->metadata, 'source'));

        if ($irpcHistory?->file_path) {
            Storage::disk('local')->delete($irpcHistory->file_path);
        }

        if ($annualHistory?->file_path) {
            Storage::disk('local')->delete($annualHistory->file_path);
        }
    }

    private function postBalancedJournal(
        User $company,
        string $journalNumber,
        string $journalDate,
        string $referenceType,
        string $description,
        array $lines
    ): JournalEntry {
        $journalEntry = JournalEntry::query()->create([
            'journal_number' => $journalNumber,
            'journal_date' => $journalDate,
            'entry_type' => 'manual',
            'reference_type' => $referenceType,
            'reference_id' => null,
            'description' => $description,
            'total_debit' => collect($lines)->sum('debit'),
            'total_credit' => collect($lines)->sum('credit'),
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        foreach ($lines as $line) {
            JournalEntryItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'debit_amount' => $line['debit'],
                'credit_amount' => $line['credit'],
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        return $journalEntry;
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
    }
}
