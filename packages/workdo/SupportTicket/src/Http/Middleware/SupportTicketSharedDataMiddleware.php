<?php

namespace Workdo\SupportTicket\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Classes\Module;
use Illuminate\Support\Facades\Cache;

class SupportTicketSharedDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (str_starts_with($request->route()?->getName() ?? '', 'support-ticket.')) {
            $userId = $this->getUserIdFromRequest($request);
            $userSlug = $request->route('slug');
            $sanitizedUserSlug = $userSlug ? htmlspecialchars($userSlug, ENT_QUOTES, 'UTF-8') : null;

            Inertia::share([
                'companyAllSetting' => Cache::remember("support_ticket:company_settings:{$userId}", now()->addMinutes(10), fn () => getCompanyAllSetting($userId)),
                'userSlug' => $sanitizedUserSlug,
                'auth' => [
                    'user' => ['activatedPackages' => ActivatedModule($userId ?? null)],
                ],
                'packages' => (new Module())->allModules(),
                'imageUrlPrefix' => getImageUrlPrefix(),
            ]);
        }

        return $next($request);
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $userSlug = $request->route('slug');
        if ($userSlug) {
            try {
                return Cache::remember(
                    "support_ticket:user_id_by_slug:{$userSlug}",
                    now()->addMinutes(15),
                    function () use ($userSlug) {
                        $user = User::where('slug', $userSlug)->first();
                        if (! $user) {
                            abort(404, 'Support ticket page not found');
                        }

                        return (int) $user->id;
                    }
                );
            } catch (\Exception $e) {
                \Log::error('Database error in SupportTicketSharedDataMiddleware: ' . $e->getMessage());
                abort(500, 'Database error');
            }
        }

        abort(404, 'Support ticket page not found');
    }
}
