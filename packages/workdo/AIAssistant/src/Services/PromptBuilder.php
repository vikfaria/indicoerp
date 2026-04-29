<?php

namespace Workdo\AIAssistant\Services;

use Workdo\AIAssistant\Models\AIPrompt;

class PromptBuilder
{
    public function buildPrompt($fieldType, $settings, $context = [])
    {
        $basePrompt     = $this->getBasePrompt($fieldType);
        $contextPrompt  = $this->buildContextPrompt($context);
        $settingsPrompt = $this->buildSettingsPrompt($settings);

        return $basePrompt . $contextPrompt . $settingsPrompt;
    }

    private function getBasePrompt($fieldType)
    {
        // Get current module from request or context
        $module    = $this->getCurrentModule();
        $submodule = $this->getCurrentSubmodule();

        // Get prompt from database
        return AIPrompt::getPrompt($module, $fieldType, $submodule);
    }

    private function getCurrentModule()
    {
        // Get module from request context (passed from frontend)
        return request()->input('module', null);
    }

    private function getCurrentSubmodule()
    {
        // Get submodule from request context (passed from frontend)
        return request()->input('submodule', null);
    }

    private function buildContextPrompt($context)
    {
        if (empty($context)) {
            return '';
        }

        $contextParts = [];
        foreach ($context as $key => $value) {
            // Skip ID fields as they're not useful in prompts
            if (str_ends_with($key, '_id') || str_ends_with($key, '_ids') || $key === 'id') {
                continue;
            }

            if (!empty($value)) {
                // Handle array values by converting to string
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $contextParts[] = "{$key}: {$value}";
            }
        }

        return $contextParts ? "\n\nContext: " . implode(', ', $contextParts) : '';
    }

    private function buildSettingsPrompt($settings)
    {
        $prompt = '';

        if (!empty($settings['language']) && $settings['language'] !== 'english') {
            $prompt  .= "\n\nGenerate the response in {$settings['language']} language.";
        }

        if (!empty($settings['maxLength'])) {
            $prompt  .= "\n\nKeep the response under {$settings['maxLength']} characters.";
        }

        if (!empty($settings['fieldContent'])) {
            $prompt  .= "\n\nAdditional context: {$settings['fieldContent']}";
        }

        return $prompt;
    }
}
