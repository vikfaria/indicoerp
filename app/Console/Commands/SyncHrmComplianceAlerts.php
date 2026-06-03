<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MozambiqueHrComplianceAlertService;
use Illuminate\Console\Command;

class SyncHrmComplianceAlerts extends Command
{
    protected $signature = 'hrm:sync-compliance-alerts
                            {--company_id= : Restrict sync to one company user ID}
                            {--continue-on-error : Return success even if one or more companies fail}';

    protected $description = 'Sync Mozambique HR compliance alerts for one or all companies.';

    public function handle(MozambiqueHrComplianceAlertService $complianceAlertService): int
    {
        $companyIdOption = $this->option('company_id');
        $companyId = is_numeric($companyIdOption) ? (int) $companyIdOption : null;

        $companiesQuery = User::query()
            ->where('type', 'company')
            ->orderBy('id');

        if ($companyId !== null && $companyId > 0) {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get(['id', 'email']);

        if ($companies->isEmpty()) {
            $this->info('No company users found for HR compliance sync.');

            return self::SUCCESS;
        }

        $syncedCompanies = 0;
        $failedCompanies = 0;
        $openAlertsTotal = 0;

        foreach ($companies as $company) {
            try {
                $state = $complianceAlertService->syncFromSnapshot((int) $company->id);
                $openAlerts = (int) data_get($state, 'metrics.open_alerts', 0);
                $highRiskAlerts = (int) data_get($state, 'metrics.open_high_alerts', 0);

                $syncedCompanies++;
                $openAlertsTotal += $openAlerts;

                $this->line(sprintf(
                    'Company #%d (%s): open alerts=%d, high risk=%d',
                    (int) $company->id,
                    (string) $company->email,
                    $openAlerts,
                    $highRiskAlerts
                ));
            } catch (\Throwable $exception) {
                $failedCompanies++;
                $this->error(sprintf(
                    'Company #%d (%s): sync failed - %s',
                    (int) $company->id,
                    (string) $company->email,
                    $exception->getMessage()
                ));
            }
        }

        $this->info(sprintf(
            'HR compliance sync summary: synced=%d, failed=%d, aggregate_open_alerts=%d.',
            $syncedCompanies,
            $failedCompanies,
            $openAlertsTotal
        ));

        if ($failedCompanies > 0 && !$this->option('continue-on-error')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
