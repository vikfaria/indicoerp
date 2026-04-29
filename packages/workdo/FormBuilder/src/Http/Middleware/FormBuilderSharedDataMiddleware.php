<?php

namespace Workdo\FormBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Classes\Module;
use Workdo\FormBuilder\Models\Form;

class FormBuilderSharedDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (str_starts_with($request->route()?->getName() ?? '', 'formbuilder.public.')) {
            $userId = $this->getUserIdFromRequest($request);
            
            $user = User::find($userId);
            $code = $request->route('code');
            
            Inertia::share([
                'companyAllSetting' => getCompanyAllSetting($userId),
                'formCode' => $code,
                'auth' => [
                    'user' => ['activatedPackages' => ActivatedModule($userId ?? null)],
                ],
                'packages' => (new Module())->allModules(),
                'imageUrlPrefix' => $user ? getImageUrlPrefix() : url('/'),
            ]);
        }

        return $next($request);
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $code = $request->route('code');
        if ($code) {
            try {
                $form = Form::where('code', $code)->firstOrFail();
                return $form->created_by;
            } catch (\Exception $e) {
                abort(404, 'Form not found');
            }
        }
        
        abort(404, 'Form not found');
    }
}