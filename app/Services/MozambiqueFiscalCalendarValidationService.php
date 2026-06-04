<?php

namespace App\Services;

use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCalendarEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MozambiqueFiscalCalendarValidationService
{
    /**
     * @return array{company_id:int,generated_at:string,overall_status:string,summary:array<string,int>,checks:array<int,array<string,mixed>>,window:array<string,mixed>}
     */
    public function validate(int $companyId, ?int $baseYear = null): array
    {
        $baseYear = $baseYear ?? (int) now()->year;
        $years = [$baseYear, $baseYear + 1];
        $checks = [];
        $summary = [
            'pass' => 0,
            'warn' => 0,
            'fail' => 0,
        ];

        $addCheck = function (
            string $code,
            string $label,
            string $status,
            string $details,
            bool $critical = false,
            array $meta = []
        ) use (&$checks, &$summary): void {
            if (!array_key_exists($status, $summary)) {
                return;
            }

            $summary[$status]++;
            $checks[] = [
                'code' => $code,
                'label' => $label,
                'status' => $status,
                'critical' => $critical,
                'details' => $details,
                'meta' => $meta,
            ];
        };

        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        $addCheck(
            'fiscal.calendar.profile',
            'Fiscal profile available for calendar validation',
            $profile !== null ? 'pass' : 'fail',
            $profile !== null
                ? 'An active fiscal profile exists for calendar validation.'
                : 'An active fiscal profile is missing for this company.',
            true,
            [
                'profile_found' => $profile !== null,
                'fiscal_year_start_month' => $profile?->fiscal_year_start_month,
            ]
        );

        $calendarTableAvailable = Schema::hasTable('fiscal_calendar_events');
        if (!$calendarTableAvailable) {
            $addCheck(
                'fiscal.calendar.table',
                'Fiscal calendar storage table',
                'fail',
                'The fiscal_calendar_events table is missing.',
                true
            );
        } else {
            $addCheck(
                'fiscal.calendar.table',
                'Fiscal calendar storage table',
                'pass',
                'The fiscal_calendar_events table is available.',
                true
            );
        }

        foreach ($years as $year) {
            if (!$calendarTableAvailable) {
                $addCheck(
                    sprintf('fiscal.calendar.coverage.%d', $year),
                    sprintf('Fiscal calendar coverage for %d', $year),
                    'fail',
                    'Calendar validation cannot run because the storage table is missing.',
                    true,
                    ['year' => $year]
                );
                continue;
            }

            $expectedEvents = collect(FiscalCalendarEvent::expectedDefinitionsForYear($year));
            $actualEvents = FiscalCalendarEvent::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($year): void {
                    $query->where('reference_period', (string) $year)
                        ->orWhere('reference_period', 'like', $year . '-%');
                })
                ->orderBy('due_date')
                ->orderBy('code')
                ->get()
                ->keyBy('code');

            $missingCodes = [];
            $mismatched = [];

            foreach ($expectedEvents as $definition) {
                $code = (string) $definition['code'];
                $event = $actualEvents->get($code);

                if ($event === null) {
                    $missingCodes[] = $code;
                    continue;
                }

                $issues = [];
                if ((string) $event->title !== (string) $definition['title']) {
                    $issues[] = 'title';
                }
                if ((string) $event->obligation_type !== (string) $definition['obligation_type']) {
                    $issues[] = 'obligation_type';
                }
                if (optional($event->due_date)->toDateString() !== (string) $definition['due_date']) {
                    $issues[] = 'due_date';
                }
                if ((string) $event->reference_period !== (string) $definition['reference_period']) {
                    $issues[] = 'reference_period';
                }

                if (!empty($issues)) {
                    $mismatched[$code] = $issues;
                }
            }

            $expectedCodes = $expectedEvents->pluck('code')->map(fn ($code) => (string) $code)->all();
            $extraCodes = $actualEvents->keys()
                ->map(fn ($code) => (string) $code)
                ->diff($expectedCodes)
                ->values()
                ->all();

            $status = 'pass';
            $details = sprintf(
                'Fiscal calendar for %d contains %d expected obligations.',
                $year,
                $expectedEvents->count()
            );

            if (!empty($missingCodes) || !empty($mismatched)) {
                $status = 'fail';
                $details = sprintf(
                    'Fiscal calendar for %d is incomplete: %d missing code(s) and %d mismatched obligation(s).',
                    $year,
                    count($missingCodes),
                    count($mismatched)
                );
            } elseif (!empty($extraCodes)) {
                $status = 'warn';
                $details = sprintf(
                    'Fiscal calendar for %d has %d extra custom obligation(s) beyond the standard schedule.',
                    $year,
                    count($extraCodes)
                );
            }

            $addCheck(
                sprintf('fiscal.calendar.coverage.%d', $year),
                sprintf('Fiscal calendar coverage for %d', $year),
                $status,
                $details,
                true,
                [
                    'year' => $year,
                    'expected_count' => $expectedEvents->count(),
                    'actual_count' => $actualEvents->count(),
                    'missing_codes' => $missingCodes,
                    'mismatched' => $mismatched,
                    'extra_codes' => $extraCodes,
                ]
            );
        }

        $routesReady = Route::has('sce.fiscal.calendar')
            && Route::has('sce.fiscal.generate-calendar')
            && Route::has('sce.fiscal.complete-event')
            && Route::has('sce.fiscal.calendar.export');

        $addCheck(
            'fiscal.calendar.routes',
            'Fiscal calendar routes and export',
            $routesReady ? 'pass' : 'fail',
            $routesReady
                ? 'Fiscal calendar view, generation, completion and export routes are available.'
                : 'One or more fiscal calendar routes are missing.',
            true,
            [
                'calendar' => Route::has('sce.fiscal.calendar'),
                'generate' => Route::has('sce.fiscal.generate-calendar'),
                'complete' => Route::has('sce.fiscal.complete-event'),
                'export' => Route::has('sce.fiscal.calendar.export'),
            ]
        );

        $recentEventCount = 0;
        if ($calendarTableAvailable) {
            foreach ($years as $year) {
                $recentEventCount += DB::table('fiscal_calendar_events')
                    ->where('company_id', $companyId)
                    ->where(function ($query) use ($year): void {
                        $query->where('reference_period', (string) $year)
                            ->orWhere('reference_period', 'like', $year . '-%');
                    })
                    ->count();
            }
        }

        $addCheck(
            'fiscal.calendar.window',
            'Fiscal calendar coverage window',
            $recentEventCount >= 108 ? 'pass' : 'warn',
            $recentEventCount >= 108
                ? sprintf('The fiscal calendar contains %d obligations across the current and next year.', $recentEventCount)
                : sprintf('The fiscal calendar currently contains %d obligations in the current/next-year window.', $recentEventCount),
            false,
            [
                'base_year' => $baseYear,
                'years' => $years,
                'event_count' => $recentEventCount,
            ]
        );

        $overallStatus = 'ready';
        if ($summary['fail'] > 0) {
            $overallStatus = 'blocked';
        } elseif ($summary['warn'] > 0) {
            $overallStatus = 'attention';
        }

        return [
            'company_id' => $companyId,
            'generated_at' => now()->toDateTimeString(),
            'overall_status' => $overallStatus,
            'summary' => $summary,
            'checks' => $checks,
            'window' => [
                'base_year' => $baseYear,
                'years' => $years,
                'recent_event_count' => $recentEventCount,
            ],
        ];
    }
}
