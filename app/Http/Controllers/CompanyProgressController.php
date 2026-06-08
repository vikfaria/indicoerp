<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AssistantActivation\CompanyProgressOverviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProgressController extends Controller
{
    public function index(Request $request, CompanyProgressOverviewService $overviewService): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && ($user->isSuperAdminUser() || $user->can('view-company-onboarding-progress')),
            403,
            __('Permission denied')
        );

        return Inertia::render('assistant-activation/company-progress', [
            'overview' => $overviewService->snapshot($user, $request->only([
                'search',
                'company_id',
                'per_page',
                'page',
            ])),
        ]);
    }
}
