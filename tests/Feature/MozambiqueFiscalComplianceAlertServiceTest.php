<?php

namespace Tests\Feature;

use App\Models\FiscalComplianceAlert;
use App\Models\User;
use App\Services\MozambiqueFiscalComplianceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MozambiqueFiscalComplianceAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_open_alert_records_for_triggered_items(): void
    {
        $company = $this->makeCompany();
        $snapshot = $this->snapshot([
            $this->alert('vat_deadline_due_soon', 2, 'high', 'IVA com vencimento próximo.'),
            $this->alert('saft_generated_not_submitted', 1, 'critical', 'SAF-T gerado sem submissão.'),
            $this->alert('documents_without_valid_nuit', 0, 'high', 'Documentos sem NUIT.'),
        ]);

        $result = app(MozambiqueFiscalComplianceAlertService::class)->syncFromSnapshot($company->id, $snapshot);

        $this->assertSame(2, (int) data_get($result, 'metrics.open_alerts'));
        $this->assertSame(1, (int) data_get($result, 'metrics.open_critical_alerts'));
        $this->assertSame(1, (int) data_get($result, 'metrics.open_high_alerts'));
        $this->assertDatabaseHas('fiscal_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'vat_deadline_due_soon',
            'status' => FiscalComplianceAlert::STATUS_OPEN,
            'severity' => FiscalComplianceAlert::SEVERITY_HIGH,
            'count' => 2,
            'times_triggered' => 1,
        ]);
        $this->assertDatabaseHas('fiscal_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'saft_generated_not_submitted',
            'status' => FiscalComplianceAlert::STATUS_OPEN,
            'severity' => FiscalComplianceAlert::SEVERITY_CRITICAL,
            'count' => 1,
            'times_triggered' => 1,
        ]);
        $this->assertDatabaseMissing('fiscal_compliance_alerts', [
            'company_id' => $company->id,
            'alert_key' => 'documents_without_valid_nuit',
        ]);
    }

    public function test_sync_marks_alert_as_resolved_when_count_reaches_zero(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueFiscalComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('saft_generated_not_submitted', 3, 'critical', 'SAF-T gerado sem submissão.'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('saft_generated_not_submitted', 0, 'critical', 'SAF-T gerado sem submissão.'),
        ]));

        $alert = FiscalComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'saft_generated_not_submitted')
            ->firstOrFail();

        $this->assertSame(FiscalComplianceAlert::STATUS_RESOLVED, $alert->status);
        $this->assertSame(0, (int) $alert->count);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_sync_reopens_resolved_alert_and_increments_times_triggered(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueFiscalComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('documents_without_exemption_reason', 1, 'high', 'Linhas isentas sem motivo legal.'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('documents_without_exemption_reason', 0, 'high', 'Linhas isentas sem motivo legal.'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('documents_without_exemption_reason', 4, 'high', 'Linhas isentas sem motivo legal.'),
        ]));

        $alert = FiscalComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'documents_without_exemption_reason')
            ->firstOrFail();

        $this->assertSame(FiscalComplianceAlert::STATUS_OPEN, $alert->status);
        $this->assertSame(4, (int) $alert->count);
        $this->assertSame(2, (int) $alert->times_triggered);
        $this->assertNull($alert->resolved_at);
    }

    public function test_sync_resolves_stale_open_alerts_missing_from_snapshot(): void
    {
        $company = $this->makeCompany();
        $service = app(MozambiqueFiscalComplianceAlertService::class);

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('vat_deadline_overdue', 1, 'critical', 'IVA com prazo vencido.'),
            $this->alert('saft_generated_not_submitted', 2, 'critical', 'SAF-T gerado sem submissão.'),
        ]));

        $service->syncFromSnapshot($company->id, $this->snapshot([
            $this->alert('saft_generated_not_submitted', 2, 'critical', 'SAF-T gerado sem submissão.'),
        ]));

        $stale = FiscalComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'vat_deadline_overdue')
            ->firstOrFail();

        $active = FiscalComplianceAlert::query()
            ->where('company_id', $company->id)
            ->where('alert_key', 'saft_generated_not_submitted')
            ->firstOrFail();

        $this->assertSame(FiscalComplianceAlert::STATUS_RESOLVED, $stale->status);
        $this->assertSame(0, (int) $stale->count);
        $this->assertNotNull($stale->resolved_at);
        $this->assertSame(FiscalComplianceAlert::STATUS_OPEN, $active->status);
        $this->assertSame(2, (int) $active->count);
    }

    public function test_sync_scopes_alerts_by_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $service = app(MozambiqueFiscalComplianceAlertService::class);

        $service->syncFromSnapshot($companyA->id, $this->snapshot([
            $this->alert('gifim_pending_alerts', 1, 'high', 'Alertas GIFiM por comunicar.'),
        ]));

        $service->syncFromSnapshot($companyB->id, $this->snapshot([
            $this->alert('gifim_pending_alerts', 3, 'high', 'Alertas GIFiM por comunicar.'),
        ]));

        $this->assertDatabaseHas('fiscal_compliance_alerts', [
            'company_id' => $companyA->id,
            'alert_key' => 'gifim_pending_alerts',
            'count' => 1,
            'status' => FiscalComplianceAlert::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('fiscal_compliance_alerts', [
            'company_id' => $companyB->id,
            'alert_key' => 'gifim_pending_alerts',
            'count' => 3,
            'status' => FiscalComplianceAlert::STATUS_OPEN,
        ]);
    }

    private function snapshot(array $alerts): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'due_soon_days' => 7,
            'summary' => [
                'total_alerts' => count($alerts),
                'critical' => collect($alerts)->where('severity', 'critical')->count(),
                'high' => collect($alerts)->where('severity', 'high')->count(),
                'medium' => collect($alerts)->where('severity', 'medium')->count(),
                'low' => collect($alerts)->where('severity', 'low')->count(),
            ],
            'alerts' => $alerts,
        ];
    }

    private function alert(string $code, int $count, string $severity, string $message): array
    {
        return [
            'code' => $code,
            'rf' => 'RF079/RF095',
            'severity' => $severity,
            'category' => 'compliance',
            'count' => $count,
            'message' => $message,
            'samples' => [
                ['reference' => $code . '-001'],
            ],
            'metadata' => [
                'source' => 'test',
            ],
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
