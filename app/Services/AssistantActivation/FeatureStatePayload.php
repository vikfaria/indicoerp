<?php

namespace App\Services\AssistantActivation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class FeatureStatePayload implements Arrayable
{
    private const SURFACES = ['menu', 'dashboard', 'onboarding'];

    private const STATE_LABELS = [
        'active' => 'Activo',
        'locked' => 'Bloqueado',
        'hidden' => 'Oculto',
        'addon' => 'Add-on',
    ];

    private const STATE_TONES = [
        'active' => 'success',
        'locked' => 'warning',
        'hidden' => 'muted',
        'addon' => 'info',
    ];

    public function __construct(private readonly array $payload)
    {
    }

    public static function fromResolution(array $resolution, ?array $recommendation = null, string $surface = 'menu'): self
    {
        $suggestion = self::normalizeSuggestion($resolution, $recommendation);
        $recommendationPayload = (array) data_get($suggestion, 'recommendation', []);

        $state = (string) ($resolution['state'] ?? 'hidden');
        $block = (array) ($suggestion['block'] ?? []);
        $blockCode = (string) ($block['code'] ?? self::blockCodeFromResolution($resolution));
        $blockLabel = (string) ($block['label'] ?? self::stateLabel($state));
        $reasons = array_values(array_unique(array_filter(array_map(
            fn ($reason) => trim((string) $reason),
            (array) ($resolution['reasons'] ?? [])
        ))));

        $modules = self::buildModules($resolution);
        $permissions = self::buildPermissions($resolution);
        $config = self::buildConfig($resolution);
        $subscription = self::buildSubscription($resolution);
        $plan = self::buildPlan($resolution);
        $surfaces = self::buildSurfaces($state, $blockCode, $recommendationPayload);
        $selectedSurface = $surfaces[$surface] ?? $surfaces['menu'];

        return new self([
            'key' => (string) ($resolution['key'] ?? ''),
            'slug' => self::slugify((string) ($resolution['key'] ?? '')),
            'label' => (string) ($resolution['label'] ?? ''),
            'domain' => $resolution['domain'] ?? null,
            'surface' => $surface,
            'state' => $state,
            'state_label' => self::stateLabel($state),
            'visible' => (bool) ($selectedSurface['visible'] ?? false),
            'enabled' => (bool) ($selectedSurface['enabled'] ?? false),
            'blocked' => $state !== 'active',
            'action_required' => ($recommendationPayload['action'] ?? 'no_action') !== 'no_action',
            'summary' => (string) ($recommendationPayload['message'] ?? self::defaultSummary($state)),
            'reasons' => $reasons,
            'block' => [
                'code' => $blockCode,
                'label' => $blockLabel,
                'reasons' => $reasons,
                'details' => $block['details'] ?? self::blockDetailsFromResolution($resolution),
            ],
            'subscription' => $subscription,
            'plan' => $plan,
            'modules' => $modules,
            'permissions' => $permissions,
            'config' => $config,
            'recommendation' => $recommendationPayload,
            'suggestion' => $suggestion,
            'cta' => self::buildCta($recommendationPayload),
            'surfaces' => $surfaces,
            'meta' => [
                'surface_keys' => self::SURFACES,
                'state_keys' => array_keys(self::STATE_LABELS),
                'feature_catalog_version' => (string) config('assistant_activation_features.catalog_version', 'unknown'),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public static function schema(): array
    {
        return [
            'surface_keys' => self::SURFACES,
            'state_keys' => array_keys(self::STATE_LABELS),
            'state_labels' => self::STATE_LABELS,
            'top_level_fields' => [
                'key',
                'slug',
                'label',
                'domain',
                'surface',
                'state',
                'state_label',
                'visible',
                'enabled',
                'blocked',
                'action_required',
                'summary',
                'reasons',
                'block',
                'subscription',
                'plan',
                'modules',
                'permissions',
                'config',
                'recommendation',
                'cta',
                'surfaces',
                'meta',
            ],
            'surface_fields' => [
                'menu' => ['visible', 'enabled', 'badge', 'cta'],
                'dashboard' => ['visible', 'enabled', 'badge', 'cta'],
                'onboarding' => ['visible', 'enabled', 'step_state', 'badge', 'cta'],
            ],
            'block_codes' => [
                'active',
                'addon_required',
                'module_unavailable',
                'permission_missing',
                'config_missing',
                'subscription_inactive',
                'subscription_expired',
                'feature_unknown',
                'no_user_context',
                'tenant_context_missing',
            ],
        ];
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    private static function buildModules(array $resolution): array
    {
        $checks = array_values((array) ($resolution['modules'] ?? []));

        return [
            'required' => array_values(array_unique(array_filter(array_map(
                fn ($check) => trim((string) data_get($check, 'module')),
                $checks
            )))),
            'active' => array_values(array_unique(array_filter(array_map(
                fn ($check) => data_get($check, 'state') === 'active' ? trim((string) data_get($check, 'module')) : null,
                $checks
            )))),
            'addon' => array_values(array_unique(array_filter(array_map(
                fn ($check) => data_get($check, 'state') === 'addon' ? trim((string) data_get($check, 'module')) : null,
                $checks
            )))),
            'unavailable' => array_values(array_unique(array_filter(array_map(
                fn ($check) => data_get($check, 'state') === 'hidden' ? trim((string) data_get($check, 'module')) : null,
                $checks
            )))),
            'checks' => $checks,
            'required_count' => count($checks),
            'active_count' => collect($checks)->where('state', 'active')->count(),
            'addon_count' => collect($checks)->where('state', 'addon')->count(),
            'unavailable_count' => collect($checks)->where('state', 'hidden')->count(),
            'missing' => array_values((array) ($resolution['missing_modules'] ?? [])),
        ];
    }

    private static function buildPermissions(array $resolution): array
    {
        $requiredAll = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['permissions_all'] ?? [])))));
        $requiredAny = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['permissions_any'] ?? [])))));
        $missing = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['missing_permissions'] ?? [])))));
        $granted = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['granted_permissions'] ?? [])))));

        return [
            'required_all' => $requiredAll,
            'required_any' => $requiredAny,
            'granted' => $granted,
            'missing' => $missing,
            'required_count' => count(array_unique(array_merge($requiredAll, $requiredAny))),
            'missing_count' => count($missing),
        ];
    }

    private static function buildConfig(array $resolution): array
    {
        $required = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['config_keys'] ?? [])))));
        $missing = array_values(array_unique(array_filter(array_map('trim', (array) ($resolution['missing_config_keys'] ?? [])))));
        $checks = array_values((array) ($resolution['config_checks'] ?? []));

        return [
            'required' => $required,
            'missing' => $missing,
            'checks' => $checks,
            'complete' => $missing === [],
            'required_count' => count($required),
            'missing_count' => count($missing),
        ];
    }

    private static function buildSubscription(array $resolution): array
    {
        $subscription = (array) ($resolution['subscription'] ?? []);

        return [
            'state' => (string) ($resolution['subscription_state'] ?? $subscription['state'] ?? 'inactive'),
            'plan_id' => data_get($resolution, 'subscription.plan_id'),
            'plan_name' => data_get($resolution, 'subscription.plan_name'),
            'plan_family' => data_get($resolution, 'subscription.plan_family'),
            'plan_expire_date' => data_get($resolution, 'subscription.plan_expire_date'),
            'trial_expire_date' => data_get($resolution, 'subscription.trial_expire_date'),
        ];
    }

    private static function buildPlan(array $resolution): array
    {
        $family = (string) data_get($resolution, 'subscription.plan_family', 'custom');

        return [
            'id' => data_get($resolution, 'subscription.plan_id'),
            'name' => data_get($resolution, 'subscription.plan_name'),
            'family' => $family,
            'family_label' => self::familyLabel($family),
            'state' => (string) ($resolution['subscription_state'] ?? 'inactive'),
        ];
    }

    private static function buildSurfaces(string $state, string $blockCode, array $recommendation): array
    {
        $cta = self::buildCta($recommendation);
        $badge = $state === 'active' ? null : [
            'label' => self::stateLabel($state),
            'tone' => self::stateTone($state),
        ];
        $visibleMenu = $state !== 'hidden';
        $visibleDashboard = $state !== 'hidden';
        $visibleOnboarding = $state !== 'hidden' || in_array($blockCode, [
            'module_unavailable',
            'addon_required',
            'permission_missing',
            'config_missing',
            'subscription_inactive',
            'subscription_expired',
        ], true);

        return [
            'menu' => [
                'visible' => $visibleMenu,
                'enabled' => $state === 'active',
                'badge' => $badge,
                'cta' => $cta,
                'state' => $state,
            ],
            'dashboard' => [
                'visible' => $visibleDashboard,
                'enabled' => $state === 'active',
                'badge' => $badge,
                'cta' => $cta,
                'state' => $state,
            ],
            'onboarding' => [
                'visible' => $visibleOnboarding,
                'enabled' => $state === 'active',
                'badge' => $badge,
                'cta' => $cta,
                'state' => $state === 'active' ? 'complete' : 'blocked',
                'blocked_by' => $blockCode,
                'step_state' => $state === 'active' ? 'complete' : 'blocked',
            ],
        ];
    }

    private static function normalizeSuggestion(array $resolution, ?array $suggestion): array
    {
        if (is_array($suggestion) && isset($suggestion['recommendation']) && is_array($suggestion['recommendation'])) {
            return $suggestion;
        }

        $state = (string) ($resolution['state'] ?? 'hidden');

        return [
            'type' => 'feature',
            'key' => (string) ($resolution['key'] ?? ''),
            'label' => (string) ($resolution['label'] ?? ''),
            'domain' => $resolution['domain'] ?? null,
            'state' => $state,
            'block' => [
                'code' => self::blockCodeFromResolution($resolution),
                'label' => self::stateLabel($state),
                'reasons' => array_values(array_filter(array_map('strval', (array) ($resolution['reasons'] ?? [])))),
                'details' => self::blockDetailsFromResolution($resolution),
            ],
            'recommendation' => [
                'action' => 'no_action',
                'label' => 'Sem acção',
                'reason_label' => 'Sem bloqueio',
                'reason_details' => [],
                'message' => 'Não é necessária qualquer acção adicional.',
                'recommended_plan' => null,
                'recommended_addons' => [],
                'recommended_permissions' => [],
                'recommended_config_keys' => [],
                'alternatives' => [],
            ],
            'subscription_state' => $resolution['subscription_state'] ?? null,
            'subject_user_id' => $resolution['subject_user_id'] ?? null,
            'tenant_user_id' => $resolution['tenant_user_id'] ?? null,
            'missing_permissions' => $resolution['missing_permissions'] ?? [],
            'missing_config_keys' => $resolution['missing_config_keys'] ?? [],
            'addon_modules' => $resolution['addon_modules'] ?? [],
            'unavailable_modules' => $resolution['unavailable_modules'] ?? [],
            'source' => $resolution,
        ];
    }

    private static function buildCta(array $recommendation): ?array
    {
        $action = (string) ($recommendation['action'] ?? 'no_action');

        if ($action === 'no_action') {
            return null;
        }

        return [
            'action' => $action,
            'label' => (string) ($recommendation['label'] ?? self::stateLabel('locked')),
            'message' => (string) ($recommendation['message'] ?? ''),
        ];
    }

    private static function blockCodeFromResolution(array $resolution): string
    {
        $reasons = array_values(array_map('strval', (array) ($resolution['reasons'] ?? [])));

        return $reasons[0] ?? 'unknown';
    }

    private static function blockDetailsFromResolution(array $resolution): array
    {
        return [
            'missing_modules' => array_values((array) ($resolution['missing_modules'] ?? [])),
            'addon_modules' => array_values((array) ($resolution['addon_modules'] ?? [])),
            'unavailable_modules' => array_values((array) ($resolution['unavailable_modules'] ?? [])),
            'missing_permissions' => array_values((array) ($resolution['missing_permissions'] ?? [])),
            'missing_config_keys' => array_values((array) ($resolution['missing_config_keys'] ?? [])),
        ];
    }

    private static function stateLabel(string $state): string
    {
        return self::STATE_LABELS[$state] ?? Str::of($state)->replace('_', ' ')->title()->toString();
    }

    private static function stateTone(string $state): string
    {
        return self::STATE_TONES[$state] ?? 'neutral';
    }

    private static function defaultSummary(string $state): string
    {
        return match ($state) {
            'active' => 'Funcionalidade disponível.',
            'addon' => 'Funcionalidade disponível apenas com add-on.',
            'locked' => 'Funcionalidade bloqueada por configuração, permissões ou subscrição.',
            'hidden' => 'Funcionalidade oculta no contexto actual.',
            default => 'Estado da funcionalidade indefinido.',
        };
    }

    private static function familyLabel(string $familyKey): string
    {
        $family = (array) config('assistant_activation.plan_families.' . $familyKey, []);

        if (isset($family['label']) && is_string($family['label']) && $family['label'] !== '') {
            return $family['label'];
        }

        return Str::of($familyKey)->replace('_', ' ')->title()->toString();
    }

    private static function slugify(string $value): string
    {
        return Str::of($value)->replace(['.', '_'], '-')->lower()->toString();
    }
}
