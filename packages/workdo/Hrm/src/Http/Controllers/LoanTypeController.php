<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\LoanType;
use Workdo\Hrm\Http\Requests\StoreLoanTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateLoanTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateLoanType;
use Workdo\Hrm\Events\DestroyLoanType;
use Workdo\Hrm\Events\UpdateLoanType;


class LoanTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-loan-types')){
            $loantypes = LoanType::select('id', 'name', 'description', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-loan-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-loan-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/LoanTypes/Index', [
                'loantypes' => $loantypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLoanTypeRequest $request)
    {
        if(Auth::user()->can('create-loan-types')){
            $validated = $request->validated();



            $loantype = new LoanType();
            $loantype->name = $validated['name'];
            $loantype->description = $validated['description'];

            $loantype->creator_id = Auth::id();
            $loantype->created_by = creatorId();
            $loantype->save();

            CreateLoanType::dispatch($request, $loantype);

            return redirect()->route('hrm.loan-types.index')->with('success', __('The loan type has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.loan-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateLoanTypeRequest $request, LoanType $loantype)
    {
        if(Auth::user()->can('edit-loan-types')){
            $validated = $request->validated();



            $loantype->name = $validated['name'];
            $loantype->description = $validated['description'];

            $loantype->save();

            UpdateLoanType::dispatch($request, $loantype);

            return redirect()->route('hrm.loan-types.index')->with('success', __('The loan type details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.loan-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(LoanType $loantype)
    {
        if(Auth::user()->can('delete-loan-types')){
            DestroyLoanType::dispatch($loantype);
            $loantype->delete();

            return redirect()->route('hrm.loan-types.index')->with('success', __('The loan type has been deleted.'));
        }
        else{
            return redirect()->route('hrm.loan-types.index')->with('error', __('Permission denied'));
        }
    }


}