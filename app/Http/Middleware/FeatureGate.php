<?php

namespace App\Http\Middleware;

use App\Services\AssistantActivation\FeatureStatePresenter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    public function __construct(
        private readonly FeatureStatePresenter $featureStatePresenter
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $featureKey = trim($featureKey);
        if ($featureKey === '') {
            return $next($request);
        }

        $payload = $this->featureStatePresenter->presentArray($featureKey, $user, 'menu');
        if (($payload['state'] ?? 'hidden') === 'active') {
            return $next($request);
        }

        $message = $this->blockedMessage($payload);

        if ($request->expectsJson()) {
            return $this->jsonBlockedResponse($payload, $message);
        }

        return $this->redirectBlockedResponse($request, $payload, $message);
    }

    private function jsonBlockedResponse(array $payload, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'feature_gate' => $payload,
        ], 403);
    }

    private function redirectBlockedResponse(Request $request, array $payload, string $message): RedirectResponse
    {
        $redirect = $request->headers->has('referer')
            ? redirect()->back()->withInput()
            : redirect()->route('dashboard');

        return $redirect
            ->with('error', $message)
            ->with('feature_gate', $payload);
    }

    private function blockedMessage(array $payload): string
    {
        $summary = trim((string) data_get($payload, 'summary', ''));
        if ($summary !== '') {
            return $summary;
        }

        $label = trim((string) data_get($payload, 'label', ''));
        $reasons = Arr::wrap(data_get($payload, 'reasons', []));
        $reasons = array_values(array_filter(array_map(
            static fn ($reason) => trim((string) $reason),
            $reasons
        )));

        if ($label !== '' && $reasons !== []) {
            return sprintf('%s indisponível (%s).', $label, implode(', ', $reasons));
        }

        if ($label !== '') {
            return sprintf('%s indisponível.', $label);
        }

        return __('Permission denied');
    }
}
