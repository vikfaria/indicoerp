<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use App\Services\PgcImportService;
use Database\Seeders\PgcNirfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

class FinancialStatementsEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_financial_statements_are_coherent_and_statement_of_changes_in_equity_is_available(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->seedOfficialCatalog();
        AccountUtility::defaultdata($company->id);

        app(PgcImportService::class)->importForCompany($company->id, 'pgc_nirf');

        $this->postJournal($company, '2026-01-05', 'equity_contribution', 'Capital contribution', [
            ['account_code' => '11', 'debit' => 10000.00, 'credit' => 0.00, 'description' => 'Cash contribution'],
            ['account_code' => '51', 'debit' => 0.00, 'credit' => 10000.00, 'description' => 'Share capital'],
        ]);

        $this->postJournal($company, '2026-02-10', 'sales_invoice', 'Credit sale', [
            ['account_code' => '211', 'debit' => 5000.00, 'credit' => 0.00, 'description' => 'Customer receivable'],
            ['account_code' => '71', 'debit' => 0.00, 'credit' => 5000.00, 'description' => 'Sales revenue'],
        ]);

        $this->postJournal($company, '2026-02-15', 'customer_payment', 'Customer settlement', [
            ['account_code' => '11', 'debit' => 5000.00, 'credit' => 0.00, 'description' => 'Cash receipt'],
            ['account_code' => '211', 'debit' => 0.00, 'credit' => 5000.00, 'description' => 'Receivable settlement'],
        ]);

        $this->postJournal($company, '2026-03-10', 'purchase_invoice', 'Operating expense on credit', [
            ['account_code' => '62', 'debit' => 2000.00, 'credit' => 0.00, 'description' => 'Operating expense'],
            ['account_code' => '221', 'debit' => 0.00, 'credit' => 2000.00, 'description' => 'Supplier payable'],
        ]);

        $this->postJournal($company, '2026-03-20', 'vendor_payment', 'Supplier settlement', [
            ['account_code' => '221', 'debit' => 2000.00, 'credit' => 0.00, 'description' => 'Payable settlement'],
            ['account_code' => '11', 'debit' => 0.00, 'credit' => 2000.00, 'description' => 'Cash payment'],
        ]);

        $balanceSheet = $this->actingAs($company)->get(route('sce.reports.balance-sheet', ['date' => '2026-12-31']));
        $balanceSheet->assertOk();
        $balanceSheet->assertInertia(function (Assert $page): void {
            $page->component('Reports/BalanceSheet')
                ->where('data.capital_proprio.capital_social', fn (mixed $value): bool => round((float) $value, 2) === 10000.00)
                ->where('data.capital_proprio.resultado_liquido', fn (mixed $value): bool => round((float) $value, 2) === 3000.00)
                ->where('data.activo.activo_corrente.caixa_bancos', fn (mixed $value): bool => round((float) $value, 2) === 13000.00)
                ->where('data.passivo.passivo_corrente.fornecedores', fn (mixed $value): bool => round((float) $value, 2) === 0.00);
        });

        $incomeStatement = $this->actingAs($company)->get(route('sce.reports.income-statement', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));
        $incomeStatement->assertOk();
        $incomeStatement->assertInertia(function (Assert $page): void {
            $page->component('Reports/IncomeStatement')
                ->where('data.rendimentos.vendas', fn (mixed $value): bool => round((float) $value, 2) === 5000.00)
                ->where('data.gastos.fornecimentos_servicos', fn (mixed $value): bool => round((float) $value, 2) === 2000.00)
                ->where('data.imposto_rendimento', fn (mixed $value): bool => round((float) $value, 2) === 0.00);
        });

        $cashFlow = $this->actingAs($company)->get(route('sce.reports.cash-flow', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));
        $cashFlow->assertOk();
        $cashFlow->assertInertia(function (Assert $page): void {
            $page->component('Reports/CashFlow')
                ->where('data.actividades_operacionais.recebimentos_clientes', fn (mixed $value): bool => round((float) $value, 2) === 5000.00)
                ->where('data.actividades_operacionais.pagamentos_fornecedores', fn (mixed $value): bool => round((float) $value, 2) === -2000.00)
                ->where('data.saldo_final', fn (mixed $value): bool => round((float) $value, 2) === 13000.00);
        });

        $equityChanges = $this->actingAs($company)->get(route('sce.reports.equity-changes', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));
        $equityChanges->assertOk();
        $equityChanges->assertInertia(function (Assert $page): void {
            $page->component('Reports/EquityChanges')
                ->where('data.totals.opening', fn (mixed $value): bool => round((float) $value, 2) === 0.00)
                ->where('data.totals.movement', fn (mixed $value): bool => round((float) $value, 2) === 13000.00)
                ->where('data.totals.closing', fn (mixed $value): bool => round((float) $value, 2) === 13000.00)
                ->where('data.totals.difference', fn (mixed $value): bool => round((float) $value, 2) === 0.00)
                ->where('data.totals.is_balanced', true)
                ->where('data.components.resultado_liquido_exercicio.closing', fn (mixed $value): bool => round((float) $value, 2) === 3000.00)
                ->where('data.notes.0.title', 'Resumo do período')
                ->where('data.notes.2.title', 'Reconciliação');
        });
    }

    private function postJournal(User $company, string $date, string $referenceType, string $description, array $lines): JournalEntry
    {
        $totalDebit = round(array_sum(array_map(static fn (array $line): float => (float) ($line['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(static fn (array $line): float => (float) ($line['credit'] ?? 0), $lines)), 2);

        $journal = JournalEntry::query()->create([
            'journal_date' => $date,
            'entry_type' => 'manual',
            'reference_type' => $referenceType,
            'reference_id' => null,
            'description' => $description,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        foreach ($lines as $line) {
            $account = $this->account($company->id, (string) $line['account_code']);

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

    private function account(int $companyId, string $code): ChartOfAccount
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

    private function seedOfficialCatalog(): void
    {
        $seeder = new PgcNirfSeeder();
        $seeder->run();
    }
}
