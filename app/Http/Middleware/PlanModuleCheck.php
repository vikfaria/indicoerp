<?php

namespace App\Http\Middleware;

use Closure;
use App\Classes\Module;
use App\Models\Plan;
use App\Models\User;
use App\Services\AssistantActivation\ContextualCtaResolverService;
use App\Services\AssistantActivation\PlanContractService;
use App\Services\AssistantActivation\ModuleCatalogService;
use App\Services\AssistantActivation\ModuleFeatureBridgeService;
use App\Services\AssistantActivation\PlanFeatureResolver;
use App\Services\AssistantActivation\TenantFeatureOverrideService;
use App\Services\AssistantActivation\UpgradeSuggestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Workdo\Hrm\Models\HrmModel;

class PlanModuleCheck
{
    public function __construct(
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly ModuleFeatureBridgeService $moduleFeatureBridgeService,
        private readonly PlanFeatureResolver $featureResolver,
        private readonly UpgradeSuggestionService $upgradeSuggestionService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService,
        private readonly PlanContractService $planContractService,
        private readonly TenantFeatureOverrideService $tenantFeatureOverrideService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $moduleName = null): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        if ($user->type === 'company' && !$request->session()->get('company_role_checked')) {
            try {
                $user->ensureCompanyAccessRole();
                User::MakeRole($user->id);
                HrmModel::defaultdata($user->id);
                Artisan::call('account:sync-finance-roles', [
                    '--company_id' => $user->id,
                ]);
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                $request->session()->put('company_role_checked', true);
            }
        }

        // Skip check for superadmin
        if ($user->isSuperAdminUser()) {
            return $next($request);
        }

        if ($user->can('view-company-onboarding-progress') && $request->routeIs('dashboard', 'assistant-activation.company-progress.index')) {
            return $next($request);
        }

        if ($user->hasRole('company')) {
            $subscriptionState = $this->resolveSubscriptionState($user);

            if ($subscriptionState !== null) {
                if ($this->routeHasTenantOverride($request, $user)) {
                    return $next($request);
                }

                // Plan expired or inactive - only allow essential plan routes
                $allowedRoutes = ['users.leave-impersonation', 'plans.index', 'plans.my-plan', 'plans.subscribe', 'plans.start-trial', 'plans.apply-coupon', 'payment.*.store', 'payment.*.status', 'bank-transfer.index', 'plans.assign-free'];
                if (! $request->routeIs($allowedRoutes)) {
                    return $this->denySubscriptionAccess(
                        $request,
                        $this->buildSubscriptionGate($user, $subscriptionState),
                        'plans.index'
                    );
                }
            }
        } else {
            // For sub-users - check creator's plan
            $creator = $user->createdBy;
            $subscriptionState = $creator ? $this->resolveSubscriptionState($creator) : null;

            if ($creator && $subscriptionState !== null) {
                if ($this->routeHasTenantOverride($request, $creator)) {
                    return $next($request);
                }

                Auth::logout();
                return $this->denySubscriptionAccess(
                    $request,
                    $this->buildSubscriptionGate($creator, $subscriptionState),
                    'login'
                );
            }
        }

        $requestedModules = $this->normalizeRequestedModules($moduleName);
        if ($requestedModules !== []) {
            if ($this->routeHasTenantOverride($request, $user)) {
                return $next($request);
            }

            $moduleGate = $this->resolveModuleGate($requestedModules, $user);

            if (($moduleGate['allowed'] ?? false) === true) {
                return $next($request);
            }

            return $this->denyModuleAccess($request, $moduleGate);
        }

