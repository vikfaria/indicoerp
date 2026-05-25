<?php

namespace Workdo\FormBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Classes\Module;
use Workdo\FormBuilder\Models\Form;
use Illuminate\Support\Facades\Cache;

class FormBuilderSharedDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (str_starts_with($request->route()?->getName() ?? '', 'formbuilder.public.')) {
            $userId = $this->getUserIdFromRequest($request);
            $code = $request->route('code');

            Inertia::share([
                'companyAllSetting' => Cache::remember("formbuilder:company_settings:{$userId}", now()->addMinutes(10), fn () => getCompanyAllSetting($userId)),
                'formCode' => $code,
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
        $code = $request->route('code');
        if ($code) {
            try {
                return Cache::remember(
                    "formbuilder:user_id_by_code:{$code}",
                    now()->addMinutes(15),
                    fn () => (int) Form::where('code', $code)->firstOrFail()->created_by
                );
            } catch (\Exception $e) {
                abort(404, 'Form not found');
            }
        }

        abort(404, 'Form not found');
    }
}
