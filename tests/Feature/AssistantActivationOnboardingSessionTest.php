<?php

namespace Tests\Feature;

use App\Models\OnboardingSession;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssistantActivationOnboardingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_onboarding_sessions_table_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('onboarding_sessions'));

        foreach ([
            'company_id',
            'status',
            'current_module_key',
            'current_step_key',
            'progress_percent',
            'started_at',
            'last_activity_at',
            'completed_at',
            'abandoned_at',
            'completion_note',
            'metadata',
            'created_by',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('onboarding_sessions', $column), sprintf('Missing column %s', $column));
        }
    }

    public function test_it_tracks_session_lifecycle_for_a_company(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
        ]);

        $service = app(OnboardingSessionService::class);

        $started = $service->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.company_profile',
            'metadata' => ['source' => 'dashboard'],
        ]);

        $this->assertInstanceOf(OnboardingSession::class, $started);
        $this->assertSame($company->id, $started->company_id);
        $this->assertSame('active', $started->status);
        $this->assertSame('billing', $started->current_module_key);
        $this->assertSame('billing.company_profile', $started->current_step_key);
        $this->assertSame(['source' => 'dashboard'], $started->metadata);
        $this->assertNotNull($started->started_at);
        $this->assertNotNull($started->last_activity_at);

        $completed = $service->completeForCompany($company->id, [
            'completion_note' => 'Initial setup done',
            'metadata' => ['completed_from' => 'assistant'],
        ]);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(100.00, (float) $completed->progress_percent);
        $this->assertNotNull($completed->completed_at);
        $this->assertSame('Initial setup done', $completed->completion_note);
        $this->assertSame([
            'source' => 'dashboard',
            'completed_from' => 'assistant',
        ], $completed->metadata);

        $restarted = $service->restartForCompany($company->id, [
            'metadata' => ['source' => 'reset'],
        ]);

        $this->assertSame('active', $restarted->status);
        $this->assertSame(['source' => 'reset'], $restarted->metadata);
        $this->assertNotSame($completed->id, $restarted->id);

        $this->assertSame(1, OnboardingSession::query()->forCompany($company->id)->active()->count());
        $this->assertSame(1, OnboardingSession::query()->forCompany($company->id)->completed()->count());
    }
}
