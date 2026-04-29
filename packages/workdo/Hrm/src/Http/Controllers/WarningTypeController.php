<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\WarningType;
use Workdo\Hrm\Http\Requests\StoreWarningTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateWarningTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateWarningType;
use Workdo\Hrm\Events\DestroyWarningType;
use Workdo\Hrm\Events\UpdateWarningType;


class WarningTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-warning-types')){
            $warningtypes = WarningType::select('id', 'warning_type_name', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-warning-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-warning-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/WarningTypes/Index', [
                'warningtypes' => $warningtypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreWarningTypeRequest $request)
    {
        if(Auth::user()->can('create-warning-types')){
            $validated = $request->validated();



            $warningtype = new WarningType();
            $warningtype->warning_type_name = $validated['warning_type_name'];

            $warningtype->creator_id = Auth::id();
            $warningtype->created_by = creatorId();
            $warningtype->save();

            CreateWarningType::dispatch($request, $warningtype);

            return redirect()->route('hrm.warning-types.index')->with('success', __('The warningtype has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.warning-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateWarningTypeRequest $request, WarningType $warningtype)
    {
        if(Auth::user()->can('edit-warning-types')){
            $validated = $request->validated();



            $warningtype->warning_type_name = $validated['warning_type_name'];

            $warningtype->save();

            UpdateWarningType::dispatch($request, $warningtype);

            return redirect()->route('hrm.warning-types.index')->with('success', __('The warningtype details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.warning-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(WarningType $warningtype)
    {
        if(Auth::user()->can('delete-warning-types')){
            DestroyWarningType::dispatch($warningtype);
            $warningtype->delete();

            return redirect()->route('hrm.warning-types.index')->with('success', __('The warningtype has been deleted.'));
        }
        else{
            return redirect()->route('hrm.warning-types.index')->with('error', __('Permission denied'));
        }
    }


}