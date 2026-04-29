<?php

namespace Workdo\Contract\Http\Controllers;

use Workdo\Contract\Models\ContractType;
use Workdo\Contract\Http\Requests\StoreContractTypeRequest;
use Workdo\Contract\Http\Requests\UpdateContractTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class ContractTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-contract-types')) {
            $contracttypes = ContractType::query()
                ->withCount(['contracts' => function ($query) {
                    $query->where('source_type', 'contract');
                }])
                ->with(['contracts' => function ($query) {
                    $query->where('source_type', 'contract');
                }])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-contract-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contract-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $searchTerm = request('name');
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('name', 'like', '%' . $searchTerm . '%');

                        // Extract numeric part from search term (e.g., CON0023 -> 23)
                        $numericPart = preg_replace('/[^0-9]/', '', $searchTerm);
                        if ($numericPart) {
                            $contractId = (int) ltrim($numericPart, '0'); // Remove leading zeros
                            $query->orWhereHas('contracts', function ($contractQuery) use ($contractId) {
                                $contractQuery->where('id', $contractId);
                            });
                        }
                    });
                })
                ->when(request('is_active') !== null && request('is_active') !== '', fn($q) => $q->where('is_active', request('is_active') === '1' ? 1 : 0))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Contract/ContractTypes/Index', [
                'contracttypes' => $contracttypes,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreContractTypeRequest $request)
    {
        if (Auth::user()->can('create-contract-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);


            $contracttype = new ContractType();
            $contracttype->name = $validated['name'];
            $contracttype->is_active = $validated['is_active'];
            $contracttype->creator_id = Auth::id();
            $contracttype->created_by = creatorId();
            $contracttype->save();

            return redirect()->route('contract-types.index')->with('success', __('The contract type has been created successfully.'));
        } else {
            return redirect()->route('contract-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateContractTypeRequest $request, $id)
    {
        $type = ContractType::find($id);
        
        if (!$type) {
            return redirect()->route('contract-types.index')->with('error', __('Contract type not found.'));
        }

        if (Auth::user()->can('edit-contract-types')) {
            try {
                $validated = $request->validated();
                $validated['is_active'] = $request->boolean('is_active', true);

                $type->name = $validated['name'];
                $type->is_active = $validated['is_active'];
                $type->save();

                return back()->with('success', __('The contract type details are updated successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', __('Failed to update contract type: ') . $e->getMessage());
            }
        } else {
            return redirect()->route('contract-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-contract-types')) {
            $type = ContractType::find($id);

            if (!$type) {
                return redirect()->route('contract-types.index')->with('error', __('Contract type not found.'));
            }

            try {
                // Check if there are any contracts using this type
                $contractsCount = $type->contracts()->count();
                if ($contractsCount > 0) {
                    return back()->with('error', __('Cannot delete contract type. It is being used by :count contract(s).', ['count' => $contractsCount]));
                }

                $type->delete();
                return back()->with('success', __('The contract type has been deleted.'));
            } catch (\Exception $e) {
                return back()->with('error', __('Failed to delete contract type: ') . $e->getMessage());
            }
        } else {
            return redirect()->route('contract-types.index')->with('error', __('Permission denied'));
        }
    }
}
