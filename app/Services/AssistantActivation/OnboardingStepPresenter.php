<?php

namespace App\Services\AssistantActivation;

use App\Models\User;

final class OnboardingStepPresenter
{
    private const ADDON_MODULE_KEYS = [
        'ProductService',
        'Contract',
    ];

    public function __construct(
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    public function presentModuleStep(array $module, array $step, ?User $user = null): array
    {
        $route = $this->resolveStepRoute((string) data_get($step, 'key', ''));
        $available = (bool) data_get($step, 'available', false);
        $state = (string) data_get($step, 'state', 'pending');
        $progressPercent = (float) data_get($step, 'progress_percent', 0);
        $permissionResolution = $this->buildPermissionResolution($step, $user);
        $action = $this->buildAction($module, $step, $route, $available, $state, $progressPercent, $permissionResolution);
        $block = $this->buildBlock($module, $step, $route, $available, $state, $progressPercent, $permissionResolution);

        return array_merge($step, [
            'module_key' => data_get($module, 'key'),
            'module_label' => data_get($module, 'label'),
            'permission_state' => $permissionResolution['state'],
            'granted_permissions' => $permissionResolution['granted'],
            'missing_permissions' => $permissionResolution['missing'],
            'action' => $action,
            'block' => $block,
        ]);
    }

    public function presentConfigBlock(array $block): array
    {
        $checklistKey = (string) data_get($block, 'key', '');
        $route = $this->resolveChecklistRoute($checklistKey);
        $moduleLabel = (string) data_get($block, 'label', '');
        $ownerModules = array_values((array) data_get($block, 'owner_modules', []));
        $reason = (string) data_get($block, 'reason', '');
        $message = (string) data_get($block, 'message', 'Configuração em falta.');
        $actionLabel = $route ? 'Corrigir configuração' : 'Rever configuração';

        return [
            'type' => 'config_block',
            'module_key' => $ownerModules[0] ?? null,
            'module_label' => $moduleLabel,
            'key' => $checklistKey,
            'label' => $moduleLabel,
            'state' => 'blocked',
            'state_label' => 'Bloqueado',
            'progress_percent' => 0.0,
            'checklist_key' => $checklistKey,
            'description' => $message,
            'evidence' => $reason,
            'required' => true,
            'available' => true,
            'items_total' => 0,
            'items_completed_total' => 0,
            'items_skipped_total' => 0,
            'items_blocked_total' => 0,
            'items_pending_total' => 0,
            'items_not_applicable_total' => 0,
            'action' => [
                'kind' => 'complete_configuration',
                'label' => $actionLabel,
                'href' => $route,
                'tone' => 'default',
                'disabled' => $route === null,
                'message' => $route
                    ? 'Abra o ecrã correspondente para concluir a configuração.'
                    : 'Abra a área de configuração adequada para concluir este passo.',
            ],
            'block' => [
                'code' => 'config_missing',
                'label' => 'Configuração em falta',
                'message' => $message,
                'details' => [
                    'reason' => $reason,
                    'owner_modules' => $ownerModules,
                    'checklist_key' => $checklistKey,
                    'route' => $route,
                ],
            ],
        ];
    }

    public function presentCompletionBlock(array $block): array
    {
        $code = (string) data_get($block, 'code', 'completion_block');
        $label = (string) data_get($block, 'label', 'Bloqueio de conclusão');
        $details = (array) data_get($block, 'details', []);
        $reason = (string) data_get($details, 'reason', data_get($block, 'message', 'Bloqueio de conclusão.'));

        return [
            'type' => 'completion_block',
            'module_key' => null,
            'module_label' => null,
            'key' => $code,
            'label' => $label,
            'state' => 'blocked',
            'state_label' => 'Bloqueado',
            'progress_percent' => 0.0,
            'checklist_key' => $code,
            'description' => $reason,
            'evidence' => $code,
            'required' => true,
            'available' => true,
            'items_total' => 0,
            'items_completed_total' => 0,
            'items_skipped_total' => 0,
            'items_blocked_total' => 0,
            'items_pending_total' => 0,
            'items_not_applicable_total' => 0,
            'action' => [
                'kind' => 'review',
                'label' => 'Ver onboarding',
                'href' => route('dashboard'),
                'tone' => 'ghost',
                'disabled' => false,
                'message' => 'Volte ao painel principal para rever o estado global.',
            ],
            'block' => [
                'code' => $code,
                'label' => $label,
                'message' => $reason,
                'details' => $details,
            ],
        ];
    }

    private function buildAction(
        array $module,
        array $step,
        ?string $route,
        bool $available,
        string $state,
        float $progressPercent,
        array $permissionResolution
    ): array {
        if (! $available) {
            $usesAddon = $this->requiresAddon($module, $step);

            return [
                'kind' => $usesAddon ? 'activate_addon' : 'upgrade_plan',
                'label' => $usesAddon ? 'Activar módulo' : 'Actualizar plano',
                'href' => $usesAddon ? route('add-ons.index') : route('plans.index'),
                'tone' => 'secondary',
                'disabled' => false,
                'message' => $usesAddon
                    ? 'Active o módulo necessário para disponibilizar este passo.'
                    : 'Actualize o plano para expor este passo no onboarding.',
            ];
        }

        if ($this->isResolvedState($state) || $progressPercent >= 100.0) {
            return [
                'kind' => 'review',
                'label' => $route ? 'Ver detalhe' : 'Concluído',
                'href' => $route,
                'tone' => 'ghost',
                'disabled' => $route === null,
                'message' => 'Este passo já foi concluído.',
            ];
        }

        if (($permissionResolution['state'] ?? 'unassessed') === 'missing') {
            return [
                'kind' => 'grant_permission',
                'label' => 'Gerir permissões',
                'href' => route('roles.index'),
                'tone' => 'outline',
                'disabled' => false,
                'message' => 'Abra os perfis de acesso para atribuir as permissões em falta.',
            ];
        }

        if ($state === 'blocked') {
            return [
                'kind' => 'resolve_blocker',
                'label' => 'Resolver bloqueio',
                'href' => $route,
                'tone' => 'outline',
                'disabled' => $route === null,
                'message' => 'Abra o ecrã correspondente para resolver os itens em falta.',
            ];
        }

        $label = $progressPercent > 0.0 ? 'Continuar' : 'Iniciar';

        return [
            'kind' => $progressPercent > 0.0 ? 'continue' : 'start',
            'label' => $label,
            'href' => $route,
            'tone' => 'default',
            'disabled' => $route === null,
            'message' => $route
                ? 'Continue a execução neste ecrã.'
                : 'Este passo não tem um destino directo configurado.',
        ];
    }

    private function buildBlock(
        array $module,
        array $step,
        ?string $route,
        bool $available,
        string $state,
        float $progressPercent,
        array $permissionResolution
    ): array {
        if (! $available) {
            $usesAddon = $this->requiresAddon($module, $step);

            return [
                'code' => $usesAddon ? 'addon_required' : 'upgrade_plan',
                'label' => $usesAddon ? 'Add-on obrigatório' : 'Plano necessário',
                'message' => $usesAddon
                    ? 'Este passo depende de um módulo adicional que não está activo no plano actual.'
                    : 'Este passo ainda não está disponível no plano actual.',
                'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent),
            ];
        }

        if ($this->isResolvedState($state) || $progressPercent >= 100.0) {
            return [
                'code' => 'completed',
                'label' => 'Concluído',
                'message' => 'Este passo já foi finalizado.',
                'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent),
            ];
        }

        if (($permissionResolution['state'] ?? 'unassessed') === 'missing') {
            return [
                'code' => 'permission_missing',
                'label' => 'Permissão em falta',
                'message' => 'O utilizador actual não tem permissões suficientes para concluir este passo.',
                'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent, $permissionResolution),
            ];
        }

        if ($state === 'blocked') {
            $blockedItems = (int) data_get($step, 'items_blocked_total', 0);
            $pendingItems = (int) data_get($step, 'items_pending_total', 0);

            return [
                'code' => 'blocked_step',
                'label' => 'Bloqueado',
                'message' => $blockedItems > 0
                    ? sprintf('%d item(s) continuam bloqueados neste passo.', $blockedItems)
                    : ($pendingItems > 0
                        ? sprintf('%d item(s) continuam pendentes neste passo.', $pendingItems)
                        : 'Existem bloqueios operacionais neste passo.'),
                'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent),
            ];
        }

        if ($progressPercent > 0.0) {
            return [
                'code' => 'in_progress',
                'label' => 'Em progresso',
                'message' => 'Este passo está em execução.',
                'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent),
            ];
        }

        return [
            'code' => 'ready_to_start',
            'label' => 'Pronto para iniciar',
            'message' => 'Não existem bloqueios conhecidos para este passo.',
            'details' => $this->buildDetails($module, $step, $route, $available, $state, $progressPercent),
        ];
    }

    private function buildDetails(
        array $module,
        array $step,
        ?string $route,
        bool $available,
        string $state,
        float $progressPercent,
        array $permissionResolution = []
    ): array {
        return [
            'module_key' => data_get($module, 'key'),
            'module_label' => data_get($module, 'label'),
            'module_refs' => array_values((array) data_get($step, 'module_refs', [])),
            'technical_modules' => array_values((array) data_get($module, 'technical_modules', [])),
            'checklist_key' => data_get($step, 'checklist_key'),
            'required' => (bool) data_get($step, 'required', false),
            'available' => $available,
            'state' => $state,
            'progress_percent' => $progressPercent,
            'required_permissions' => array_values((array) data_get($step, 'permissions', [])),
            'granted_permissions' => array_values((array) ($permissionResolution['granted'] ?? [])),
            'missing_permissions' => array_values((array) ($permissionResolution['missing'] ?? [])),
            'permission_state' => (string) ($permissionResolution['state'] ?? 'unassessed'),
            'permission_route' => route('roles.index'),
            'items_total' => (int) data_get($step, 'items_total', 0),
            'items_completed_total' => (int) data_get($step, 'items_completed_total', 0),
            'items_skipped_total' => (int) data_get($step, 'items_skipped_total', 0),
            'items_blocked_total' => (int) data_get($step, 'items_blocked_total', 0),
            'items_pending_total' => (int) data_get($step, 'items_pending_total', 0),
            'items_not_applicable_total' => (int) data_get($step, 'items_not_applicable_total', 0),
            'route' => $route,
        ];
    }

    /**
     * @param array<string, mixed> $step
     */
    private function buildPermissionResolution(array $step, ?User $user): array
    {
        $required = array_values(array_unique(array_filter(array_map(
            static fn ($permission): string => trim((string) $permission),
            (array) data_get($step, 'permissions', [])
        ))));

        if ($required === []) {
            return [
                'state' => 'not_applicable',
                'required' => [],
                'granted' => [],
                'missing' => [],
            ];
        }

        if (! $user) {
            return [
                'state' => 'unassessed',
                'required' => $required,
                'granted' => [],
                'missing' => [],
            ];
        }

        $granted = array_values(array_unique(array_filter(array_map(
            static fn ($permission): string => trim((string) $permission),
            $user->getAllPermissions()->pluck('name')->all()
        ))));
        $missing = array_values(array_diff($required, $granted));

        return [
            'state' => $missing === [] ? 'granted' : 'missing',
            'required' => $required,
            'granted' => $granted,
            'missing' => $missing,
        ];
    }

    private function isResolvedState(string $state): bool
    {
        return in_array($state, ['completed', 'skipped', 'not_applicable'], true);
    }

    private function requiresAddon(array $module, array $step): bool
    {
        $moduleRefs = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) data_get($step, 'module_refs', [])
        )));
        $technicalModules = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) data_get($module, 'technical_modules', [])
        )));

        return array_intersect(array_merge($moduleRefs, $technicalModules), self::ADDON_MODULE_KEYS) !== [];
    }

    private function resolveStepRoute(string $stepKey): ?string
    {
        return $this->contextualCtaResolverService->resolveStepRoute($stepKey);
    }

    private function resolveChecklistRoute(string $checklistKey): ?string
    {
        return $this->contextualCtaResolverService->resolveChecklistRoute($checklistKey);
    }
}
