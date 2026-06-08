<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AssistantActivation\OnboardingMenuStateService;
use App\Services\AssistantActivation\OnboardingReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AssistantActivationMenuStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_blocked_modules_in_the_menu_snapshot(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'active_plan' => 1,
        ]);

        $readiness = Mockery::mock(OnboardingReadinessService::class);
        $readiness->shouldReceive('catalogVersion')->andReturn('2026-06-06');
        $readiness->shouldReceive('calculateForCompany')
            ->once()
            ->with($company, null, null)
            ->andReturn([
                'meta' => [
                    'catalog_version' => '2026-06-06',
                    'plan_label' => 'Starter',
                    'session_status' => 'active',
                ],
                'summary' => [
                    'readiness_state' => 'blocked',
                    'overall_score' => 41.5,
                    'applicable_modules_total' => 4,
                ],
                'critical_blocks' => [
                    [
                        'type' => 'step_incomplete',
                        'module_key' => 'billing',
                        'key' => 'billing.configure_document_series',
                        'label' => 'Criar séries documentais',
                        'message' => 'Séries em falta.',
                    ],
                    [
                        'type' => 'config_missing',
                        'owner_modules' => ['billing', 'accounting'],
                        'applicable_owner_modules' => ['billing'],
                        'key' => 'document_series',
                        'label' => 'Séries documentais',
                        'message' => 'Séries em falta.',
                    ],
                    [
                        'type' => 'step_incomplete',
                        'module_key' => 'hr',
                        'key' => 'hr.configure_payroll_calendar',
                        'label' => 'Calendário salarial',
                        'message' => 'Calendário salarial pendente.',
                    ],
                ],
            ]);

        $this->app->instance(OnboardingReadinessService::class, $readiness);

        $snapshot = $this->app->make(OnboardingMenuStateService::class)->snapshot($company);

        $this->assertTrue($snapshot['modules']['billing']['blocked']);
        $this->assertSame(2, $snapshot['modules']['billing']['block_count']);
        $this->assertTrue($snapshot['modules']['hr']['blocked']);
        $this->assertFalse($snapshot['modules']['accounting']['blocked']);
        $this->assertContains('billing', $snapshot['blocked_module_keys']);
        $this->assertContains('hr', $snapshot['blocked_module_keys']);
        $this->assertSame('complete_configuration', $snapshot['modules']['billing']['cta_action']);
        $this->assertSame('Criar séries documentais', $snapshot['modules']['billing']['cta_label']);
        $this->assertSame(route('sce.fiscal.series'), $snapshot['modules']['billing']['cta_href']);
        $this->assertNotEmpty($snapshot['modules']['billing']['cta_message']);
        $this->assertSame('Configurar calendário salarial', $snapshot['modules']['hr']['cta_label']);
        $this->assertSame(route('hrm.payrolls.index'), $snapshot['modules']['hr']['cta_href']);
        $this->assertNotEmpty($snapshot['meta']['signature']);
    }
}
