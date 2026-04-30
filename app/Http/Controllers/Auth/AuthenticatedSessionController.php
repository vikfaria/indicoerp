<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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

        // Log login history
        $this->logLoginHistory($request);

        $user = Auth::user();
        if ($user && $user->type === 'company') {
            $user->ensureCompanyAccessRole();
        }

        $isSuperAdmin = $user && $user->isSuperAdminUser();

        if ($isSuperAdmin) {
            try {
                $hasUpdates = false;

                // 1. Check for migrations
                $output = Artisan::call('migrate:status');
                $result = Artisan::output();
                if (strpos($result, 'Pending') !== false) {
                    $hasUpdates = true;
                }

                // 2. Check for new packages
                if (!$hasUpdates) {
                    $packagesPath = base_path('packages/workdo');
                    $folderCount = count(glob($packagesPath . '/*', GLOB_ONLYDIR));
                    $dbCount = \App\Models\AddOn::count();
                    
                    if ($folderCount > $dbCount) {
                        $hasUpdates = true;
                    }
                }

                if ($hasUpdates) {
                    return redirect()->route('updater.index');
                }
            } catch (\Exception $e) {
                // Ignore errors in checking migrations
            }
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

    private function logLoginHistory(Request $request): void
    {
        $ip = $request->ip();
        $locationData = $this->getLocationData($ip);
        $userAgent = $request->userAgent();
        $browserData = parseBrowserData($userAgent);
        $details = array_merge($locationData, $browserData, [
            'status' => 'success',
            'referrer_host' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_HOST) : null,
            'referrer_path' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_PATH) : null,
        ]);

        $loginHistory             = new LoginHistory();
        $loginHistory->user_id    = Auth::id();
        $loginHistory->ip         = $ip;
        $loginHistory->date       = now()->toDateString();
        $loginHistory->details    = $details;
        $loginHistory->type       = Auth::user()->type;
        $loginHistory->created_by = creatorId();
        $loginHistory->save();
    }

    private function getLocationData(string $ip): array
    {
        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
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
