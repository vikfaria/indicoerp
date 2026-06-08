<?php

namespace App\Console\Commands;

use App\Services\AssistantActivation\PlanContractService;
use Illuminate\Console\Command;

class InspectPlanContractCommand extends Command
{
    protected $signature = 'assistant:plan-contract {--json : Output the contract report as JSON.}';

    protected $description = 'Inspect the current plan catalog against the activation contract.';

    public function handle(PlanContractService $service): int
    {
        $report = $service->buildReport();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Assistant Activation Plan Contract');
        $this->line('Contract version: ' . $report['meta']['contract_version']);
        $this->line('Generated at: ' . $report['meta']['generated_at']);
        $this->line('Feature states: ' . implode(', ', $report['meta']['feature_states']));
        $this->line('Observed plans: ' . $report['summary']['plans_total']);
        $this->line('Priority domains: ' . $report['summary']['domains_total']);
        $this->line('Modules catalogued: ' . $report['summary']['modules_total']);

        $this->newLine();
        $this->info('Global requirements');
        foreach ($report['global_requirements'] as $requirement) {
            $this->line('- ' . $requirement);
        }

        $this->newLine();
        $this->info('Observed plans');
        foreach ($report['plans'] as $plan) {
            $this->line(sprintf(
                '- %s [%s] modules=%d users=%d storage=%dKB',
                $plan['name'],
                $plan['family'],
                $plan['module_count'],
                $plan['users_limit'],
                $plan['storage_limit_kb']
            ));

            foreach ($report['domains'] as $domain) {
                $coverage = collect($domain['plan_coverage'])->firstWhere('plan_id', $plan['id']);
                if (! $coverage) {
                    continue;
                }

                $missing = $coverage['missing_modules'] ? ' missing: ' . implode(', ', $coverage['missing_modules']) : '';
                $this->line(sprintf(
                    '  - %s: %d/%d (%d%%)%s',
                    $domain['label'],
                    $coverage['matched_modules'],
                    $coverage['total_modules'],
                    $coverage['coverage_percent'],
                    $missing
                ));
            }

            $onboardingSummary = data_get($plan, 'onboarding_steps.summary');
            if (is_array($onboardingSummary)) {
                $this->line(sprintf(
                    '  - Onboarding steps: %d available / %d total (%d required available)',
                    $onboardingSummary['available_steps_total'] ?? 0,
                    $onboardingSummary['steps_total'] ?? 0,
                    $onboardingSummary['available_required_steps_total'] ?? 0
                ));
            }
        }

        $this->newLine();
        $this->info('Priority domain contract');
        foreach ($report['domains'] as $domain) {
            $this->line(sprintf('- %s [%s]', $domain['label'], $domain['key']));
            $this->line('  modules: ' . implode(', ', $domain['recommended_modules']));
            $this->line('  permissions: ' . implode(', ', $domain['required_permissions']));
            $this->line('  config: ' . implode(', ', $domain['required_config_keys']));
        }

        $this->newLine();
        $this->info('Module catalog');
        $this->line('Version: ' . $report['module_catalog']['meta']['catalog_version']);
        $this->line('Total: ' . $report['module_catalog']['summary']['modules_total']);
        $this->line('By type: ' . json_encode($report['module_catalog']['summary']['by_type'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line('By menu group: ' . json_encode($report['module_catalog']['summary']['by_menu_group'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($report['module_catalog']['modules'] as $module) {
            $routePrefixes = $module['route_prefixes'] ? implode(', ', $module['route_prefixes']) : '-';
            $menuGroups = $module['menu_groups'] ? implode(', ', $module['menu_groups']) : '-';
            $this->line(sprintf(
                '- %s [%s] type=%s package=%s routes=%s menus=%s',
                $module['label'],
                $module['key'],
                $module['type'],
                $module['package_key'] ?? '-',
                $routePrefixes,
                $menuGroups
            ));
        }

        $this->newLine();
        $this->info('Module-feature bridge');
        $this->line('Version: ' . $report['module_feature_bridge']['meta']['catalog_version']);
        $this->line('Modules linked: ' . $report['module_feature_bridge']['summary']['modules_with_feature_links_total']);
        $this->line('Features linked: ' . $report['module_feature_bridge']['summary']['features_with_module_links_total']);
        $this->line('Links total: ' . $report['module_feature_bridge']['summary']['links_total']);

        foreach ($report['module_feature_bridge']['modules'] as $module) {
            if ($module['feature_count'] === 0) {
                continue;
            }

            $this->line(sprintf(
                '- %s [%s] features=%d',
                $module['label'],
                $module['key'],
                $module['feature_count']
            ));
        }

        $this->newLine();
        $this->info('Feature matrix');
        $this->line('Version: ' . $report['feature_matrix']['meta']['catalog_version']);
        $this->line('Features: ' . $report['feature_matrix']['summary']['features_total']);
        $this->line('Domains: ' . $report['feature_matrix']['summary']['domains_total']);
        $this->line('Modules referenced: ' . $report['feature_matrix']['summary']['modules_total']);
        $this->line('Permissions referenced: ' . $report['feature_matrix']['summary']['permissions_total']);
        $this->line('Config keys referenced: ' . $report['feature_matrix']['summary']['config_keys_total']);

        foreach ($report['feature_matrix']['domains'] as $domain) {
            $this->line(sprintf(
                '- %s [%s] features=%d',
                $domain['label'],
                $domain['key'],
                $domain['features_total']
            ));
        }

        foreach ($report['feature_matrix']['features'] as $feature) {
            $modules = $feature['modules'] ? implode(', ', $feature['modules']) : '-';
            $permissions = array_values(array_unique(array_merge($feature['permissions_all'], $feature['permissions_any'])));
            $permissionsText = $permissions ? implode(', ', $permissions) : '-';
            $configKeys = $feature['config_keys'] ? implode(', ', $feature['config_keys']) : '-';

            $this->line(sprintf(
                '- %s [%s] domain=%s modules=%s permissions=%s config=%s',
                $feature['label'],
                $feature['key'],
                $feature['domain'],
                $modules,
                $permissionsText,
                $configKeys
            ));
        }

        $this->newLine();
        $this->info('Feature state payload');
        $this->line('Surface keys: ' . implode(', ', $report['feature_state_payload']['surface_keys']));
        $this->line('State keys: ' . implode(', ', $report['feature_state_payload']['state_keys']));
        $this->line('Top-level fields: ' . implode(', ', $report['feature_state_payload']['top_level_fields']));

        $this->line('Surface shapes:');
        foreach ($report['feature_state_payload']['surface_fields'] as $surface => $fields) {
            $this->line(sprintf('- %s: %s', $surface, implode(', ', $fields)));
        }

        $this->line('Block codes: ' . implode(', ', $report['feature_state_payload']['block_codes']));

        $this->newLine();
        $this->info('Permission matrix');
        $this->line('Version: ' . $report['permission_matrix']['meta']['catalog_version']);
        $this->line('Areas: ' . $report['permission_matrix']['summary']['areas_total']);
        $this->line('Role templates: ' . $report['permission_matrix']['summary']['role_templates_total']);
        $this->line('Unique permissions: ' . $report['permission_matrix']['summary']['permissions_total']);

        foreach ($report['permission_matrix']['areas'] as $area) {
            $this->line(sprintf(
                '- %s [%s] permissions=%d',
                $area['label'],
                $area['key'],
                $area['permission_count']
            ));
        }

        foreach ($report['permission_matrix']['role_templates'] as $template) {
            $this->line(sprintf(
                '- %s [%s] permissions=%d',
                $template['label'],
                $template['name'],
                $template['permission_count']
            ));
        }

        $this->newLine();
        $this->info('Plan limits');
        $this->line('Version: ' . $report['plan_limits']['meta']['catalog_version']);
        $this->line('Families: ' . $report['plan_limits']['summary']['families_total']);
        $this->line('Dimensions: ' . $report['plan_limits']['summary']['dimensions_total']);
        $this->line('Runtime dimensions: ' . $report['plan_limits']['summary']['runtime_dimensions_total']);
        $this->line('Contract dimensions: ' . $report['plan_limits']['summary']['contract_dimensions_total']);

        foreach ($report['plan_limits']['families'] as $family) {
            $limits = collect($family['limits'])
                ->map(fn (array $limit, string $key) => sprintf('%s=%s', $key, $this->formatLimitValue($limit['value'])))
                ->implode(', ');

            $sourcePlan = $family['source_plan_name'] ? ' source=' . $family['source_plan_name'] : '';

            $this->line(sprintf(
                '- %s [%s]%s %s',
                $family['label'],
                $family['family'],
                $sourcePlan,
                $limits
            ));
        }

        $this->newLine();
        $this->info('Onboarding step registry');
        $this->line('Version: ' . $report['onboarding_steps']['meta']['catalog_version']);
        $this->line('Modules: ' . $report['onboarding_steps']['summary']['modules_total']);
        $this->line('Steps: ' . $report['onboarding_steps']['summary']['steps_total']);
        $this->line('Required steps: ' . $report['onboarding_steps']['summary']['required_steps_total']);
        $this->line('Technical modules: ' . $report['onboarding_steps']['summary']['technical_modules_total']);

        foreach ($report['onboarding_steps']['modules'] as $module) {
            $this->line(sprintf(
                '- %s [%s] steps=%d tech=%s',
                $module['label'],
                $module['key'],
                $module['step_count'],
                implode(', ', $module['technical_modules'])
            ));

            foreach ($module['steps'] as $step) {
                $this->line(sprintf(
                    '  - %s (%s)',
                    $step['label'],
                    $step['checklist_key'] ?: $step['key']
                ));
            }
        }

        $this->newLine();
        $this->info('Onboarding progress');
        $this->line('Version: ' . $report['onboarding_progress']['meta']['catalog_version']);
        $this->line('Calculation basis: ' . $report['onboarding_progress']['summary']['calculation_basis']);
        $this->line('Calculation scope: ' . $report['onboarding_progress']['summary']['calculation_scope']);
        $this->line('Step states: ' . implode(', ', $report['onboarding_progress']['step_states']));
        $this->line('Checklist states: ' . implode(', ', $report['onboarding_progress']['checklist_states']));

        $this->newLine();
        $this->info('Onboarding readiness');
        $this->line('Version: ' . $report['onboarding_readiness']['meta']['catalog_version']);
        $this->line('Calculation basis: ' . $report['onboarding_readiness']['summary']['calculation_basis']);
        $this->line('State codes: ' . implode(', ', $report['onboarding_readiness']['state_codes']));
        $this->line('Critical block types: ' . implode(', ', $report['onboarding_readiness']['critical_block_types']));
        $this->line('Critical checks: ' . $report['onboarding_readiness']['summary']['critical_config_checks_total']);
        $this->line('Ready threshold: ' . $report['onboarding_readiness']['summary']['ready_threshold']);
        $this->line('Warning threshold: ' . $report['onboarding_readiness']['summary']['warning_threshold']);
        $this->line('Blocked threshold: ' . $report['onboarding_readiness']['summary']['blocked_threshold']);

        $this->newLine();
        $this->info('Onboarding completion');
        $this->line('Version: ' . $report['onboarding_completion']['meta']['catalog_version']);
        $this->line('Calculation basis: ' . $report['onboarding_completion']['summary']['calculation_basis']);
        $this->line('Decision states: ' . implode(', ', $report['onboarding_completion']['decision_states']));
        $this->line('Blocker codes: ' . implode(', ', $report['onboarding_completion']['blocker_codes']));
        $this->line('Validation checks: ' . $report['onboarding_completion']['summary']['validation_checks_total']);

        $this->newLine();
        $this->info('Onboarding step skips');
        $this->line('Version: ' . $report['onboarding_step_skips']['meta']['catalog_version']);
        $this->line('Calculation basis: ' . $report['onboarding_step_skips']['summary']['calculation_basis']);
        $this->line('Decision states: ' . implode(', ', $report['onboarding_step_skips']['decision_states']));
        $this->line('Blocker codes: ' . implode(', ', $report['onboarding_step_skips']['blocker_codes']));
        $this->line('Audit fields: ' . implode(', ', $report['onboarding_step_skips']['audit_fields']));

        $this->newLine();
        $this->info('Readiness score formula');
        $this->line('Version: ' . $report['readiness_formula']['meta']['catalog_version']);
        $this->line('Module component weight: ' . $report['readiness_formula']['summary']['module_component_weight']);
        $this->line('Critical config weight: ' . $report['readiness_formula']['summary']['critical_config_component_weight']);
        $this->line('Module weights total: ' . $report['readiness_formula']['summary']['module_weights_total']);
        $this->line('Critical config weights total: ' . $report['readiness_formula']['summary']['critical_config_weights_total']);
        $this->line('Ready threshold: ' . $report['readiness_formula']['summary']['ready_threshold']);
        $this->line('Warning threshold: ' . $report['readiness_formula']['summary']['warning_threshold']);
        $this->line('Blocked threshold: ' . $report['readiness_formula']['summary']['blocked_threshold']);

        $this->line('Module weights:');
        foreach ($report['readiness_formula']['module_weights'] as $moduleWeight) {
            $this->line(sprintf(
                '- %s [%s] weight=%s',
                $moduleWeight['label'],
                $moduleWeight['key'],
                $this->formatWeightValue($moduleWeight['weight'])
            ));
        }

        $this->line('Critical config weights:');
        foreach ($report['readiness_formula']['critical_config_keys'] as $configKey) {
            $this->line(sprintf(
                '- %s [%s] weight=%s',
                $configKey['label'],
                $configKey['key'],
                $this->formatWeightValue($configKey['weight'])
            ));
        }

        $this->line(sprintf(
            'Examples: blank=%s, complete=%s',
            $this->formatScoreValue($report['readiness_formula']['examples']['blank']['overall_score']),
            $this->formatScoreValue($report['readiness_formula']['examples']['complete']['overall_score'])
        ));

        return self::SUCCESS;
    }

    private function formatLimitValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'n/a';
        }

        if (is_int($value) && $value === -1) {
            return 'unlimited';
        }

        return (string) $value;
    }

    private function formatWeightValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function formatScoreValue(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
