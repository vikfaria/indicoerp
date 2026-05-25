<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class PlanModuleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$moduleName = null): Response
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        if ($user->type === 'company' && !$request->session()->get('company_role_checked')) {
            try {
                $user->ensureCompanyAccessRole();
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                $request->session()->put('company_role_checked', true);
            }
        }

        // Skip check for superadmin
        if ($user->isSuperAdminUser()) {
            return $next($request);
        } elseif ($user->hasRole('company')) {
            if (($user->plan_expire_date && now()->gt($user->plan_expire_date)) || ($user->active_plan == 0)) {
                // Plan expired - only allow essential plan routes
                $allowedRoutes = ['users.leave-impersonation','plans.index', 'plans.subscribe', 'plans.start-trial', 'plans.apply-coupon', 'payment.*.store','payment.*.status', 'bank-transfer.index','plans.assign-free'];
                if (!$request->routeIs($allowedRoutes)) {
                    return redirect()->route('plans.index')
                        ->with('error', 'Your plan has expired. Please renew your subscription.');
                }
            }
        } else {
            // For sub-users - check creator's plan
            $creator = $user->createdBy;
            if ($creator && ($creator->plan_expire_date && now()->gt($creator->plan_expire_date) || ($creator->active_plan == 0))) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', 'Company plan has expired. Please contact your administrator.');
            }
        }

        if($moduleName != null)
        {
            $requestedModules = array_filter(explode('-', $moduleName));
            $activeModules = array_flip(ActivatedModule($user->id));

            foreach ($requestedModules as $module) {
                if (isset($activeModules[$module]) && module_is_active($module)) {
                    return $next($request);
                }
            }

            return redirect()->route('dashboard')->with('error', __('Permission denied '));
        }

        $response = $next($request);
        return $response;
    }
}
