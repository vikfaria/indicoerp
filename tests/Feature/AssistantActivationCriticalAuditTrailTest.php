<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\OnboardingSession;
use App\Models\OnboardingStep;
use App\Models\Plan;
use App\Models\TenantFeatureOverride;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingSessionService;
use App\Services\AssistantActivation\OnboardingStepSkipService;
use App\Services\AssistantActivation\TenantFeatureOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AssistantActivationCriticalAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_audits_plan_subscription_changes_when_a_plan_is_assigned(): void
    {
        $plan = $this->makePlan('Professional Plan');
        $company = $this->makeCompany('Empresa Plano');

        $this->actingAs($company);

        $result = assignPlan(
            $plan->id,
            'month',
            '',
            [
                'user_counter' => $plan->number_of_users,
                'storage_limit' => $plan->storage_limit,
            ],
            $company->id
        );

        $this->assertTrue($result['is_success']);

        $entry = AuditTrail::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $company->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('updated', $entry->event);
        $this->assertSame($company->id, $entry->user_id);
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame($plan->id, $entry->new_values['active_plan']);
        $this->assertSame($plan->number_of_users, $entry->new_values['total_user']);
        $this->assertSame($plan->storage_limit, $entry->new_values['storage_limit']);
    }

    public function test_it_audits_company_overrides_on_create_update_and_delete(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = $this->makeCompany('Empresa Override');
        $service = app(TenantFeatureOverrideService::class);

        $this->actingAs($superAdmin);

        $override = $service->upsert([
            'company_id' => $company->id,
            'override_type' => TenantFeatureOverrideService::TYPE_FEATURE,
            'override_key' => 'billing.product.manage',
            'notes' => 'Aprovado para arranque',
        ], $superAdmin);

        $service->upsert([
            'id' => $override->id,
            'company_id' => $company->id,
            'override_type' => TenantFeatureOverrideService::TYPE_FEATURE,
            'override_key' => 'billing.product.manage',
            'notes' => 'Aprovado para arranque com revisão',
        ], $superAdmin);

        $service->delete($override->refresh());

        $entries = AuditTrail::query()
            ->where('auditable_type', TenantFeatureOverride::class)
            ->where('auditable_id', $override->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('event')->all());
        $this->assertSame('Aprovado para arranque', $entries[0]->new_values['notes']);
        $this->assertSame('Aprovado para arranque com revisão', $entries[1]->new_values['notes']);
    }

    public function test_it_audits_skipped_onboarding_steps(): void
    {
        $plan = $this->makePlan('Professional Plan');
        $company = $this->makeCompany('Empresa Skip', $plan);
        $sessionService = app(OnboardingSessionService::class);
        $skipService = app(OnboardingStepSkipService::class);

        $this->actingAs($company);

        $session = $sessionService->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.issue_test_invoice',
        ]);

        $step = OnboardingStep::query()->create([
            'onboarding_session_id' => $session->id,
            'company_id' => $company->id,
            'module_key' => 'billing',
            'step_key' => 'billing.issue_test_invoice',
            'step_label' => 'Emitir fatura de teste',
            'step_order' => 1,
            'is_required' => false,
            'state' => 'pending',
            'created_by' => $company->id,
        ]);

        $report = $skipService->skipForSession(
            $session,
            'billing.issue_test_invoice',
            'Não aplicável no go-live',
            $company
        );

        $this->assertSame('skipped', $report['summary']['skip_state']);

        $entry = AuditTrail::query()
            ->where('auditable_type', OnboardingStep::class)
            ->where('auditable_id', $step->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('updated', $entry->event);
        $this->assertSame('skipped', $entry->new_values['state']);
        $this->assertSame('Não aplicável no go-live', $entry->new_values['skip_reason']);
    }

    public function test_it_audits_onboarding_completion(): void
    {
        $plan = $this->makePlan('Professional Plan');
        $company = $this->makeCompany('Empresa Completion', $plan);
        $sessionService = app(OnboardingSessionService::class);

        $this->actingAs($company);

        $sessionService->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.company_profile',
        ]);

        $completed = $sessionService->completeForCompany($company->id, [
            'completion_note' => 'Setup concluído',
        ]);

        $entry = AuditTrail::query()
            ->where('auditable_type', OnboardingSession::class)
            ->where('auditable_id', $completed->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('updated', $entry->event);
        $this->assertSame('completed', $entry->new_values['status']);
        $this->assertSame('Setup concluído', $entry->new_values['completion_note']);
    }

    private function makePlan(string $name): Plan
    {
        return Plan::create([
            'name' => $name,
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

    private function makeCompany(string $name, ?Plan $plan = null): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan?->id ?? 0,
            'plan_expire_date' => $plan ? now()->addMonth() : null,
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function makeSuperAdmin(): User
    {
        return User::forceCreate([
            'name' => 'Super Admin Audit',
            'email' => 'superadmin.audit@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'superadmin',
            'active_plan' => 0,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }
}
