<?php

namespace Workdo\GoogleCaptcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('Please complete the reCAPTCHA verification.');
            return;
        }

        $secretKey = admin_setting('recaptcha_secret_key');
        if (empty($secretKey)) {
            $fail('reCAPTCHA is not properly configured.');
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $result = $response->json();

        if (!$result['success']) {
            $fail('reCAPTCHA verification failed. Please try again.');
        }
    }
}