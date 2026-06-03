<?php

namespace App\Console\Commands;

use App\Models\CompanyFiscalProfile;
use App\Services\MozambiqueFiscalComplianceAlertService;
use Illuminate\Console\Command;

class SyncFiscalComplianceAlerts extends Command
{
    protected $signature = 'sce:sync-fiscal-compliance-alerts
                            {--company_id= : Restrict sync to one company ID}
                            {--from_date= : Start date for the compliance snapshot window}
                            {--to_date= : End date for the compliance snapshot window}
                            {--due_soon_days=7 : Compliance deadline window in days}
                            {--continue-on-error : Return success even if one or more companies fail}';

    protected $description = 'Sync Mozambique fiscal compliance alerts for one or all companies.';

    public function handle(MozambiqueFiscalComplianceAlertService $complianceAlertService): int
    {
        $companyIdOption = $this->option('company_id');
        $companyId = is_numeric($companyIdOption) ? (int) $companyIdOption : null;
        $fromDate = (string) ($this->option('from_date') ?: now()->startOfYear()->toDateString());
        $toDate = (string) ($this->option('to_date') ?: now()->endOfYear()->toDateString());
        $dueSoonDays = max(1, min(30, (int) $this->option('due_soon_days')));

        $profiles = CompanyFiscalProfile::query()
            ->when($companyId !== null && $companyId > 0, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('company_id')
            ->get(['company_id', 'legal_name']);

        if ($profiles->isEmpty()) {
            $this->info('No company fiscal profiles found for fiscal compliance sync.');

            return self::SUCCESS;
        }

        $syncedCompanies = 0;
        $failedCompanies = 0;
        $openAlertsTotal = 0;
        $criticalAlertsTotal = 0;

        foreach ($profiles as $profile) {
            try {
                $state = $complianceAlertService->syncFromReport((int) $profile->company_id, [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'due_soon_days' => $dueSoonDays,
                ]);

                $openAlerts = (int) data_get($state, 'metrics.open_alerts', 0);
                $criticalAlerts = (int) data_get($state, 'metrics.open_critical_alerts', 0);

                $syncedCompanies++;
                $openAlertsTotal += $openAlerts;
                $criticalAlertsTotal += $criticalAlerts;

                $this->line(sprintf(
                    'Company #%d (%s): open alerts=%d, critical=%d',
                    (int) $profile->company_id,
                    (string) ($profile->legal_name ?: 'n/a'),
                    $openAlerts,
                    $criticalAlerts
                ));
            } catch (\Throwable $exception) {
                $failedCompanies++;
                $this->error(sprintf(
                    'Company #%d (%s): sync failed - %s',
                    (int) $profile->company_id,
                    (string) ($profile->legal_name ?: 'n/a'),
                    $exception->getMessage()
                ));
            }
        }

        $this->info(sprintf(
            'Fiscal compliance sync summary: synced=%d, failed=%d, aggregate_open_alerts=%d, aggregate_critical=%d.',
            $syncedCompanies,
            $failedCompanies,
            $openAlertsTotal,
            $criticalAlertsTotal
        ));

        if ($failedCompanies > 0 && !$this->option('continue-on-error')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
