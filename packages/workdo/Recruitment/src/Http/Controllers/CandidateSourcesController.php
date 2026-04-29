<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\CandidateSources;
use Workdo\Recruitment\Http\Requests\StoreCandidateSourcesRequest;
use Workdo\Recruitment\Http\Requests\UpdateCandidateSourcesRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Events\CreateCandidateSources;
use Workdo\Recruitment\Events\UpdateCandidateSources;
use Workdo\Recruitment\Events\DestroyCandidateSources;


class CandidateSourcesController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-candidate-sources')){
            $candidatesources = CandidateSources::select('id', 'name', 'description', 'is_active', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-candidate-sources')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-candidate-sources')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Recruitment/SystemSetup/CandidateSources/Index', [
                'candidatesources' => $candidatesources,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCandidateSourcesRequest $request)
    {
        if(Auth::user()->can('create-candidate-sources')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $candidatesources = new CandidateSources();
            $candidatesources->name = $validated['name'];
            $candidatesources->description = $validated['description'];
            $candidatesources->is_active = $validated['is_active'];
            $candidatesources->is_active = $validated['is_active'];
            $candidatesources->creator_id = Auth::id();
            $candidatesources->created_by = creatorId();
            $candidatesources->save();

            CreateCandidateSources::dispatch($request, $candidatesources);

            return redirect()->route('recruitment.candidate-sources.index')->with('success', __('The candidate sources has been created successfully.'));
        }
        else{
            return redirect()->route('recruitment.candidate-sources.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCandidateSourcesRequest $request, CandidateSources $candidatesources)
    {
        if(Auth::user()->can('edit-candidate-sources')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $candidatesources->name = $validated['name'];
            $candidatesources->description = $validated['description'];
            $candidatesources->is_active = $validated['is_active'];
            $candidatesources->is_active = $validated['is_active'];
            $candidatesources->save();

            UpdateCandidateSources::dispatch($request, $candidatesources);

            return redirect()->back()->with('success', __('The candidate sources details are updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.candidate-sources.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(CandidateSources $candidatesources)
    {
        if(Auth::user()->can('delete-candidate-sources')){
            DestroyCandidateSources::dispatch($candidatesources);
            $candidatesources->delete();

            return redirect()->back()->with('success', __('The candidate sources has been deleted.'));
        }
        else{
            return redirect()->route('recruitment.candidate-sources.index')->with('error', __('Permission denied'));
        }
    }


}