<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Services\BankTransactionsService;

class BankStatementImportReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_csv_creates_transactions_and_ignores_duplicates(): void
    {
        $company = $this->makeCompany();
        $bankAccount = $this->makeBankAccount($company);

        $csvPath = tempnam(sys_get_temp_dir(), 'bank-csv-');
        file_put_contents($csvPath, implode("\n", [
            'transaction_date,transaction_type,reference_number,description,amount,running_balance,transaction_status',
            '2026-04-01,credit,CP-2026-04-001,Customer payment,1000.00,1000.00,cleared',
            '2026-04-01,credit,CP-2026-04-001,Customer payment,1000.00,1000.00,cleared',
            '2026-04-02,debit,VP-2026-04-002,Vendor payment,200.00,800.00,cleared',
        ]));

        $result = app(BankTransactionsService::class)->importBankStatementCsv(
            $csvPath,
            $bankAccount->id,
            $company->id
        );

        @unlink($csvPath);

        $this->assertSame(2, $result['created']);
        $this->assertSame(1, $result['duplicates']);
        $this->assertSame(0, $result['errors']);
        $this->assertDatabaseCount('bank_transactions', 2);
    }

    public function test_auto_reconcile_marks_matching_transactions(): void
    {
        $company = $this->makeCompany();
        $bankAccount = $this->makeBankAccount($company);
        $customer = $this->makeClient($company);

        CustomerPayment::create([
            'payment_number' => 'CP-2026-04-010',
            'payment_date' => '2026-04-10',
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'reference_number' => 'CP-2026-04-010',
            'payment_amount' => 1500,
            'status' => 'cleared',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        BankTransaction::create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => '2026-04-10',
            'transaction_type' => 'credit',
            'reference_number' => 'CP-2026-04-010',
            'description' => 'Imported statement row',
            'amount' => 1500,
            'running_balance' => 1500,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'unreconciled',
            'created_by' => $company->id,
        ]);

        $result = app(BankTransactionsService::class)->autoReconcileImportedTransactions($company->id);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['reconciled']);
        $this->assertSame(0, $result['unmatched']);

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'reference_number' => 'CP-2026-04-010',
            'reconciliation_status' => 'reconciled',
        ]);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeClient(User $company): User
    {
        return User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeBankAccount(User $company): BankAccount
    {
        return BankAccount::create([
            'account_number' => '001-TEST',
            'account_name' => 'Conta Teste',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}

