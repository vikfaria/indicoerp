<?php

namespace Workdo\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class AIPrompt extends Model
{
    protected $table = 'ai_prompts';

    protected $fillable = [
        'module',
        'submodule',
        'field_type',
        'prompt_template',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public static function getPrompt($module, $fieldType, $submodule = null)
    {
        // Try module + submodule specific prompt first
        if ($submodule) {
            $prompt = self::where('module', $module)
                ->where('submodule', $submodule)
                ->where('field_type', $fieldType)
                ->where('status', true)
                ->first();

            if ($prompt) {
                return $prompt->prompt_template;
            }
        }

        // Try module-specific prompt (without submodule)
        $prompt = self::where('module', $module)
            ->whereNull('submodule')
            ->where('field_type', $fieldType)
            ->where('status', true)
            ->first();

        // Fallback to general prompt
        if (!$prompt) {
            $prompt = self::where('module', 'general')
                ->whereNull('submodule')
                ->where('field_type', $fieldType)
                ->where('status', true)
                ->first();
        }

        return $prompt?->prompt_template ?? __('Generate professional content for this field.');
    }
}
