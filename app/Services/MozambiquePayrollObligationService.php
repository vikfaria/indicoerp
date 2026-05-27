<?php

namespace App\Services;

use App\Models\FiscalCalendarEvent;
use Carbon\Carbon;
use Workdo\Hrm\Models\Payroll;

class MozambiquePayrollObligationService
{
    public function monthlySummary(int $companyId, int $months = 6): array
    {
        $months = max(1, min($months, 24));
        $today = Carbon::today();

        $recordsByPeriod = $this->buildMonthlySkeleton($today, $months);
        $periodKeys = $recordsByPeriod->keys()->all();

        $oldestDate = $today->copy()->startOfMonth()->subMonthsNoOverflow($months - 1)->toDateString();
        $newestDate = $today->copy()->endOfMonth()->toDateString();

        $payrolls = Payroll::query()
            ->where('created_by', $companyId)
            ->where('status', 'completed')
            ->where(function ($query) use ($oldestDate, $newestDate): void {
                $query
                    ->where(function ($dateQuery) use ($oldestDate, $newestDate): void {
                        $dateQuery
                            ->whereDate('pay_period_end', '>=', $oldestDate)
                            ->whereDate('pay_period_end', '<=', $newestDate);
                    })
                    ->orWhere(function ($dateQuery) use ($oldestDate, $newestDate): void {
                        $dateQuery
                            ->whereDate('pay_date', '>=', $oldestDate)
                            ->whereDate('pay_date', '<=', $newestDate);
                    });
            })
            ->get([
                'id',
                'pay_period_end',
                'pay_date',
                'total_irps',
                'total_inss_employee',
                'total_inss_employer',
                'total_gross_pay',
                'employee_count',
            ]);

        foreach ($payrolls as $payroll) {
            $referenceDate = $payroll->pay_period_end ?? $payroll->pay_date;
            if ($referenceDate === null) {
                continue;
            }

            $periodKey = Carbon::parse($referenceDate)->format('Y-m');
            if (!$recordsByPeriod->has($periodKey)) {
                continue;
            }

            $record = $recordsByPeriod->get($periodKey);
            $record['payroll_runs']++;
            $record['total_irps'] = round($record['total_irps'] + (float) ($payroll->total_irps ?? 0), 2);
            $record['total_inss_employee'] = round($record['total_inss_employee'] + (float) ($payroll->total_inss_employee ?? 0), 2);
            $record['total_inss_employer'] = round($record['total_inss_employer'] + (float) ($payroll->total_inss_employer ?? 0), 2);
            $record['total_gross_pay'] = round($record['total_gross_pay'] + (float) ($payroll->total_gross_pay ?? 0), 2);
            $record['employee_count'] += (int) ($payroll->employee_count ?? 0);
            $record['has_completed_payroll'] = true;
            $recordsByPeriod->put($periodKey, $record);
        }

        $events = FiscalCalendarEvent::query()
            ->where('company_id', $companyId)
            ->whereIn('obligation_type', ['inss', 'irps'])
            ->whereIn('reference_period', $periodKeys)
            ->get(['obligation_type', 'reference_period', 'status'])
            ->groupBy(fn (FiscalCalendarEvent $event): string => "{$event->obligation_type}|{$event->reference_period}");

        $totals = [
            'applicable_periods' => 0,
            'overdue_inss' => 0,
            'pending_inss' => 0,
            'completed_inss' => 0,
            'overdue_irps' => 0,
            'pending_irps' => 0,
            'completed_irps' => 0,
            'total_irps' => 0.0,
            'total_inss_employee' => 0.0,
            'total_inss_employer' => 0.0,
            'total_inss' => 0.0,
        ];

        $records = [];

        foreach ($recordsByPeriod as $periodKey => $record) {
            $periodDate = Carbon::createFromFormat('!Y-m', $periodKey)->startOfMonth();
            $inssDueDate = $periodDate->copy()->addMonthNoOverflow()->day(10);
            $irpsDueDate = $periodDate->copy()->addMonthNoOverflow()->day(20);

            $inssEvent = $events->get("inss|{$periodKey}")?->first();
            $irpsEvent = $events->get("irps|{$periodKey}")?->first();

            $record['inss_due_date'] = $inssDueDate->toDateString();
            $record['irps_due_date'] = $irpsDueDate->toDateString();
            $record['inss_status'] = $this->resolveStatus($record['has_completed_payroll'], $inssDueDate, $today, $inssEvent);
            $record['irps_status'] = $this->resolveStatus($record['has_completed_payroll'], $irpsDueDate, $today, $irpsEvent);
            $record['total_inss'] = round($record['total_inss_employee'] + $record['total_inss_employer'], 2);

            if ($record['has_completed_payroll']) {
                $totals['applicable_periods']++;
                $totals['total_irps'] = round($totals['total_irps'] + $record['total_irps'], 2);
                $totals['total_inss_employee'] = round($totals['total_inss_employee'] + $record['total_inss_employee'], 2);
                $totals['total_inss_employer'] = round($totals['total_inss_employer'] + $record['total_inss_employer'], 2);
                $totals['total_inss'] = round($totals['total_inss'] + $record['total_inss'], 2);

                $this->incrementStatusCounter($totals, $record['inss_status'], 'inss');
                $this->incrementStatusCounter($totals, $record['irps_status'], 'irps');
            }

            $records[] = $record;
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'records' => $records,
            'totals' => $totals,
        ];
    }

    private function buildMonthlySkeleton(Carbon $today, int $months)
    {
        $records = collect();

        for ($offset = 0; $offset < $months; $offset++) {
            $periodDate = $today->copy()->startOfMonth()->subMonthsNoOverflow($offset);
            $periodKey = $periodDate->format('Y-m');

            $records->put($periodKey, [
                'reference_period' => $periodKey,
                'month_label' => $periodDate->format('F Y'),
                'payroll_runs' => 0,
                'employee_count' => 0,
                'total_gross_pay' => 0.0,
                'total_irps' => 0.0,
                'total_inss_employee' => 0.0,
                'total_inss_employer' => 0.0,
                'total_inss' => 0.0,
                'has_completed_payroll' => false,
                'inss_due_date' => null,
                'irps_due_date' => null,
                'inss_status' => 'not_applicable',
                'irps_status' => 'not_applicable',
            ]);
        }

        return $records;
    }

    private function resolveStatus(bool $hasCompletedPayroll, Carbon $dueDate, Carbon $today, ?FiscalCalendarEvent $event): string
    {
        if (!$hasCompletedPayroll) {
            return 'not_applicable';
        }

        if ($event !== null && $event->status === 'completed') {
            return 'completed';
        }

        return $dueDate->lt($today) ? 'overdue' : 'pending';
    }

    private function incrementStatusCounter(array &$totals, string $status, string $suffix): void
    {
        if (!in_array($status, ['overdue', 'pending', 'completed'], true)) {
            return;
        }

        $key = "{$status}_{$suffix}";
        $totals[$key] = ($totals[$key] ?? 0) + 1;
    }
}
