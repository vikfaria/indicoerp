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
     * Whitelisted admin setting keys for public (guest) pages.
     * This keeps Inertia payloads small on high-traffic routes like / and /login.
     */
    private const GUEST_ADMIN_SETTING_KEYS = [
        'defaultLanguage',
        'default_language',
        'logo_dark',
        'logo_light',
        'favicon',
        'titleText',
        'footerText',
        'sidebarVariant',
        'sidebarStyle',
        'layoutDirection',
        'themeMode',
        'themeColor',
        'customColor',
        'sidebar_variant',
        'sidebar_style',
        'layout_direction',
        'theme_mode',
        'theme_color',
        'custom_color',
        'currencySymbol',
        'currencySymbolSpace',
        'currencySymbolPosition',
        'decimalFormat',
        'decimalSeparator',
        'currency_symbol',
        'currency_symbol_space',
        'currency_symbol_position',
        'decimal_format',
        'decimal_separator',
        'enableCookiePopup',
        'strictlyNecessaryCookies',
        'cookieTitle',
        'cookieDescription',
        'strictlyCookieTitle',
        'strictlyCookieDescription',
        'contactUsDescription',
        'contactUsUrl',
        'enable_cookie_popup',
        'strictly_necessary_cookies',
        'cookie_title',
        'cookie_description',
        'strictly_cookie_title',
        'strictly_cookie_description',
        'contact_us_description',
        'contact_us_url',
    ];

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

        if (!$user) {
            $adminSettings = $this->getGuestAdminSettings();
            $locale = $this->resolveGuestLocale($adminSettings);

            if (config('app.is_demo') && Cookie::get('language')) {
                $locale = Cookie::get('language');
            }

            app()->setLocale($locale);

            return [
                ...parent::share($request),
                'auth' => [
                    'user' => ['activatedPackages' => []],
                    'impersonating' => false,
                    'lang' => $locale,
                ],
                'flash' => [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ],
                'packages' => [],
                'adminAllSetting' => $adminSettings,
                'companyAllSetting' => [],
                'imageUrlPrefix' =>  getImageUrlPrefix(),
                'baseUrl' => url('/'),
                'currencies' => [],
                'mozambiqueCompliance' => $this->getMozambiqueComplianceSettings(),
                'defaultLanguages' => fn () => $this->getDefaultLanguages(),
                'is_demo' => config('app.is_demo', false),
            ];
        }

        $locale = $user->lang ?? $this->getSuperAdminLang();

        if (config('app.is_demo') && Cookie::get('language')) {
            $locale = Cookie::get('language');
        }

        app()->setLocale($locale);

        $activatedPackages = ActivatedModule($user->id);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => array_merge(
                    $user->toArray(),
                    [
                        'permissions' => $this->getUserPermissions($user),
                        'roles' => $this->getUserRoles($user),
                        'activatedPackages' => $activatedPackages,
                    ]
                ),
                'impersonating' => $request->session()->has('impersonator_id'),
                'lang' => $locale,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'packages' => fn () => $user ? (new Module())->allModules() : [],
            'adminAllSetting' => fn () => $user ? getAdminAllSetting() : getAdminAllSetting(true),
            'companyAllSetting' => fn () => $user ? getCompanyAllSetting($user->id) : [],
            'imageUrlPrefix' =>  getImageUrlPrefix(),
            'baseUrl' => url('/'),
            'currencies' => config('default_currency.currencies', []),
            'mozambiqueCompliance' => $this->getMozambiqueComplianceSettings(),
            'defaultLanguages' => fn () => $this->getDefaultLanguages(),
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

    private function getMozambiqueComplianceSettings(): array
    {
        return [
            'gifim' => [
                'cash_threshold_mzn' => (float) config('sce.gifim.cash_threshold_mzn', 250000),
                'electronic_threshold_mzn' => (float) config('sce.gifim.electronic_threshold_mzn', 750000),
                'electronic_payment_methods' => array_values(array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    (array) config('sce.gifim.electronic_payment_methods', ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other'])
                ))),
            ],
        ];
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

    private function getGuestAdminSettings(): array
    {
        $settings = getAdminAllSetting(true);
        if (!$settings) {
            return [];
        }

        return array_intersect_key($settings, array_flip(self::GUEST_ADMIN_SETTING_KEYS));
    }

    private function resolveGuestLocale(array $adminSettings): string
    {
        $locale = $adminSettings['defaultLanguage'] ?? $adminSettings['default_language'] ?? 'en';

        return is_string($locale) && $locale !== '' ? $locale : 'en';
    }

    /**
     * Get superadmin language if user lang is not set
     */
    private function getSuperAdminLang(): string
    {
        $defaultLanguage = admin_setting('defaultLanguage');
        return $defaultLanguage ?: 'en';
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}
