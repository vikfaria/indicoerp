<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Http\Requests\StoreTerminationTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateTerminationTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateTerminationType;
use Workdo\Hrm\Events\DestroyTerminationType;
use Workdo\Hrm\Events\UpdateTerminationType;


class TerminationTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-termination-types')){
            $terminationtypes = TerminationType::select('id', 'termination_type', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-termination-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-termination-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/TerminationTypes/Index', [
                'terminationtypes' => $terminationtypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTerminationTypeRequest $request)
    {
        if(Auth::user()->can('create-termination-types')){
            $validated = $request->validated();
            $terminationtype = new TerminationType();
            $terminationtype->termination_type = $validated['termination_type'];

            $terminationtype->creator_id = Auth::id();
            $terminationtype->created_by = creatorId();
            $terminationtype->save();

            CreateTerminationType::dispatch($request, $terminationtype);

            return redirect()->route('hrm.termination-types.index')->with('success', __('The terminationtype has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.termination-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTerminationTypeRequest $request, TerminationType $terminationtype)
    {
        if(Auth::user()->can('edit-termination-types')){
            $validated = $request->validated();



            $terminationtype->termination_type = $validated['termination_type'];

            $terminationtype->save();

            UpdateTerminationType::dispatch($request, $terminationtype);

            return redirect()->route('hrm.termination-types.index')->with('success', __('The terminationtype details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.termination-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(TerminationType $terminationtype)
    {
        if(Auth::user()->can('delete-termination-types')){
            DestroyTerminationType::dispatch($terminationtype);
            $terminationtype->delete();

            return redirect()->route('hrm.termination-types.index')->with('success', __('The terminationtype has been deleted.'));
        }
        else{
            return redirect()->route('hrm.termination-types.index')->with('error', __('Permission denied'));
        }
    }


}