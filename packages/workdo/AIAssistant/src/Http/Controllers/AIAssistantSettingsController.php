<?php

namespace Workdo\AIAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AIAssistantSettingsController extends Controller
{
    public function index()
    {
        $providers = [
            'openai'    => [
                'name'   => 'OpenAI',
                'models' => ['gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo']
            ],
            'anthropic' => [
                'name'   => 'Anthropic (Claude)',
                'models' => ['claude-sonnet-4-5', 'claude-opus-4-1', 'claude-3-5-haiku-latest']
            ],
            'google'    => [
                'name'   => 'Google (Gemini)',
                'models' => ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.5-flash-lite']
            ]
        ];

        return response()->json([
            'providers' => $providers
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('edit-ai-assistant-settings')) {
            $validator = Validator::make($request->all(), [
                'settings.ai_provider' => 'required|string|in:openai,anthropic,google',
                'settings.ai_model'    => 'required|string|max:255',
                'settings.ai_api_key'  => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', __('Validation failed'));
            }

            $settings = $request->input('settings', []);
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value);
                }

                return redirect()->back()->with('success', __('AI Assistant settings saved successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update AI Assistant settings: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
