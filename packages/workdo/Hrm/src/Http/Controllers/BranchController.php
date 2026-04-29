<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Http\Requests\StoreBranchRequest;
use Workdo\Hrm\Http\Requests\UpdateBranchRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateBranch;
use Workdo\Hrm\Events\DestroyBranch;
use Workdo\Hrm\Events\UpdateBranch;

class BranchController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-branches')) {
            $branches = Branch::select('id', 'branch_name', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-branches')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-branches')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/Branches/Index', [
                'branches' => $branches,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBranchRequest $request)
    {
        if (Auth::user()->can('create-branches')) {
            $validated = $request->validated();



            $branch = new Branch();
            $branch->branch_name = $validated['branch_name'];

            $branch->creator_id = Auth::id();
            $branch->created_by = creatorId();
            $branch->save();

            CreateBranch::dispatch($request, $branch);

            return redirect()->route('hrm.branches.index')->with('success', __('The branch has been created successfully.'));
        } else {
            return redirect()->route('hrm.branches.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        if (Auth::user()->can('edit-branches')) {
            $validated = $request->validated();
            $branch->branch_name = $validated['branch_name'];
            $branch->save();

            UpdateBranch::dispatch($request, $branch);

            return redirect()->route('hrm.branches.index')->with('success', __('The branch details are updated successfully.'));
        } else {
            return redirect()->route('hrm.branches.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Branch $branch)
    {
        if (Auth::user()->can('delete-branches')) {
            DestroyBranch::dispatch($branch);
            $branch->delete();

            return redirect()->route('hrm.branches.index')->with('success', __('The branch has been deleted.'));
        } else {
            return redirect()->route('hrm.branches.index')->with('error', __('Permission denied'));
        }
    }
}
