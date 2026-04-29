<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\DeductionType;
use Workdo\Hrm\Http\Requests\StoreDeductionTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateDeductionTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateDeductionType;
use Workdo\Hrm\Events\DestroyDeductionType;
use Workdo\Hrm\Events\UpdateDeductionType;


class DeductionTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-deduction-types')){
            $deductiontypes = DeductionType::select('id', 'name', 'description', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-deduction-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-deduction-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/DeductionTypes/Index', [
                'deductiontypes' => $deductiontypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreDeductionTypeRequest $request)
    {
        if(Auth::user()->can('create-deduction-types')){
            $validated = $request->validated();



            $deductiontype = new DeductionType();
            $deductiontype->name = $validated['name'];
            $deductiontype->description = $validated['description'];

            $deductiontype->creator_id = Auth::id();
            $deductiontype->created_by = creatorId();
            $deductiontype->save();

            CreateDeductionType::dispatch($request, $deductiontype);

            return redirect()->route('hrm.deduction-types.index')->with('success', __('The deduction type has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.deduction-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDeductionTypeRequest $request, DeductionType $deductiontype)
    {
        if(Auth::user()->can('edit-deduction-types')){
            $validated = $request->validated();
            $deductiontype->name = $validated['name'];
            $deductiontype->description = $validated['description'];

            $deductiontype->save();

            UpdateDeductionType::dispatch($request, $deductiontype);

            return redirect()->route('hrm.deduction-types.index')->with('success', __('The deduction type details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.deduction-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(DeductionType $deductiontype)
    {
        if(Auth::user()->can('delete-deduction-types')){
            DestroyDeductionType::dispatch($deductiontype);
            $deductiontype->delete();

            return redirect()->route('hrm.deduction-types.index')->with('success', __('The deduction type has been deleted.'));
        }
        else{
            return redirect()->route('hrm.deduction-types.index')->with('error', __('Permission denied'));
        }
    }


}