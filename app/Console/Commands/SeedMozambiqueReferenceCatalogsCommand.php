<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Workdo\Contract\Models\ContractUtility;
use Workdo\Hrm\Models\HrmModel;

class SeedMozambiqueReferenceCatalogsCommand extends Command
{
    protected $signature = 'mozambique:seed-reference-catalogs
                            {--company= : Company user ID to seed}
                            {--force : Seed catalogs even when the module is not active}';

    protected $description = 'Seed Mozambique HRM and Contract reference catalogs for one or all companies.';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $force = (bool) $this->option('force');

        if ($companyId !== null && $companyId !== '') {
            $targetCompany = User::query()->where('id', (int) $companyId)->first(['id', 'email', 'name', 'type']);

            if (!$targetCompany) {
                $this->error(sprintf('Company #%d not found.', (int) $companyId));
                return self::FAILURE;
            }

            if ($targetCompany->type !== 'company') {
                $this->error(sprintf(
                    'User #%d exists but is not type company (type=%s).',
                    (int) $targetCompany->id,
                    (string) $targetCompany->type
                ));
                return self::FAILURE;
            }
        }

        $companiesQuery = User::query()
            ->where('type', 'company')
            ->orderBy('id');

        if ($companyId !== null && $companyId !== '') {
            $companiesQuery->where('id', (int) $companyId);
        }

        $companies = $companiesQuery->get(['id', 'email', 'name']);

        if ($companies->isEmpty()) {
            $this->info('No company users found for reference catalog seeding.');
            return self::SUCCESS;
        }

        $seededCompanies = 0;
        $hrmSeeded = 0;
        $contractSeeded = 0;

        foreach ($companies as $company) {
            $companySeeded = false;
            $companyId = (int) $company->id;

            if ($force || Module_is_active('Hrm', $companyId)) {
                HrmModel::defaultdata($companyId);
                $hrmSeeded++;
                $companySeeded = true;
                $this->line(sprintf(
                    'Company #%d (%s): HRM defaults seeded.',
                    $companyId,
                    (string) $company->email
                ));
            }

            if ($force || Module_is_active('Contract', $companyId)) {
                ContractUtility::defaultdata($companyId);
                $contractSeeded++;
                $companySeeded = true;
                $this->line(sprintf(
                    'Company #%d (%s): Contract defaults seeded.',
                    $companyId,
                    (string) $company->email
                ));
            }

            if ($companySeeded) {
                $seededCompanies++;
            } else {
                $this->warn(sprintf(
                    'Company #%d (%s): skipped (no active HRM/Contract module). Use --force to override.',
                    $companyId,
                    (string) $company->email
                ));
            }
        }

        $this->info(sprintf(
            'Reference catalog seeding completed: companies=%d, hrm=%d, contract=%d.',
            $seededCompanies,
            $hrmSeeded,
            $contractSeeded
        ));

        return self::SUCCESS;
    }
}
