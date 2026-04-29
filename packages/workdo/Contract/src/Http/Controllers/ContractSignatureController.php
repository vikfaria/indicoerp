<?php

namespace Workdo\Contract\Http\Controllers;

use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractSignature;
use Workdo\Contract\Http\Requests\StoreContractSignatureRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContractSignatureController extends Controller
{
    public function store(StoreContractSignatureRequest $request, $contractId)
    {
        if (Auth::user()->can('signatures-contracts')) {
            $contract = Contract::findOrFail($contractId);

            try {
                DB::transaction(function () use ($request, $contract) {
                    ContractSignature::updateOrCreate(
                        [
                            'contract_id' => $contract->id,
                            'user_id' => Auth::id(),
                        ],
                        [
                            'signature_type' => $request->signature_type,
                            'signature_data' => $request->signature_data,
                            'signed_at' => now(),
                            'creator_id' => Auth::id(),
                            'created_by' => creatorId(),
                        ]
                    );

                    $contract->refresh();

                    if ($contract->signatures()->count() >= 2) {
                        $contract->update(['status' => '1']);
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', __('Failed to sign contract. Please try again.'));
            }

            return back()->with('success', __('The contract has been signed successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
