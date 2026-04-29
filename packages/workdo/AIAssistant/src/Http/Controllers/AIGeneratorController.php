<?php

namespace Workdo\AIAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Workdo\AIAssistant\Services\AIService;

class AIGeneratorController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generate(Request $request)
    {
        try {
            $fieldType = $request->input('fieldType');
            $settings  = $request->input('settings', []);
            $context   = $request->input('context', []);

            $results = $this->aiService->generateContent($fieldType, $settings, $context);

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => __('Content generated successfully!')
            ]);

        } catch (\Exception $e) {
            \Log::error('AI Generation Error: ' . $e->getMessage());

            $errorMessage = $this->getFormattedErrorMessage($e->getMessage());

            return response()->json([
                'success'    => false,
                'error'      => $errorMessage,
                'show_toast' => true
            ], 422);
        }
    }

    private function getFormattedErrorMessage($message)
    {
        // Configuration errors
        if (str_contains($message, 'AI configuration')) {
            return __('Please configure AI settings first (Provider, Model, API Key).');
        }

        // API key errors
        if (str_contains($message, 'API key') || str_contains($message, 'authentication') || str_contains($message, 'unauthorized')) {
            return __('Invalid API key. Please check your AI configuration.');
        }

        // Rate limit errors
        if (str_contains($message, 'rate limit') || str_contains($message, 'quota')) {
            return __('API rate limit exceeded. Please try again later.');
        }

        // Content safety errors
        if (str_contains($message, 'safety filters') || str_contains($message, 'blocked')) {
            return __('Content was blocked by safety filters. Please modify your request.');
        }

        // Token limit errors
        if (str_contains($message, 'token limit') || str_contains($message, 'MAX_TOKENS')) {
            return __('Response too long. Please increase Maximum Length or simplify your request.');
        }

        // Model errors
        if (str_contains($message, 'model') && str_contains($message, 'not found')) {
            return __('Selected AI model is not available. Please choose a different model.');
        }

        // Network/API errors
        if (str_contains($message, 'HTTP error') || str_contains($message, 'network') || str_contains($message, 'timeout')) {
            return __('Unable to connect to AI service. Please check your internet connection and try again.');
        }

        // Generic API errors
        if (str_contains($message, 'API error')) {
            return __('AI service is temporarily unavailable. Please try again later.');
        }

        // Default fallback
        return __('Failed to generate content. Please try again or contact support if the issue persists.');
    }
}
