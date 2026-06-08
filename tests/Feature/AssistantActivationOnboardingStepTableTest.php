<?php

namespace Tests\Feature;

use App\Models\OnboardingSession;
use App\Models\OnboardingStep;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssistantActivationOnboardingStepTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_onboarding_steps_table_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('onboarding_steps'));

        foreach ([
            'onboarding_session_id',
            'company_id',
            'module_key',
            'step_key',
            'step_label',
            'step_order',
            'is_required',
            'state',
            'started_at',
            'completed_at',
            'skipped_at',
            'blocked_at',
            'skip_reason',
            'metadata',
            'created_by',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('onboarding_steps', $column), sprintf('Missing column %s', $column));
        }
    }

    public function test_it_tracks_step_state_against_a_company_session(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
        ]);

        $session = app(OnboardingSessionService::class)->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.company_profile',
        ]);

        $step = $session->steps()->create([
            'company_id' => $company->id,
            'module_key' => 'billing',
            'step_key' => 'billing.company_profile',
            'step_label' => 'Configurar perfil fiscal',
            'step_order' => 1,
            'is_required' => true,
            'state' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'metadata' => [
                'source' => 'manual_test',
            ],
            'created_by' => $company->id,
        ]);

        $this->assertInstanceOf(OnboardingStep::class, $step);
        $this->assertSame($session->id, $step->onboarding_session_id);
        $this->assertSame($company->id, $step->company_id);
        $this->assertSame('billing', $step->module_key);
        $this->assertSame('billing.company_profile', $step->step_key);
        $this->assertSame('completed', $step->state);
        $this->assertTrue($step->isCompleted());
        $this->assertSame(['source' => 'manual_test'], $step->metadata);

        $this->assertSame(1, OnboardingStep::query()->forSession($session->id)->completed()->count());
        $this->assertSame(1, $session->steps()->count());
        $this->assertInstanceOf(OnboardingSession::class, $step->session);
    }
}
