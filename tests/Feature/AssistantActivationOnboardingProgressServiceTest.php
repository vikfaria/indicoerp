<?php

namespace Tests\Feature;

use App\Models\OnboardingChecklistItem;
use App\Models\OnboardingStep;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingProgressService;
use App\Services\AssistantActivation\OnboardingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantActivationOnboardingProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_the_progress_contract_schema(): void
    {
        $service = app(OnboardingProgressService::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame('available steps only; checklist items provide partial progress', $report['summary']['calculation_basis']);
        $this->assertSame(6, $report['summary']['step_states_total']);
        $this->assertSame(6, $report['summary']['checklist_states_total']);
        $this->assertSame(['pending', 'in_progress', 'completed', 'blocked', 'skipped', 'not_applicable'], $report['step_states']);
        $this->assertSame(['pending', 'in_progress', 'completed', 'blocked', 'skipped', 'not_applicable'], $report['checklist_states']);
    }

    public function test_it_calculates_global_and_module_progress_from_session_data(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
        ]);

        $session = app(OnboardingSessionService::class)->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.create_customer_masterdata',
        ]);

        $this->createStep($session->id, $company->id, 'billing', 'billing.configure_fiscal_profile', 'completed', 10);
        $this->createStep($session->id, $company->id, 'billing', 'billing.configure_document_series', 'completed', 20);
        $this->createStep($session->id, $company->id, 'billing', 'billing.open_accounting_period', 'completed', 30);
        $this->createStep($session->id, $company->id, 'billing', 'billing.create_customer_masterdata', 'completed', 40);

        $this->createStep($session->id, $company->id, 'accounting', 'accounting.configure_chart_of_accounts', 'completed', 10);
        $this->createStep($session->id, $company->id, 'accounting', 'accounting.record_opening_balances', 'completed', 20);
        $this->createStep($session->id, $company->id, 'accounting', 'accounting.open_period', 'completed', 30);

        $partialAccountingStep = $this->createStep($session->id, $company->id, 'accounting', 'accounting.configure_journal_templates', 'in_progress', 40);
        $partialAccountingStep->checklistItems()->create([
            'onboarding_session_id' => $session->id,
            'company_id' => $company->id,
            'item_key' => 'accounting.journal_templates.chart',
            'item_label' => 'Definir templates de lançamentos',
            'item_order' => 1,
            'is_required' => true,
            'state' => 'completed',
            'created_by' => $company->id,
        ]);
        $partialAccountingStep->checklistItems()->create([
            'onboarding_session_id' => $session->id,
            'company_id' => $company->id,
            'item_key' => 'accounting.journal_templates.approval',
            'item_label' => 'Configurar aprovação',
            'item_order' => 2,
            'is_required' => true,
            'state' => 'pending',
            'created_by' => $company->id,
        ]);

        $report = app(OnboardingProgressService::class)->calculateForSession($session, ['Taskly', 'Account', 'Hrm', 'DoubleEntry'], 'Free Plan');

        $this->assertSame('Free Plan', $report['meta']['plan_label']);
        $this->assertSame(['Taskly', 'Account', 'Hrm', 'DoubleEntry'], $report['meta']['plan_modules']);
        $this->assertSame(22, $report['summary']['available_steps_total']);
        $this->assertSame(28, $report['summary']['steps_total']);
        $this->assertSame(7, $report['summary']['completed_steps_total']);
        $this->assertSame(1, $report['summary']['partial_steps_total']);
        $this->assertSame(14, $report['summary']['pending_steps_total']);
        $this->assertSame(34.09, $report['summary']['progress_percent']);

        $billing = collect($report['modules'])->firstWhere('key', 'billing');
        $accounting = collect($report['modules'])->firstWhere('key', 'accounting');
        $hr = collect($report['modules'])->firstWhere('key', 'hr');

        $this->assertNotNull($billing);
        $this->assertSame(4, $billing['available_step_count']);
        $this->assertSame(100.0, $billing['progress_percent']);
        $this->assertSame(4, $billing['completed_step_count']);
        $this->assertSame(0, $billing['partial_step_count']);

        $this->assertNotNull($accounting);
        $this->assertSame(6, $accounting['available_step_count']);
        $this->assertSame(58.33, $accounting['progress_percent']);
        $this->assertSame(3, $accounting['completed_step_count']);
        $this->assertSame(1, $accounting['partial_step_count']);
        $this->assertSame(2, $accounting['pending_step_count']);

        $this->assertNotNull($hr);
        $this->assertSame(0.0, $hr['progress_percent']);
        $this->assertSame(0, $hr['completed_step_count']);

        $billingPartialStep = collect($billing['steps'])->firstWhere('key', 'billing.create_customer_masterdata');
        $this->assertSame(100.0, $billingPartialStep['progress_percent']);
        $accountingPartialStep = collect($accounting['steps'])->firstWhere('key', 'accounting.configure_journal_templates');
        $this->assertSame(50.0, $accountingPartialStep['progress_percent']);
        $this->assertSame(2, $accountingPartialStep['items_total']);
        $this->assertSame(1, $accountingPartialStep['items_completed_total']);
    }

    private function createStep(int $sessionId, int $companyId, string $moduleKey, string $stepKey, string $state, int $order): OnboardingStep
    {
        return OnboardingStep::query()->create([
            'onboarding_session_id' => $sessionId,
            'company_id' => $companyId,
            'module_key' => $moduleKey,
            'step_key' => $stepKey,
            'step_label' => $stepKey,
            'step_order' => $order,
            'is_required' => true,
            'state' => $state,
            'started_at' => now()->subMinutes(10),
            'completed_at' => $state === 'completed' ? now() : null,
            'created_by' => $companyId,
        ]);
    }
}
