<?php

namespace Workdo\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractRenewal;
use Workdo\Contract\Http\Requests\StoreContractRenewalRequest;
use Workdo\Contract\Http\Requests\UpdateContractRenewalRequest;

class ContractRenewalController extends Controller
{
    public function store(StoreContractRenewalRequest $request, Contract $contract)
    {
        if (Auth::user()->can('create-contract-renewals')) {
            $renewal = ContractRenewal::create([
                'contract_id' => $contract->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'value' => $request->value,
                'status' => $request->status,
                'notes' => $request->notes,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            return back()->with('success', __('The renewal has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateContractRenewalRequest $request, ContractRenewal $renewal)
    {
        if (Auth::user()->can('edit-contract-renewals')) {
            $renewal->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'value' => $request->value,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            return back()->with('success', __('The renewal details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(ContractRenewal $renewal)
    {
        if (Auth::user()->can('delete-contract-renewals')) {
            $renewal->delete();

            return back()->with('success', __('The renewal has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
