<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MozambiqueHrComplianceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Hrm\Models\HrComplianceAlert;

class MozambiqueHrComplianceAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_open_alert_records_for_triggered_items(): void
    {
        $company = $this->makeCompany();
        $snapshot = $this->snapshot([
            $this->item('employees_without_nuit', 2, 'high'),
            $this->item('probation_ending_30d', 1, 'medium'),
            $this->item('missing_active_irps_table', 0, 'high'),
        ]);

        $result = app(MozambiqueHrComplianceAlertService::class)->syncFromSnapshot($company->id, $snapshot);

        $this->assertSame(2, (int) data_get($result, 'metrics.open_alerts'));
        $this->assertDatabaseHas('hr_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'employees_without_nuit',
            'status' => HrComplianceAlert::STATUS_OPEN,
            'severity' => HrComplianceAlert::SEVERITY_HIGH,
            'count' => 2,
            'times_triggered' => 1,
        ]);
        $this->assertDatabaseHas('hr_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'probation_ending_30d',
            'status' => HrComplianceAlert::STATUS_OPEN,
            'severity' => HrComplianceAlert::SEVERITY_MEDIUM,
            'count' => 1,
            'times_triggered' => 1,
        ]);
        $this->assertDatabaseMissing('hr_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'missing_active_irps_table',
        ]);
    }

    public function test_sync_marks_alert_as_resolved_when_count_reaches_zero(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueHrComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('employees_without_inss', 3, 'high'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('employees_without_inss', 0, 'high'),
        ]));

        $alert = HrComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'employees_without_inss')
            ->firstOrFail();

        $this->assertSame(HrComplianceAlert::STATUS_RESOLVED, $alert->status);
        $this->assertSame(0, (int) $alert->count);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_sync_reopens_resolved_alert_and_increments_times_triggered(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueHrComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('foreign_documents_expired', 1, 'high'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('foreign_documents_expired', 0, 'high'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('foreign_documents_expired', 4, 'high'),
        ]));

        $alert = HrComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'foreign_documents_expired')
            ->firstOrFail();

        $this->assertSame(HrComplianceAlert::STATUS_OPEN, $alert->status);
        $this->assertSame(4, (int) $alert->count);
        $this->assertSame(2, (int) $alert->times_triggered);
        $this->assertNull($alert->resolved_at);
    }

    public function test_sync_resolves_stale_open_alerts_missing_from_snapshot(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueHrComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('overtime_weekly_limit_breaches', 1, 'high'),
            $this->item('labour_contracts_expiring_30d', 2, 'medium'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->item('labour_contracts_expiring_30d', 2, 'medium'),
        ]));

        $stale = HrComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'overtime_weekly_limit_breaches')
            ->firstOrFail();

        $active = HrComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'labour_contracts_expiring_30d')
            ->firstOrFail();

        $this->assertSame(HrComplianceAlert::STATUS_RESOLVED, $stale->status);
        $this->assertSame(0, (int) $stale->count);
        $this->assertNotNull($stale->resolved_at);
        $this->assertSame(HrComplianceAlert::STATUS_OPEN, $active->status);
        $this->assertSame(2, (int) $active->count);
    }

    public function test_sync_scopes_alerts_by_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $service = app(MozambiqueHrComplianceAlertService::class);

        $service->syncFromSnapshot($companyA->id, $this->snapshot([
            $this->item('payroll_irps_submission_overdue', 1, 'high'),
        ]));

        $service->syncFromSnapshot($companyB->id, $this->snapshot([
            $this->item('payroll_irps_submission_overdue', 2, 'high'),
        ]));

        $this->assertDatabaseHas('hr_compliance_alerts', [
            'company_id' => $companyA->id,
            'alert_key' => 'payroll_irps_submission_overdue',
            'count' => 1,
            'status' => HrComplianceAlert::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('hr_compliance_alerts', [
            'company_id' => $companyB->id,
            'alert_key' => 'payroll_irps_submission_overdue',
            'count' => 2,
            'status' => HrComplianceAlert::STATUS_OPEN,
        ]);
    }

    private function snapshot(array $items): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'items' => $items,
        ];
    }

    private function item(string $key, int $count, string $severity): array
    {
        return [
            'key' => $key,
            'label' => ucfirst(str_replace('_', ' ', $key)),
            'count' => $count,
            'severity' => $severity,
        ];
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }
}

