<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\OnboardingStepRegistry;
use App\Services\AssistantActivation\ReadinessScoreService;
use Tests\TestCase;

class AssistantActivationReadinessScoreTest extends TestCase
{
    public function test_it_scores_readiness_using_module_and_configuration_weights(): void
    {
        $stepRegistry = app(OnboardingStepRegistry::class);
        $scoreService = app(ReadinessScoreService::class);

        $allStepKeys = collect($stepRegistry->modules())
            ->flatMap(fn (array $module) => $module['steps'])
            ->pluck('key')
            ->values()
            ->all();

        $allConfigKeys = collect($scoreService->criticalConfigWeights())
            ->pluck('key')
            ->values()
            ->all();

        $baseline = $scoreService->score([], []);
        $partial = $scoreService->score(
            array_slice($allStepKeys, 0, 6),
            array_slice($allConfigKeys, 0, 5)
        );
        $complete = $scoreService->score($allStepKeys, $allConfigKeys);

        $this->assertSame(0.0, $baseline['summary']['overall_score']);
        $this->assertGreaterThan($baseline['summary']['overall_score'], $partial['summary']['overall_score']);
        $this->assertSame(100.0, $complete['summary']['overall_score']);
        $this->assertSame(70.0, $complete['summary']['module_component_weight']);
        $this->assertSame(30.0, $complete['summary']['critical_config_component_weight']);
    }
}
