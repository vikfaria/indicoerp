<?php

namespace Workdo\AIAssistant\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    public function generateContent($fieldType, $settings, $context = [])
    {
        $provider = company_setting('ai_provider');
        $model    = company_setting('ai_model');
        $apiKey   = company_setting('ai_api_key');

        if (!$provider || !$model || !$apiKey) {
            throw new \Exception('AI configuration not found');
        }

        $promptBuilder = new PromptBuilder();
        $prompt        = $promptBuilder->buildPrompt($fieldType, $settings, $context);

        return $this->callAI($provider, $model, $apiKey, $prompt, $settings);
    }

    private function callAI($provider, $model, $apiKey, $prompt, $settings)
    {
        switch ($provider) {
            case 'openai':
                return $this->callOpenAI($model, $apiKey, $prompt, $settings);
            case 'anthropic':
                return $this->callAnthropic($model, $apiKey, $prompt, $settings);
            case 'google':
                return $this->callGoogle($model, $apiKey, $prompt, $settings);
            default:
                throw new \Exception('Unsupported AI provider');
        }
    }

    private function callOpenAI($model, $apiKey, $prompt, $settings)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens'  => $settings['maxLength'],
                    'temperature' => $this->getTemperature($settings['creativity']),
                    'n'           => $settings['resultCount']
                ]);

        if ($response->successful()) {
            $data = $response->json();
            return collect($data['choices'])->pluck('message.content')->toArray();
        }

        throw new \Exception('OpenAI API error: ' . $response->body());
    }

    private function callAnthropic($model, $apiKey, $prompt, $settings)
    {
        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'Content-Type'      => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
                    'model'       => $model,
                    'max_tokens'  => $settings['maxLength'],
                    'temperature' => $this->getTemperature($settings['creativity']),
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ]);

        if ($response->successful()) {
            $data    = $response->json();
            $results = [];
            for ($i = 0; $i < $settings['resultCount']; $i++) {
                $results[] = $data['content'][0]['text'] ?? '';
            }
            return $results;
        }

        throw new \Exception('Anthropic API error: ' . $response->body());
    }

    private function callGoogle($model, $apiKey, $prompt, $settings)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents'         => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'     => $this->getTemperature($settings['creativity']),
                        'maxOutputTokens' => max(2000, intval($settings['maxLength']))
                    ]
                ]);

        if ($response->successful()) {
            $data = $response->json();

            // Check for API errors in response
            if (isset($data['error'])) {
                throw new \Exception('Google API error: ' . $data['error']['message']);
            }

            $results = [];
            if (isset($data['candidates']) && !empty($data['candidates'])) {
                foreach ($data['candidates'] as $candidate) {
                    // Check if content was blocked
                    if (isset($candidate['finishReason']) && $candidate['finishReason'] === 'SAFETY') {
                        throw new \Exception('Content was blocked by safety filters');
                    }

                    // Handle different response structures
                    $text = '';
                    if (isset($candidate['content']['parts'][0]['text'])) {
                        $text = trim($candidate['content']['parts'][0]['text']);
                    } elseif (isset($candidate['finishReason']) && $candidate['finishReason'] === 'MAX_TOKENS') {
                        $text = 'Content truncated. Increase Maximum Length to 2000+ for full content.';
                    }

                    if (!empty($text)) {
                        $results[] = $text;
                    }
                }
            }

            if (empty($results)) {
                throw new \Exception('No content generated. Try increasing Maximum Length to 2000+ or simplify your prompt.');
            }

            return $results;
        }

        $errorBody = $response->body();
        \Log::error('Google API HTTP Error:', ['status' => $response->status(), 'body' => $errorBody]);
        throw new \Exception('Google API HTTP error (' . $response->status() . '): ' . $errorBody);
    }

    private function getTemperature($creativity)
    {
        return match ($creativity) {
            'low' => 0.3,
            'medium' => 0.7,
            'high' => 1.0,
            default => 0.7
        };
    }
}
