<?php

namespace App\Console\Commands;

use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCalendarEvent;
use Illuminate\Console\Command;

class SyncFiscalCalendar extends Command
{
    protected $signature = 'sce:sync-fiscal-calendar
                            {--company_id= : Restrict sync to one company ID}
                            {--year= : Start fiscal year for the sync window}
                            {--years=2 : Number of consecutive years to sync}';

    protected $description = 'Generate and refresh fiscal calendar events for companies with a fiscal profile.';

    public function handle(): int
    {
        $companyIdOption = $this->option('company_id');
        $companyId = is_numeric($companyIdOption) ? (int) $companyIdOption : null;
        $startYear = (int) ($this->option('year') ?: date('Y'));
        $years = max(1, (int) $this->option('years'));

        $profiles = CompanyFiscalProfile::query()
            ->when($companyId !== null && $companyId > 0, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('company_id')
            ->get(['company_id', 'legal_name']);

        if ($profiles->isEmpty()) {
            $this->info('No company fiscal profiles found for calendar sync.');

            return self::SUCCESS;
        }

        $syncedCompanies = 0;
        $generatedEvents = 0;

        foreach ($profiles as $profile) {
            $syncedCompanies++;
            $companyId = (int) $profile->company_id;

            for ($year = $startYear; $year < ($startYear + $years); $year++) {
                FiscalCalendarEvent::generateForYear($companyId, $year);
                $count = FiscalCalendarEvent::query()
                    ->where('company_id', $companyId)
                    ->whereYear('due_date', $year)
                    ->count();

                $generatedEvents += $count;

                $this->line(sprintf(
                    'Company #%d (%s): fiscal calendar synced for %d (%d events).',
                    $companyId,
                    (string) ($profile->legal_name ?: 'n/a'),
                    $year,
                    $count
                ));
            }
        }

        $this->info(sprintf(
            'Fiscal calendar sync completed: companies=%d, events=%d.',
            $syncedCompanies,
            $generatedEvents
        ));

        return self::SUCCESS;
    }
}
