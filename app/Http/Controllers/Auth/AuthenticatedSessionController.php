<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $enableRegistration = admin_setting('enableRegistration');

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'enableRegistration' => $enableRegistration === 'on',
            'isDemo' => config('app.is_demo', false),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Log login history without blocking login on external geo APIs.
        $this->logLoginHistory($request, $user);

        if ($user && $user->type === 'company') {
            $user->ensureCompanyAccessRole();
            $request->session()->put('company_role_checked', true);
        }

        if ($user && $user->isSuperAdminUser() && $this->shouldRedirectSuperAdminToUpdater()) {
            return redirect()->route('updater.index');
        }

        return redirect()->route('dashboard');

        // old code
        // return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function logLoginHistory(Request $request, $user): void
    {
        if (!$user) {
            return;
        }

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $browserData = parseBrowserData($userAgent);
        $details = array_merge($browserData, [
            'query' => $ip,
            'status' => 'success',
            'referrer_host' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_HOST) : null,
            'referrer_path' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_PATH) : null,
        ]);

        $loginHistory             = new LoginHistory();
        $loginHistory->user_id    = Auth::id();
        $loginHistory->ip         = $ip;
        $loginHistory->date       = now()->toDateString();
        $loginHistory->details    = $details;
        $loginHistory->type       = $user->type;
        $loginHistory->created_by = creatorId();
        $loginHistory->save();

        $this->enrichLoginHistoryLocationAfterResponse($loginHistory->id, (string) $ip);
    }

    private function shouldRedirectSuperAdminToUpdater(): bool
    {
        try {
            return Cache::remember('superadmin:updater:required', now()->addMinutes(5), function () {
                $hasUpdates = false;

                Artisan::call('migrate:status');
                $result = Artisan::output();
                if (strpos($result, 'Pending') !== false) {
                    $hasUpdates = true;
                }

                if (!$hasUpdates) {
                    $packagesPath = base_path('packages/workdo');
                    $folderCount = is_dir($packagesPath) ? count(glob($packagesPath . '/*', GLOB_ONLYDIR)) : 0;
                    $dbCount = \App\Models\AddOn::count();
                    $hasUpdates = $folderCount > $dbCount;
                }

                return $hasUpdates;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function enrichLoginHistoryLocationAfterResponse(int $loginHistoryId, string $ip): void
    {
        if ($this->shouldSkipGeoLookup($ip)) {
            return;
        }

        app()->terminating(function () use ($loginHistoryId, $ip): void {
            $locationData = Cache::remember(
                'login_history:geo:' . $ip,
                now()->addHours(12),
                fn() => $this->getLocationData($ip)
            );

            if (count($locationData) <= 1) {
                return;
            }

            $loginHistory = LoginHistory::find($loginHistoryId);
            if (!$loginHistory) {
                return;
            }

            $details = is_array($loginHistory->details) ? $loginHistory->details : [];
            $loginHistory->details = array_merge($details, $locationData);
            $loginHistory->save();
        });
    }

    private function shouldSkipGeoLookup(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function getLocationData(string $ip): array
    {
        try {
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query',
            ]);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? 'fail') !== 'success') {
                    return ['query' => $ip];
                }

                return [
                    'country' => $data['country'] ?? null,
                    'countryCode' => $data['countryCode'] ?? null,
                    'region' => $data['region'] ?? null,
                    'regionName' => $data['regionName'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'org' => $data['org'] ?? null,
                    'as' => $data['as'] ?? null,
                    'query' => $data['query'] ?? $ip,
                ];
            }
        } catch (\Exception $e) {
            // Ignore API errors
        }

        return ['query' => $ip];
    }
}
