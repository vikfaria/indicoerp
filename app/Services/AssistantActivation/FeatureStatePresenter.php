<?php

namespace App\Services\AssistantActivation;

use App\Models\User;

class FeatureStatePresenter
{
    public function __construct(
        private readonly PlanFeatureResolver $featureResolver,
        private readonly UpgradeSuggestionService $upgradeSuggestionService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    public function present(string $featureKey, ?User $user = null, string $surface = 'menu'): FeatureStatePayload
    {
        $resolution = $this->featureResolver->resolve($featureKey, $user);
        $recommendation = $this->upgradeSuggestionService->suggestFromFeatureResolution($resolution);
        $payload = FeatureStatePayload::fromResolution($resolution, $recommendation, $surface)->toArray();
        $contextualCta = $this->contextualCtaResolverService->forRecommendation($recommendation, $resolution);

        if ($contextualCta !== null) {
            $payload['cta'] = $contextualCta;
        }

        return new FeatureStatePayload($payload);
    }

    public function presentArray(string $featureKey, ?User $user = null, string $surface = 'menu'): array
    {
        return $this->present($featureKey, $user, $surface)->toArray();
    }

    public function presentResolution(array $resolution, string $surface = 'menu'): FeatureStatePayload
    {
        $recommendation = $this->upgradeSuggestionService->suggestFromFeatureResolution($resolution);
        $payload = FeatureStatePayload::fromResolution($resolution, $recommendation, $surface)->toArray();
        $contextualCta = $this->contextualCtaResolverService->forRecommendation($recommendation, $resolution);

        if ($contextualCta !== null) {
            $payload['cta'] = $contextualCta;
        }

        return new FeatureStatePayload($payload);
    }
}
