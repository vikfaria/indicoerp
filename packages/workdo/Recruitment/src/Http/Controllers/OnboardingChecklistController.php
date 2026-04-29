<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\OnboardingChecklist;
use Workdo\Recruitment\Http\Requests\StoreOnboardingChecklistRequest;
use Workdo\Recruitment\Http\Requests\UpdateOnboardingChecklistRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Events\CreateOnboardingChecklist;
use Workdo\Recruitment\Events\UpdateOnboardingChecklist;
use Workdo\Recruitment\Events\DestroyOnboardingChecklist;


class OnboardingChecklistController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-onboarding-checklists')){
            $onboardingchecklists = OnboardingChecklist::select('id', 'name', 'description', 'is_default', 'status', 'created_at')
                ->withCount('checklistItems')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-onboarding-checklists')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-onboarding-checklists')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Recruitment/SystemSetup/OnboardingChecklists/Index', [
                'onboardingchecklists' => $onboardingchecklists,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreOnboardingChecklistRequest $request)
    {
        if(Auth::user()->can('create-onboarding-checklists')){
            $validated = $request->validated();

            $validated['is_default'] = $request->boolean('is_default', false);
            $validated['status'] = $request->boolean('status', true);

            $onboardingchecklist = new OnboardingChecklist();
            $onboardingchecklist->name = $validated['name'];
            $onboardingchecklist->description = $validated['description'];
            $onboardingchecklist->is_default = $validated['is_default'];
            $onboardingchecklist->status = $validated['status'];

            $onboardingchecklist->creator_id = Auth::id();
            $onboardingchecklist->created_by = creatorId();
            $onboardingchecklist->save();

            CreateOnboardingChecklist::dispatch($request, $onboardingchecklist);

            return redirect()->route('recruitment.onboarding-checklists.index')->with('success', __('The onboarding checklist has been created successfully.'));
        }
        else{
            return redirect()->route('recruitment.onboarding-checklists.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateOnboardingChecklistRequest $request, OnboardingChecklist $onboardingchecklist)
    {
        if(Auth::user()->can('edit-onboarding-checklists')){
            $validated = $request->validated();

            $validated['is_default'] = $request->boolean('is_default', false);
            $validated['status'] = $request->boolean('status', true);

            $onboardingchecklist->name = $validated['name'];
            $onboardingchecklist->description = $validated['description'];
            $onboardingchecklist->is_default = $validated['is_default'];
            $onboardingchecklist->status = $validated['status'];

            $onboardingchecklist->save();

            UpdateOnboardingChecklist::dispatch($request, $onboardingchecklist);

            return redirect()->back()->with('success', __('The onboarding checklist details are updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.onboarding-checklists.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(OnboardingChecklist $onboardingchecklist)
    {
        if(Auth::user()->can('delete-onboarding-checklists')){
            DestroyOnboardingChecklist::dispatch($onboardingchecklist);

            $onboardingchecklist->delete();

            return redirect()->back()->with('success', __('The onboarding checklist has been deleted.'));
        }
        else{
            return redirect()->route('recruitment.onboarding-checklists.index')->with('error', __('Permission denied'));
        }
    }
}