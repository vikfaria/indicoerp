<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Models\JournalEntry;

class JournalEntryNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_numbers_increment_within_a_company_and_can_repeat_across_companies(): void
    {
        $companyA = $this->makeCompany('Empresa A');
        $companyB = $this->makeCompany('Empresa B');

        $first = $this->createJournalEntry($companyA, 'Sales Invoice #1');
        $second = $this->createJournalEntry($companyA, 'Sales Invoice #2');
        $foreign = $this->createJournalEntry($companyB, 'Sales Invoice #3');

        $this->assertSame('JE/2026/00001', $first->journal_number);
        $this->assertSame('JE/2026/00002', $second->journal_number);
        $this->assertSame('JE/2026/00001', $foreign->journal_number);

        $this->assertDatabaseCount('journal_entries', 3);
    }

    private function createJournalEntry(User $company, string $description): JournalEntry
    {
        return JournalEntry::query()->create([
            'journal_date' => '2026-06-19',
            'entry_type' => 'automatic',
            'reference_type' => 'sales_invoice',
            'reference_id' => null,
            'description' => $description,
            'total_debit' => 2610,
            'total_credit' => 2610,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeCompany(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }
}
