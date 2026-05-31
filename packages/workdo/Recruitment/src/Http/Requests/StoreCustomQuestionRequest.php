<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => [
                'required',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->containsDiscriminatoryCriterion((string) $value)) {
                        $fail(__('This question contains a potentially discriminatory criterion. Use objective and job-related criteria only.'));
                    }
                },
            ],
            'type' => 'required',
            'options' => 'nullable',
            'is_required' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|min:0'
        ];
    }

    private function containsDiscriminatoryCriterion(string $question): bool
    {
        $normalized = $this->normalizeText($question);

        $blockedTerms = [
            'gender',
            'genero',
            'sexo',
            'raca',
            'etnia',
            'religiao',
            'religion',
            'filiacao partidaria',
            'partido politico',
            'partidaria',
            'filiacao sindical',
            'sindical',
            'origem social',
            'estado civil',
            'marital status',
        ];

        foreach ($blockedTerms as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $ascii = strtr(mb_strtolower($value), [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', trim($ascii)) ?? '';
    }
}
