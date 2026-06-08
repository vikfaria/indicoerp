<?php

namespace App\Http\Controllers;

use App\Models\TenantFeatureOverride;
use App\Models\User;
use App\Services\AssistantActivation\FeatureCatalogService;
use App\Services\AssistantActivation\PlanLimitResolver;
use App\Services\AssistantActivation\TenantFeatureOverrideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantFeatureOverrideController extends Controller
{
    public function __construct(
        private readonly TenantFeatureOverrideService $overrideService,
        private readonly FeatureCatalogService $featureCatalogService,
        private readonly PlanLimitResolver $limitResolver
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $featureKeys = $this->featureKeys();
        $limitKeys = $this->limitKeys();
        $overrideId = $request->input('override.id');

        $uniqueOverrideRule = Rule::unique('tenant_feature_overrides', 'override_key')
            ->where(fn ($query) => $query
                ->where('company_id', $request->input('override.company_id'))
                ->where('override_type', $request->input('override.override_type')));

        if ($overrideId) {
            $uniqueOverrideRule->ignore($overrideId);
        }

        $validated = $request->validate([
            'override.id' => ['nullable', 'integer', 'exists:tenant_feature_overrides,id'],
            'override.company_id' => ['required', 'integer', 'exists:users,id'],
            'override.override_type' => ['required', Rule::in([TenantFeatureOverrideService::TYPE_FEATURE, TenantFeatureOverrideService::TYPE_LIMIT])],
            'override.override_key' => ['required', 'string', Rule::in(array_merge($featureKeys, $limitKeys)), $uniqueOverrideRule],
            'override.limit_value' => ['nullable', 'integer', 'min:0'],
            'override.notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'override.company_id.required' => __('Company is required.'),
            'override.company_id.exists' => __('Selected company does not exist.'),
            'override.override_type.required' => __('Override type is required.'),
            'override.override_key.required' => __('Override key is required.'),
            'override.override_key.in' => __('Selected override key is invalid.'),
            'override.limit_value.integer' => __('Limit value must be a number.'),
            'override.limit_value.min' => __('Limit value must be zero or greater.'),
            'override.notes.max' => __('Notes must not exceed 2000 characters.'),
        ]);

        $data = $validated['override'];
        $data['id'] = $data['id'] ?? null;

        $override = $this->overrideService->upsert($data, Auth::user());

        return redirect()
            ->back()
            ->with('success', __('Override saved successfully.'))
            ->with('tenant_feature_override_id', $override->id);
    }

    public function destroy(TenantFeatureOverride $override): RedirectResponse
    {
        $this->authorizeAccess();

        $this->overrideService->delete($override);

        return redirect()->back()->with('success', __('Override deleted successfully.'));
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();

        if (! $user || (! $user->isSuperAdminUser() && ! $user->can('manage-company-overrides'))) {
            abort(403, __('Permission denied'));
        }
    }

    /**
     * @return array<int, string>
     */
    private function featureKeys(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $feature): string => trim((string) ($feature['key'] ?? '')),
            $this->featureCatalogService->features()
        )));
    }

    /**
     * @return array<int, string>
     */
    private function limitKeys(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $dimension): string => trim((string) ($dimension['key'] ?? '')),
            $this->limitResolver->dimensions()
        )));
    }
}