        $response = $next($request);
        return $response;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeRequestedModules(mixed $moduleName): array
    {
        $moduleName = trim((string) $moduleName);

        if ($moduleName === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($module) => trim((string) $module),
            explode('-', $moduleName)
        )));
    }

    /**
     * @param array<int, string> $requestedModules
     * @return array<string, mixed>
     */
    private function resolveModuleGate(array $requestedModules, User $user): array
    {
        $activeModules = array_flip($this->resolveActiveModules($user));
        $moduleHelper = new Module();
        $checks = [];
        $resolverSuggestion = null;
        $knownCatalogModuleCount = 0;

        foreach ($requestedModules as $moduleReference) {
            $moduleReference = trim((string) $moduleReference);
            if ($moduleReference === '') {
                continue;
            }

            $exists = $moduleHelper->has($moduleReference);
            $enabled = $exists && $moduleHelper->isEnabled($moduleReference);
            $active = $enabled && isset($activeModules[$moduleReference]);
            $featureKeys = $this->moduleFeatureBridgeService->featureKeysForModuleReference($moduleReference);
            $catalogModuleKeys = $this->moduleFeatureBridgeService->moduleKeysForReference($moduleReference);
            $catalogModules = array_values(array_filter(array_map(
                fn (string $moduleKey) => $this->moduleCatalogService->find($moduleKey),
                $catalogModuleKeys
            )));

            if ($catalogModules !== [] || $featureKeys !== []) {
                $knownCatalogModuleCount++;
            }

            if ($resolverSuggestion === null && $featureKeys !== []) {
                $representativeFeatureKey = $this->pickRepresentativeFeatureKey($featureKeys);

                if ($representativeFeatureKey !== null) {
                    $resolverSuggestion = $this->upgradeSuggestionService->suggestFeature($representativeFeatureKey, $user);
                }
            }

            $checks[] = [
                'reference' => $moduleReference,
                'exists' => $exists,
                'enabled' => $enabled,
                'active' => $active,
                'state' => $active ? 'active' : ($enabled ? 'addon' : 'hidden'),
                'module_keys' => $catalogModuleKeys,
                'modules' => array_map(static function (array $module): array {
                    return [
                        'key' => $module['key'] ?? null,
                        'label' => $module['label'] ?? null,
                        'type' => $module['type'] ?? null,
                        'package_key' => $module['package_key'] ?? null,
                        'plan_gate' => $module['plan_gate'] ?? null,
                        'menu_groups' => $module['menu_groups'] ?? [],
                    ];
                }, $catalogModules),
                'feature_keys' => $featureKeys,
                'feature_count' => count($featureKeys),
            ];
        }

        $hasActiveModule = collect($checks)->contains(fn (array $check) => (bool) ($check['active'] ?? false));
        $hasEnabledModule = collect($checks)->contains(fn (array $check) => (bool) ($check['enabled'] ?? false));
        $moduleState = $hasActiveModule ? 'active' : ($hasEnabledModule ? 'addon' : 'hidden');

        $reasons = [];
        if (! $hasActiveModule) {
            if ($resolverSuggestion && data_get($resolverSuggestion, 'block.code') !== null) {
                $reasons[] = (string) data_get($resolverSuggestion, 'block.code');
            } elseif ($knownCatalogModuleCount > 0) {
                $reasons[] = $hasEnabledModule ? 'addon_required' : 'module_unavailable';
            } else {
                $reasons[] = 'module_unavailable';
            }
        }

        $message = $this->buildModuleGateMessage($resolverSuggestion, $moduleState);

        return [
            'allowed' => $hasActiveModule,
            'module_name' => implode('-', $requestedModules),
            'requested_modules' => $checks,
            'state' => $moduleState,
            'reasons' => $reasons,
            'message' => $message,
            'suggestion' => $resolverSuggestion,
            'resolved_via' => $resolverSuggestion ? 'resolver' : 'legacy',
        ];
    }

    private function routeHasTenantOverride(Request $request, User $tenantUser): bool
    {
        $companyId = $this->resolveCompanyId($tenantUser);

        if ($companyId === null) {
            return false;
        }

        $route = $request->route();
        if (! $route) {
            return false;
        }

        foreach ((array) $route->gatherMiddleware() as $middleware) {
            $middleware = trim((string) $middleware);

            if (Str::startsWith($middleware, 'feature:')) {
                $featureKey = trim(Str::after($middleware, 'feature:'));

                if ($featureKey !== '' && $this->tenantFeatureOverrideService->hasFeatureOverride($companyId, $featureKey)) {
                    return true;
                }
            }

            if (Str::startsWith($middleware, 'plan.limit:')) {
                $limitKey = trim(Str::after($middleware, 'plan.limit:'));

                if ($limitKey !== '' && $this->tenantFeatureOverrideService->hasLimitOverride($companyId, $limitKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveCompanyId(User $user): ?int
    {
        if ($user->type === 'company') {
            return (int) $user->id;
        }

        return $user->created_by ? (int) $user->created_by : null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveActiveModules(User $user): array
    {
        if ($user->isSuperAdminUser()) {
            return array_values((new Module())->allEnabled());
        }

        return Plan::getUserSubscriptionModules($user->id);
    }

    /**
     * @param array<int, string> $featureKeys
     */
    private function pickRepresentativeFeatureKey(array $featureKeys): ?string
    {
        $candidates = [];

        foreach (array_values(array_unique(array_filter(array_map('strval', $featureKeys)))) as $featureKey) {
            $feature = $this->featureResolver->find($featureKey);

            if (! is_array($feature)) {
                continue;
            }

            $candidates[] = [
                'key' => $featureKey,
                'module_count' => count((array) ($feature['modules'] ?? [])),
                'route_count' => count((array) ($feature['route_prefixes'] ?? [])),
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function (array $left, array $right): int {
            if ($left['module_count'] !== $right['module_count']) {
                return $left['module_count'] <=> $right['module_count'];
            }

            if ($left['route_count'] !== $right['route_count']) {
                return $left['route_count'] <=> $right['route_count'];
            }

            return strcmp($left['key'], $right['key']);
        });

        return $candidates[0]['key'] ?? null;
    }

    private function buildModuleGateMessage(?array $suggestion, string $moduleState): string
    {
        $message = trim((string) data_get($suggestion, 'recommendation.message', ''));

        if ($message !== '') {
            return $message;
        }

        return match ($moduleState) {
            'addon' => __('Módulo disponível no catálogo, mas ainda não activo para esta empresa.'),
            'hidden' => __('A empresa não tem este módulo activo no plano actual ou nos add-ons.'),
            default => __('A empresa não tem este módulo activo no plano actual ou nos add-ons.'),
        };
    }

    private function resolveSubscriptionState(User $user): ?string
    {
        if ((int) $user->active_plan <= 0) {
            return 'inactive';
        }

        if ($user->plan_expire_date && now()->gt(Carbon::parse($user->plan_expire_date))) {
            return 'expired';
        }

        return null;
    }

    private function buildSubscriptionGate(User $user, string $state): array
    {
        $plan = $user->active_plan ? Plan::find($user->active_plan) : null;
        $planFamily = $plan ? $this->planContractService->normalizePlanFamily($plan->name) : 'custom';
        $planFamilyLabel = $this->planContractService->familyLabel($planFamily);
        $planExpireDate = $user->plan_expire_date ? Carbon::parse($user->plan_expire_date)->toDateString() : null;
        $trialExpireDate = $user->trial_expire_date ? Carbon::parse($user->trial_expire_date)->toDateString() : null;
        $reasonCode = $state === 'expired' ? 'subscription_expired' : 'subscription_inactive';
        $label = $state === 'expired'
            ? __('Subscrição expirada')
            : __('Subscrição inactiva');
        $message = $state === 'expired'
            ? __('A subscrição desta empresa expirou. Renove o plano para continuar.')
            : __('Não existe uma subscrição activa para esta empresa. Escolha um plano para continuar.');

        $recommendation = [
            'action' => $state === 'expired' ? 'renew_subscription' : 'upgrade_plan',
            'label' => $state === 'expired' ? __('Renovar subscrição') : __('Actualizar plano'),
            'message' => $state === 'expired'
                ? __('Renove a subscrição para restaurar o acesso.')
                : __('Escolha um plano activo para restaurar o acesso.'),
            'reason_label' => $label,
            'reason_details' => [
                'plan_id' => $plan?->id,
                'plan_name' => $plan?->name,
                'plan_family' => $planFamily,
                'plan_family_label' => $planFamilyLabel,
                'plan_expire_date' => $planExpireDate,
                'trial_expire_date' => $trialExpireDate,
                'active_plan' => (int) $user->active_plan,
            ],
            'recommended_plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'family' => $planFamily,
                'family_label' => $planFamilyLabel,
            ] : null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
        ];

        $payload = [
            'blocked' => true,
            'type' => 'subscription',
            'key' => 'subscription.' . $state,
            'label' => $label,
            'message' => $message,
            'summary' => $message,
            'state' => $state,
            'subscription_state' => $state,
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name,
            'plan_family' => $planFamily,
            'plan_family_label' => $planFamilyLabel,
            'plan_expire_date' => $planExpireDate,
            'trial_expire_date' => $trialExpireDate,
            'reasons' => [$reasonCode],
            'block' => [
                'code' => $reasonCode,
                'label' => $label,
                'reasons' => [$reasonCode],
                'details' => [
                    'plan_id' => $plan?->id,
                    'plan_name' => $plan?->name,
                    'plan_family' => $planFamily,
                    'plan_family_label' => $planFamilyLabel,
                    'plan_expire_date' => $planExpireDate,
                    'trial_expire_date' => $trialExpireDate,
                    'active_plan' => (int) $user->active_plan,
                ],
            ],
            'recommendation' => $recommendation,
        ];

        $payload['cta'] = $this->contextualCtaResolverService->forRecommendation($recommendation, $payload) ?? [
            'action' => $recommendation['action'],
            'label' => $recommendation['label'],
            'message' => $recommendation['message'],
            'tone' => 'default',
        ];

        return $payload;
    }

    private function denySubscriptionAccess(Request $request, array $payload, string $redirectRoute): Response
    {
        $message = (string) ($payload['message'] ?? __('Subscription unavailable.'));

        if ($request->expectsJson()) {
            return $this->jsonSubscriptionBlockedResponse($payload, $message);
        }

        return redirect()->route($redirectRoute)
            ->with('error', $message)
            ->with('subscription_gate', $payload);
    }

    private function jsonSubscriptionBlockedResponse(array $payload, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'subscription_gate' => $payload,
        ], 403);
    }

    private function denyModuleAccess(Request $request, array $moduleGate): Response
    {
        $message = (string) ($moduleGate['message'] ?? __('Permission denied '));

        if ($request->expectsJson()) {
            return $this->jsonBlockedResponse($moduleGate, $message);
        }

        return $this->redirectBlockedResponse($request, $moduleGate, $message);
    }

    private function jsonBlockedResponse(array $moduleGate, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'module_gate' => $moduleGate,
        ], 403);
    }

    private function redirectBlockedResponse(Request $request, array $moduleGate, string $message): RedirectResponse
    {
        $redirect = $request->headers->has('referer')
            ? redirect()->back()->withInput()
            : redirect()->route('dashboard');

        return $redirect
            ->with('error', $message)
            ->with('module_gate', $moduleGate);
    }
}
