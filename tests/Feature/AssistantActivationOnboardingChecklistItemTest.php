<?php

namespace Tests\Feature;

use App\Models\OnboardingChecklistItem;
use App\Models\OnboardingStep;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssistantActivationOnboardingChecklistItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_onboarding_checklist_items_table_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('onboarding_checklist_items'));

        foreach ([
            'onboarding_session_id',
            'onboarding_step_id',
            'company_id',
            'item_key',
            'item_label',
            'item_description',
            'item_order',
            'is_required',
            'state',
            'started_at',
            'completed_at',
            'skipped_at',
            'blocked_at',
            'skip_reason',
            'evidence',
            'metadata',
            'created_by',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('onboarding_checklist_items', $column), sprintf('Missing column %s', $column));
        }
    }

    public function test_it_tracks_checklist_items_against_a_step(): void
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
            'state' => 'pending',
            'created_by' => $company->id,
        ]);

        $item = $step->checklistItems()->create([
            'onboarding_session_id' => $session->id,
            'company_id' => $company->id,
            'item_key' => 'billing.company_nuit',
            'item_label' => 'NUIT da empresa',
            'item_description' => 'Confirmar que o NUIT está preenchido e válido.',
            'item_order' => 1,
            'is_required' => true,
            'state' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'evidence' => [
                'field' => 'company_fiscal_profile.nuit',
                'value' => '123456789',
            ],
            'metadata' => [
                'source' => 'manual_validation',
            ],
            'created_by' => $company->id,
        ]);

        $this->assertInstanceOf(OnboardingChecklistItem::class, $item);
        $this->assertSame($session->id, $item->onboarding_session_id);
        $this->assertSame($step->id, $item->onboarding_step_id);
        $this->assertSame($company->id, $item->company_id);
        $this->assertSame('billing.company_nuit', $item->item_key);
        $this->assertSame('completed', $item->state);
        $this->assertTrue($item->isCompleted());
        $this->assertSame([
            'field' => 'company_fiscal_profile.nuit',
            'value' => '123456789',
        ], $item->evidence);
        $this->assertSame(['source' => 'manual_validation'], $item->metadata);

        $this->assertSame(1, OnboardingChecklistItem::query()->forStep($step->id)->completed()->count());
        $this->assertSame(1, $step->checklistItems()->count());
        $this->assertInstanceOf(OnboardingStep::class, $item->step);
    }
}
