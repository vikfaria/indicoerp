<?php

namespace Tests\Feature;

use App\Models\OnboardingSession;
use App\Models\Plan;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingStepRegistry;
use App\Services\AssistantActivation\OnboardingSessionService;
use App\Services\AssistantActivation\OnboardingStepSkipService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AssistantActivationOnboardingStepSkipServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-07 12:00:00'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_exposes_the_step_skip_contract_schema(): void
    {
        $service = app(OnboardingStepSkipService::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame('Non-critical onboarding steps can be skipped with a required reason and preserved in the audit trail.', $report['summary']['calculation_basis']);
        $this->assertSame(2, $report['summary']['decision_states_total']);
        $this->assertSame(7, $report['summary']['blocker_codes_total']);
        $this->assertSame(7, $report['summary']['validation_checks_total']);
        $this->assertSame(4, $report['summary']['audit_fields_total']);
        $this->assertSame(['allowed', 'blocked'], $report['decision_states']);
        $this->assertSame([
            'missing_session',
            'missing_step',
            'session_completed',
            'session_abandoned',
            'step_required',
            'step_finalized',
            'missing_skip_reason',
        ], $report['blocker_codes']);
        $this->assertSame(['state', 'skip_reason', 'skipped_at', 'metadata'], $report['audit_fields']);
    }

    public function test_it_skips_an_optional_step_and_records_the_audit_trail(): void
    {
        $plan = $this->createPlan();
        $company = $this->createCompany($plan);
        $session = $this->createSession($company);

        $this->createRegistrySteps($session, $company, ['billing.issue_test_invoice']);

        $report = app(OnboardingStepSkipService::class)->skipForSession(
            $session,
            'billing.issue_test_invoice',
            'Não aplicável no go-live'
        );

        $this->assertSame('skipped', $report['summary']['skip_state']);
        $this->assertTrue($report['summary']['can_skip']);
        $this->assertFalse($report['summary']['already_skipped']);
        $this->assertSame('allowed', $report['summary']['decision_state']);
        $this->assertSame('billing.issue_test_invoice', $report['meta']['step_key']);
        $this->assertSame('skipped', $report['step']['state']);
        $this->assertFalse($report['step']['is_required']);
        $this->assertSame('Não aplicável no go-live', $report['step']['skip_reason']);
        $this->assertSame($company->id, $report['step']['metadata']['skip']['skipped_by']);
        $this->assertSame('Não aplicável no go-live', $report['step']['metadata']['skip']['reason']);
        $this->assertSame(100.0, $report['progress']['summary']['progress_percent']);
        $this->assertSame(100.0, (float) $report['session']['progress_percent']);

        $billing = collect($report['progress']['modules'])->firstWhere('key', 'billing');
        $this->assertNotNull($billing);
        $this->assertSame(100.0, $billing['progress_percent']);

        $skippedStep = collect($billing['steps'])->firstWhere('key', 'billing.issue_test_invoice');
        $this->assertSame('skipped', $skippedStep['state']);
        $this->assertSame(100.0, $skippedStep['progress_percent']);
    }

    public function test_it_blocks_skipping_required_steps(): void
    {
        $plan = $this->createPlan();
        $company = $this->createCompany($plan);
        $session = $this->createSession($company);

        $this->createRegistrySteps($session, $company, ['billing.configure_document_series']);

        $report = app(OnboardingStepSkipService::class)->skipForSession(
            $session,
            'billing.configure_document_series',
            'Não aplicável no go-live'
        );

        $this->assertSame('blocked', $report['summary']['skip_state']);
        $this->assertFalse($report['summary']['can_skip']);
        $this->assertSame('blocked', $report['summary']['decision_state']);
        $this->assertSame('step_required', $report['blocker']['code']);
        $this->assertSame('Required onboarding steps cannot be skipped.', $report['blocker']['message']);
        $this->assertSame('pending', $report['step']['state']);
        $this->assertNull($report['step']['skip_reason']);
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);
    }

    private function createCompany(Plan $plan): User
    {
        return User::forceCreate([
            'name' => 'Empresa Skip',
            'email' => 'skip@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function createSession(User $company): OnboardingSession
    {
        return app(OnboardingSessionService::class)->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.issue_test_invoice',
        ]);
    }

    /**
     * @param array<int, string> $pendingStepKeys
     */
    private function createRegistrySteps(OnboardingSession $session, User $company, array $pendingStepKeys): void
    {
        $registry = app(OnboardingStepRegistry::class);

        foreach ($registry->modules() as $module) {
            foreach ($module['steps'] as $step) {
                $isPending = in_array($step['key'], $pendingStepKeys, true);

                $session->steps()->create([
                    'company_id' => $company->id,
                    'module_key' => $module['key'],
                    'step_key' => $step['key'],
                    'step_label' => $step['label'],
                    'step_order' => $step['order'],
                    'is_required' => (bool) $step['required'],
                    'state' => $isPending ? 'pending' : 'completed',
                    'started_at' => now()->subMinutes(5),
                    'completed_at' => $isPending ? null : now(),
                    'created_by' => $company->id,
                ]);
            }
        }
    }
}
