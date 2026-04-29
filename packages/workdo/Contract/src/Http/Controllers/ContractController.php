<?php

namespace Workdo\Contract\Http\Controllers;

use Workdo\Contract\Models\Contract;
use Workdo\Contract\Http\Requests\StoreContractRequest;
use Workdo\Contract\Http\Requests\UpdateContractRequest;
use Workdo\Contract\Http\Requests\DuplicateContractRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Contract\Events\StatusChangeContract;
use Workdo\Contract\Events\CreateContract;
use Workdo\Contract\Events\UpdateContract;
use Workdo\Contract\Events\DestroyContract;
use Workdo\Contract\Models\ContractType;

class ContractController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-contracts')) {
            $contract = Contract::query()
                ->with(['user', 'contractType'])
                ->where('source_type', 'contract')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-contracts')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contracts')) {
                        $q->where('user_id', Auth::id())->orWhere('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('subject'), function ($q) {
                    $searchTerm = request('subject');
                    $numericSearch = preg_replace('/[^0-9.]/', '', $searchTerm);

                    $searchTerm = request('subject');
                    $numericSearch = preg_replace('/[^0-9.]/', '', $searchTerm);

                    $q->where(function ($query) use ($searchTerm, $numericSearch) {
                        $query->where('subject', 'like', '%' . $searchTerm . '%')
                            ->orWhere('description', 'like', '%' . $searchTerm . '%')
                            ->orWhere('contract_number', 'like', '%' . $searchTerm . '%');
                        if ($numericSearch) {
                            $query->orWhere('value', 'like', '%' . $numericSearch . '%');
                        }
                    });
                })
                ->when(request('type_id') && request('type_id') !== '', fn($q) => $q->where('type_id', request('type_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('user_id') && request('user_id') !== '', fn($q) => $q->where('user_id', request('user_id')))
                ->when(request('start_date') && request('start_date') !== '', fn($q) => $q->whereDate('start_date', '>=', request('start_date')))
                ->when(request('end_date') && request('end_date') !== '', fn($q) => $q->whereDate('end_date', '<=', request('end_date')))

                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = User::select('id', 'name')->where(function ($q) {
                if (Auth::user()->id == creatorId()) {
                    $q->where('created_by', creatorId());
                } else {
                    $q->where('id', Auth::id());
                }
            })->get();

            $contracttypes = ContractType::where(function ($q) {
                if (Auth::user()->can('manage-any-contract-types')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-contract-types')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->where('is_active', true)->select('id', 'name')->get();

            return Inertia::render('Contract/Contracts/Index', [
                'contracts' => $contract,
                'users' => $users,
                'contracttypes' => $contracttypes,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreContractRequest $request)
    {
        if (Auth::user()->can('create-contracts')) {
            $validated = $request->validated();

            $contract = new Contract();
            $contract->fill($validated);
            $contract->creator_id = Auth::id();
            $contract->created_by = creatorId();
            $contract->save();

            CreateContract::dispatch($request, $contract);

            return redirect()->route('contract.index')->with('success', __('The contract has been created successfully.'));
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateContractRequest $request, $id)
    {
        if (Auth::user()->can('edit-contracts')) {
            $contract = Contract::find($id);

            if (!$contract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            $validated = $request->validated();

            $contract->fill($validated);
            $contract->save();
            
            UpdateContract::dispatch($request, $contract);

            return redirect()->back()->with('success', __('The contract details are updated successfully.'));
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Request $request, $id)
    {
        if (Auth::user()->can('edit-contracts')) {
            $contract = Contract::find($id);

            if (!$contract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            $request->validate([
                'status' => 'required|string'
            ]);

            $contract->status = $request->status;
            $contract->save();

            StatusChangeContract::dispatch($request, $contract);

            return redirect()->back()->with('success', __('Contract status updated successfully.'));
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-contracts')) {
            $contract = Contract::find($id);

            if (!$contract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            DestroyContract::dispatch($contract);
            $contract->delete();

            return redirect()->back()->with('success', __('The contract has been deleted.'));
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function show($id)
    {
        if (Auth::user()->can('view-contracts')) {
            $contract = Contract::where('source_type', 'contract')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-contracts')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contracts')) {
                        $q->where('user_id', Auth::id())->orWhere('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->find($id);

            if (!$contract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            $contract->load([
                'user',
                'contractType',
                'attachments' => function ($query) {
                    if (Auth::user()->can('manage-any-contract-attachments')) {
                        $query->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contract-attachments')) {
                        $query->where('uploaded_by', Auth::id());
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                },
                'attachments.uploader',
                'comments' => function ($query) {
                    if (Auth::user()->can('manage-any-contract-comments')) {
                        $query->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contract-comments')) {
                        $query->where('user_id', Auth::id());
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                },
                'notes' => function ($query) {
                    if (Auth::user()->can('manage-any-contract-notes')) {
                        $query->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contract-notes')) {
                        $query->where('user_id', Auth::id());
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                },
                'renewals' => function ($query) {
                    if (Auth::user()->can('manage-any-contract-renewals')) {
                        $query->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contract-renewals')) {
                        $query->where('creator_id', Auth::id());
                    } else {
                        $query->whereRaw('1 = 0');
                    }

                    // Add search functionality
                    if (request('renewal_search')) {
                        $searchTerm = request('renewal_search');
                        $numericSearch = preg_replace('/[^0-9.]/', '', $searchTerm);

                        $query->where(function ($q) use ($searchTerm, $numericSearch) {
                            $q->where('notes', 'like', '%' . $searchTerm . '%')
                                ->orWhere('status', 'like', '%' . $searchTerm . '%')
                                ->orWhere('start_date', 'like', '%' . $searchTerm . '%')
                                ->orWhere('end_date', 'like', '%' . $searchTerm . '%')
                                ->orWhereHas('creator', function ($userQuery) use ($searchTerm) {
                                    $userQuery->where('name', 'like', '%' . $searchTerm . '%');
                                });

                            // Search for numeric value (handles formatted currency)
                            if ($numericSearch) {
                                $q->orWhere('value', 'like', '%' . $numericSearch . '%');
                            }
                        });
                    }

                    // Add pagination
                    $perPage = request('renewal_per_page', 10);
                    $page = request('renewal_page', 1);
                    $query->skip(($page - 1) * $perPage)->take($perPage);

                    $query->with('creator')->latest();
                },
                'signatures.user'
            ]);

            return Inertia::render('Contract/Contracts/Show', [
                'contract' => $contract,
            ]);
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function preview($id)
    {
        if (Auth::user()->can('preview-contracts')) {
            $contract = Contract::where('source_type', 'contract')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-contracts')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contracts')) {
                        $q->where('user_id', Auth::id())->orWhere('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->with(['user', 'contractType', 'signatures.user'])->find($id);

            if (!$contract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            return Inertia::render('Contract/Contracts/Preview', [
                'contract' => $contract,
            ]);
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }

    public function duplicate(DuplicateContractRequest $request, $id)
    {
        if (Auth::user()->can('duplicate-contracts')) {
            $originalContract = Contract::where('source_type', 'contract')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-contracts')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-contracts')) {
                        $q->where('user_id', Auth::id())->orWhere('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->find($id);

            if (!$originalContract) {
                return redirect()->route('contract.index')->with('error', __('Contract not found.'));
            }

            $validated = $request->validated();

            $duplicateContract = new Contract();
            $duplicateContract->fill($validated);
            $duplicateContract->source_type = 'contract';
            $duplicateContract->creator_id = Auth::id();
            $duplicateContract->created_by = creatorId();
            $duplicateContract->save();

            return redirect()->route('contract.index')->with('success', __('The contract has been duplicated successfully.'));
        } else {
            return redirect()->route('contract.index')->with('error', __('Permission denied'));
        }
    }
}
