<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Cache;
use App\Classes\Module;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (!$this->isInstalled()) {
            return [];
        }

        $user = $request->user();
        $locale = $user?->lang ?? $this->getSuperAdminLang();

        if (config('app.is_demo') && Cookie::get('language')) {
            $locale = Cookie::get('language');
        }

        app()->setLocale($locale);

        $activatedPackages = $user ? ActivatedModule($user->id) : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? array_merge(
                        $user->toArray(),
                        [
                            'permissions' => $this->getUserPermissions($user),
                            'roles' => $this->getUserRoles($user),
                            'activatedPackages' => $activatedPackages,
                        ]
                    )
                    : ['activatedPackages' => []],
                'impersonating' => $request->session()->has('impersonator_id'),
                'lang' => $locale,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'packages' => $user ? (new Module())->allModules() : [],
            'adminAllSetting' => $user ? getAdminAllSetting() : getAdminAllSetting(true),
            'companyAllSetting' => $user ? getCompanyAllSetting($user->id) : [],
            'imageUrlPrefix' =>  getImageUrlPrefix(),
            'baseUrl' => url('/'),
            'currencies' => config('default_currency.currencies', []),
            'defaultLanguages' => $this->getDefaultLanguages(),
            'is_demo' => config('app.is_demo', false),
        ];
    }

    public function onException($request, $exception)
    {
        if ($exception instanceof AuthorizationException) {
            return redirect()->route('users.index')->with('error', 'Permission denied');
        }

        return parent::onException($request, $exception);
    }

    /**
     * Get user permissions (placeholder - implement based on your permission system)
     */
    private function getUserPermissions($user): array
    {
        if (method_exists($user, 'getAllPermissions')) {
            $cacheKey = 'user:permissions:' . $user->id;
            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->toArray();
            });
        }
        return [];
    }

    private function getUserRoles($user): array
    {
        if (method_exists($user, 'getRoleNames')) {
            $cacheKey = 'user:roles:' . $user->id;
            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
                return $user->getRoleNames()->toArray();
            });
        }
        return [];
    }

    private function getDefaultLanguages(): array
    {
        $languageFile = resource_path('lang/language.json');
        if (!file_exists($languageFile)) {
            return [];
        }

        $mtime = (string) filemtime($languageFile);
        $cacheKey = 'default_languages:' . $mtime;

        return Cache::rememberForever($cacheKey, function () use ($languageFile) {
            $languages = json_decode(file_get_contents($languageFile), true) ?? [];
            return array_values($languages);
        });
    }

    /**
     * Get superadmin language if user lang is not set
     */
    private function getSuperAdminLang(): string
    {
        return admin_setting('defaultLanguage') ? admin_setting('defaultLanguage') : 'en';
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}
