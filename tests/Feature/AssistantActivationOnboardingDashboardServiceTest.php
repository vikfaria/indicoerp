<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AssistantActivation\OnboardingCompletionService;
use App\Services\AssistantActivation\OnboardingDashboardService;
use App\Services\AssistantActivation\OnboardingReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AssistantActivationOnboardingDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_contextual_ctas_for_critical_blocks_on_the_dashboard(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'active_plan' => 1,
        ]);

        $readiness = Mockery::mock(OnboardingReadinessService::class);
        $readiness->shouldReceive('catalogVersion')->andReturn('2026-06-06');
        $readiness->shouldReceive('calculateForCompany')
            ->once()
            ->with($company, ['Account'], 'Starter')
            ->andReturn([
                'meta' => [
                    'catalog_version' => '2026-06-06',
                    'plan_label' => 'Starter',
                    'session_status' => 'active',
                ],
                'summary' => [
                    'readiness_state' => 'blocked',
                    'overall_score' => 41.5,
                    'applicable_modules_total' => 1,
                    'config_blocks_total' => 1,
                    'step_blocks_total' => 0,
                ],
                'critical_blocks' => [
                    [
                        'type' => 'config_missing',
                        'key' => 'fiscal_profile',
                        'label' => 'Perfil fiscal',
                        'message' => 'Perfil fiscal em falta.',
                    ],
                ],
                'modules' => [],
            ]);

        $completion = Mockery::mock(OnboardingCompletionService::class);
        $completion->shouldReceive('calculateForCompany')
            ->once()
            ->with($company, ['Account'], 'Starter')
            ->andReturn([
                'summary' => [
                    'can_complete' => false,
                    'completion_state' => 'blocked',
                ],
            ]);

        $this->app->instance(OnboardingReadinessService::class, $readiness);
        $this->app->instance(OnboardingCompletionService::class, $completion);

        $snapshot = $this->app->make(OnboardingDashboardService::class)->snapshot($company, ['Account'], 'Starter');

        $this->assertSame('Configurar perfil fiscal', $snapshot['next_action']['label']);
        $this->assertSame(route('sce.fiscal.index'), $snapshot['next_action']['href']);
        $this->assertSame('default', $snapshot['next_action']['tone']);
        $this->assertStringContainsString('perfil fiscal', $snapshot['next_action']['message']);
    }
}
