<?php

namespace App\Console\Commands;

use App\Models\RecurringJournalTemplate;
use App\Services\JournalNumberingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

class ProcessRecurringJournals extends Command
{
    protected $signature = 'accounting:process-recurring-journals';
    protected $description = 'Process due recurring journal entry templates';

    public function handle(JournalNumberingService $numberingService): int
    {
        $templates = RecurringJournalTemplate::where('is_active', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->get();

        if ($templates->isEmpty()) {
            $this->info('Nenhum lançamento recorrente pendente.');
            return self::SUCCESS;
        }

        $processed = 0;
        $errors = 0;

        foreach ($templates as $template) {
            try {
                DB::transaction(function () use ($template, $numberingService, &$processed) {
                    $journalDate = $template->next_run_date->toDateString();

                    // Generate number
                    $numbering = $numberingService->generateNumber(
                        $template->company_id,
                        $journalDate,
                        $template->accounting_journal_id
                    );

                    // Decode template items
                    $items = $template->template_items;
                    if (!is_array($items) || empty($items)) {
                        return;
                    }

                    $totalDebit = collect($items)->sum('debit');
                    $totalCredit = collect($items)->sum('credit');

                    // Create journal entry
                    $status = $template->requires_approval ? 'draft' : 'posted';

                    $entry = JournalEntry::create([
                        'journal_number' => $numbering['journal_number'],
                        'journal_date' => $journalDate,
                        'entry_type' => 'automatic',
                        'reference_type' => 'recurring',
                        'reference_id' => $template->id,
                        'description' => $template->name . ' (recorrente)',
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'status' => $status,
                        'accounting_journal_id' => $template->accounting_journal_id,
                        'accounting_period_id' => $numbering['accounting_period_id'],
                        'fiscal_year' => $numbering['fiscal_year'],
                        'period_number' => $numbering['period_number'],
                        'creator_id' => $template->created_by,
                        'created_by' => $template->company_id,
                    ]);

                    foreach ($items as $item) {
                        JournalEntryItem::create([
                            'journal_entry_id' => $entry->id,
                            'account_id' => $item['account_id'],
                            'description' => $item['description'] ?? $template->name,
                            'debit_amount' => $item['debit'] ?? 0,
                            'credit_amount' => $item['credit'] ?? 0,
                            'creator_id' => $template->created_by,
                            'created_by' => $template->company_id,
                        ]);
                    }

                    // Update template
                    $template->update([
                        'last_run_date' => $journalDate,
                        'next_run_date' => $template->calculateNextRunDate(),
                        'executions_count' => $template->executions_count + 1,
                    ]);

                    $processed++;
                });
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Recurring journal error', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Erro template #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processados: {$processed}, Erros: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
