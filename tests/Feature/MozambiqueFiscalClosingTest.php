<?php

namespace Tests\Feature;

use App\Models\FiscalExportHistory;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\MozFiscalClosing;

class MozambiqueFiscalClosingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_fiscal_closing_export_returns_csv_with_closings(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        $periodFrom = now()->startOfMonth()->toDateString();
        $periodTo = now()->endOfMonth()->toDateString();

        $closeResponse = $this->actingAs($company)->post(route('account.reports.fiscal-closings.close'), [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'close_reason' => 'Fecho fiscal mensal.',
        ]);

        $closeResponse->assertOk();
        $closeResponse->assertJsonPath('message', 'Fiscal period closed successfully.');
        $closeResponse->assertJsonPath('data.latest_closed_until', $periodTo);
        $closeResponse->assertJsonPath('data.closings.0.period_from', $periodFrom);
        $closeResponse->assertJsonPath('data.closings.0.period_to', $periodTo);
        $closeResponse->assertJsonPath('data.closings.0.status', 'closed');

        $closing = MozFiscalClosing::query()
            ->where('created_by', $company->id)
            ->where('status', 'closed')
            ->whereDate('period_from', $periodFrom)
            ->whereDate('period_to', $periodTo)
            ->latest('id')
            ->first();

        $this->assertNotNull($closing);
        $this->assertSame('Fecho fiscal mensal.', (string) $closing->close_reason);

        $response = $this->actingAs($company)->get(route('account.reports.fiscal-closings.export'));

        $response->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringStartsWith(
            'attachment; filename="mozambique-fiscal-closings-',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('"summary","closings_count","1"', $response->getContent());
        $this->assertStringContainsString('"closings","' . $periodFrom . ' - ' . $periodTo . '"', $response->getContent());
        $this->assertStringContainsString('Fecho fiscal mensal.', $response->getContent());

        $history = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'fiscal_closings_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringStartsWith('mozambique-fiscal-closings-', (string) $history->file_name);
        $this->assertSame($periodFrom, optional($history->period_start)->toDateString());
        $this->assertSame($periodTo, optional($history->period_end)->toDateString());
        $this->assertSame('generated', (string) $history->status);
    }

    public function test_monthly_vat_close_snapshot_captures_vat_totals(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        AccountUtility::defaultdata($company->id);

        $outputVatAccountId = $this->ensureChartAccount(
            companyId: $company->id,
            accountCode: '2433',
            accountName: 'IVA Liquidado',
            accountTypeCode: 'CL',
            normalBalance: 'credit',
            taxType: 'vat_output',
        );

        $inputVatAccountId = $this->ensureChartAccount(
            companyId: $company->id,
            accountCode: '2432',
            accountName: 'IVA Dedutível',
            accountTypeCode: 'OA',
            normalBalance: 'debit',
            taxType: 'vat_input',
        );

        $salesInvoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_number' => 'FT-CLOSE-001',
            'invoice_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(7)->toDateString(),
            'customer_id' => $company->id,
            'subtotal' => 1000,
            'tax_amount' => 160,
            'discount_amount' => 0,
            'total_amount' => 1160,
            'paid_amount' => 0,
            'balance_amount' => 1160,
            'status' => 'posted',
            'type' => 'product',
            'payment_terms' => null,
            'notes' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseInvoiceId = DB::table('purchase_invoices')->insertGetId([
            'invoice_number' => 'FC-CLOSE-001',
            'invoice_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(10)->toDateString(),
            'vendor_id' => $company->id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'discount_amount' => 0,
            'total_amount' => 580,
            'paid_amount' => 0,
            'balance_amount' => 580,
            'status' => 'posted',
            'payment_terms' => null,
            'notes' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalDate = now()->startOfMonth()->addDays(8)->toDateString();

        $this->insertJournalEntry(
            companyId: $company->id,
            journalNumber: 'JV-VAT-OUT-001',
            journalDate: $journalDate,
            referenceType: 'sales_invoice',
            referenceId: $salesInvoiceId,
            description: 'IVA liquidado do periodo.',
            debitAccountId: $this->getChartAccountId($company->id, '1100'),
            creditAccountId: $outputVatAccountId,
            amount: 144.00,
        );

        $this->insertJournalEntry(
            companyId: $company->id,
            journalNumber: 'JV-VAT-IN-001',
            journalDate: $journalDate,
            referenceType: 'purchase_invoice',
            referenceId: $purchaseInvoiceId,
            description: 'IVA dedutivel do periodo.',
            debitAccountId: $inputVatAccountId,
            creditAccountId: $this->getChartAccountId($company->id, '2000'),
            amount: 72.00,
        );

        DB::table('credit_notes')->insert([
            'credit_note_number' => 'NC-CLOSE-001',
            'credit_note_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'customer_id' => $company->id,
            'invoice_id' => $salesInvoiceId,
            'reason' => 'Ajuste IVA',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'applied_amount' => 0,
            'balance_amount' => 116,
            'notes' => null,
            'approved_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('debit_notes')->insert([
            'debit_note_number' => 'ND-CLOSE-001',
            'debit_note_date' => now()->startOfMonth()->addDays(6)->toDateString(),
            'vendor_id' => $company->id,
            'invoice_id' => $purchaseInvoiceId,
            'reason' => 'Ajuste IVA',
            'status' => 'approved',
            'subtotal' => 50,
            'tax_amount' => 8,
            'discount_amount' => 0,
            'total_amount' => 58,
            'applied_amount' => 0,
            'balance_amount' => 58,
            'notes' => null,
            'approved_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $periodFrom = now()->startOfMonth()->toDateString();
        $periodTo = now()->endOfMonth()->toDateString();

        $closeResponse = $this->actingAs($company)->post(route('account.reports.fiscal-closings.close'), [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'close_reason' => 'Fecho mensal de IVA.',
        ]);

        $closeResponse->assertOk();

        $closing = MozFiscalClosing::query()
            ->where('created_by', $company->id)
            ->where('status', 'closed')
            ->whereDate('period_from', $periodFrom)
            ->whereDate('period_to', $periodTo)
            ->latest('id')
            ->first();

        $this->assertNotNull($closing);
        $this->assertSame(72.0, (float) data_get($closing->snapshot, 'tax_summary.net_tax_liability'));
        $this->assertSame(72.0, (float) data_get($closing->snapshot, 'mozambique_fiscal_map.vat.net_vat_payable'));
        $this->assertSame('Fecho mensal de IVA.', (string) $closing->close_reason);
    }

    private function ensureChartAccount(
        int $companyId,
        string $accountCode,
        string $accountName,
        string $accountTypeCode,
        string $normalBalance,
        ?string $taxType = null,
    ): int {
        $existing = DB::table('chart_of_accounts')
            ->where('created_by', $companyId)
            ->where('account_code', $accountCode)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $accountTypeId = (int) DB::table('account_types')
            ->where('created_by', $companyId)
            ->where('code', $accountTypeCode)
            ->value('id');

        $this->assertNotSame(0, $accountTypeId, "Missing account type {$accountTypeCode} for company {$companyId}");

        return (int) DB::table('chart_of_accounts')->insertGetId([
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'account_type_id' => $accountTypeId,
            'parent_account_id' => null,
            'level' => 2,
            'normal_balance' => $normalBalance,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => true,
            'is_movement_account' => true,
            'pgc_class' => null,
            'tax_type' => $taxType,
            'vat_code' => null,
            'deductibility' => null,
            'financial_statement_line' => null,
            'modelo20_line' => null,
            'saft_taxonomy_code' => null,
            'cost_center_required' => false,
            'accounting_framework' => 'pgc_nirf',
            'description' => null,
            'creator_id' => $companyId,
            'created_by' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getChartAccountId(int $companyId, string $accountCode): int
    {
        $accountId = DB::table('chart_of_accounts')
            ->where('created_by', $companyId)
            ->where('account_code', $accountCode)
            ->value('id');

        $this->assertNotNull($accountId, "Missing chart account {$accountCode} for company {$companyId}");

        return (int) $accountId;
    }

    private function insertJournalEntry(
        int $companyId,
        string $journalNumber,
        string $journalDate,
        string $referenceType,
        int $referenceId,
        string $description,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
    ): void {
        $entryId = DB::table('journal_entries')->insertGetId([
            'journal_number' => $journalNumber,
            'journal_date' => $journalDate,
            'entry_type' => 'manual',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'creator_id' => $companyId,
            'created_by' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_items')->insert([
            [
                'journal_entry_id' => $entryId,
                'account_id' => $debitAccountId,
                'description' => $description . ' - debit',
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'creator_id' => $companyId,
                'created_by' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $entryId,
                'account_id' => $creditAccountId,
                'description' => $description . ' - credit',
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'creator_id' => $companyId,
                'created_by' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
