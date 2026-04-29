<?php

namespace Workdo\GoogleCaptcha\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Workdo\GoogleCaptcha\Rules\RecaptchaRule;

class GoogleCaptchaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routesPath = __DIR__.'/../Routes/web.php';
        if (!$this->app->runningUnitTests() && file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
        
        $migrationsPath = __DIR__.'/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Register reCAPTCHA validation rule (only once)
        if (!app()->bound('recaptcha.rule.registered')) {
            Validator::extend('recaptcha', function ($attribute, $value, $parameters, $validator) {
                $rule = new RecaptchaRule();
                $passes = true;
                $rule->validate($attribute, $value, function ($message) use (&$passes) {
                    $passes = false;
                });
                return $passes;
            });
            app()->instance('recaptcha.rule.registered', true);
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
