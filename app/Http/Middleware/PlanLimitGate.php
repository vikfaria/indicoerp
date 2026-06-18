<?php

namespace App\Http\Middleware;

use App\Services\AssistantActivation\PlanLimitResolver;
use App\Services\AssistantActivation\ContextualCtaResolverService;
use App\Services\AssistantActivation\UpgradeSuggestionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PlanLimitGate
{
    public function __construct(
        private readonly PlanLimitResolver $limitResolver,
        private readonly UpgradeSuggestionService $upgradeSuggestionService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitKey): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'ensureCompanyAccessRole')) {
            $user->ensureCompanyAccessRole();
        }

        $limitKey = trim($limitKey);
        if ($limitKey === '' || ! $this->shouldEnforceForCurrentRoute($request)) {
            return $next($request);
        }

        $resolution = $this->limitResolver->resolve($limitKey, $user);
        $contractedLimit = $resolution['contracted_limit'] ?? null;

        if ($contractedLimit === null || (int) $contractedLimit < 0) {
            return $next($request);
        }

        if (! $this->isLimitReached($resolution)) {
            return $next($request);
        }

        $message = $this->blockedMessage($resolution);
        $suggestion = $this->upgradeSuggestionService->suggestLimit($limitKey, $user);
        $cta = $this->contextualCtaResolverService->forRecommendation(
            (array) data_get($suggestion, 'recommendation', []),
            array_merge($resolution, ['type' => 'limit'])
        );
        $payload = $this->buildPayload($resolution, $message, $suggestion, $cta);

        if ($request->expectsJson()) {
            return $this->jsonBlockedResponse($payload, $message);
        }

        return $this->redirectBlockedResponse($request, $payload, $message);
    }

    private function shouldEnforceForCurrentRoute(Request $request): bool
    {
        $route = $request->route();
        $routeName = (string) ($route?->getName() ?? '');
        $actionMethod = (string) ($route?->getActionMethod() ?? '');

        if ($routeName !== '' && (Str::endsWith($routeName, '.create') || Str::endsWith($routeName, '.store'))) {
            return true;
        }

        return in_array($actionMethod, ['create', 'store'], true);
    }

    private function isLimitReached(array $resolution): bool
    {
        $contractedLimit = (int) ($resolution['contracted_limit'] ?? -1);
        $currentUsage = (int) ($resolution['current_usage'] ?? 0);

        if ($contractedLimit < 0) {
            return false;
        }

        return $currentUsage >= $contractedLimit;
    }

    private function buildPayload(array $resolution, string $message, array $suggestion, ?array $cta): array
    {
        return [
            'blocked' => true,
            'message' => $message,
            'limit_key' => $resolution['key'] ?? null,
            'label' => $resolution['label'] ?? null,
            'state' => $resolution['state'] ?? null,
            'current_usage' => $resolution['current_usage'] ?? null,
            'contracted_limit' => $resolution['contracted_limit'] ?? null,
            'contracted_limit_display' => $resolution['contracted_limit_display'] ?? null,
            'remaining' => $resolution['remaining'] ?? null,
            'usage_percent' => $resolution['usage_percent'] ?? null,
            'threshold_percent' => $resolution['threshold_percent'] ?? null,
            'unlimited' => $resolution['unlimited'] ?? null,
            'plan_family' => $resolution['plan_family'] ?? null,
            'plan_name' => $resolution['plan_name'] ?? null,
            'plan_id' => $resolution['plan_id'] ?? null,
            'subscription_state' => $resolution['subscription_state'] ?? null,
            'reasons' => $resolution['reasons'] ?? [],
            'usage_breakdown' => $resolution['usage_breakdown'] ?? [],
            'block' => data_get($suggestion, 'block'),
            'suggestion' => $suggestion,
            'recommendation' => data_get($suggestion, 'recommendation'),
            'cta' => $cta,
        ];
    }

    private function jsonBlockedResponse(array $payload, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'plan_limit' => $payload,
        ], 403);
    }

    private function redirectBlockedResponse(Request $request, array $payload, string $message): RedirectResponse
    {
        $redirect = $request->headers->has('referer')
            ? redirect()->back()->withInput()
            : redirect()->route('dashboard');

        return $redirect
            ->with('error', $message)
            ->with('plan_limit', $payload);
    }

    private function blockedMessage(array $resolution): string
    {
        $label = trim((string) ($resolution['label'] ?? ''));
        $currentUsage = (int) ($resolution['current_usage'] ?? 0);
        $contractedLimit = (int) ($resolution['contracted_limit'] ?? 0);

        if ($label === '') {
            $label = __('este recurso');
        }

        return __('O limite de :label foi atingido (:current/:limit). Actualize o plano para continuar.', [
            'label' => $label,
            'current' => $currentUsage,
            'limit' => $contractedLimit,
        ]);
    }
}
