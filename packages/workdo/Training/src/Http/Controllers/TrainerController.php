<?php

namespace Workdo\Training\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Training\Models\Trainer;
use Workdo\Training\Http\Requests\StoreTrainerRequest;
use Workdo\Training\Http\Requests\UpdateTrainerRequest;
use Workdo\Training\Events\CreateTrainer;
use Workdo\Training\Events\UpdateTrainer;
use Workdo\Training\Events\DestroyTrainer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;

class TrainerController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-trainers')){
            $trainers = Trainer::query()
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-trainers')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-trainers')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->with(['branch', 'department'])
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')))
                ->when(request('department_id'), fn($q) => $q->where('department_id', request('department_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $branches = Branch::where('created_by', creatorId())->get();
            $departments = Department::where('created_by', creatorId())->get();

            return Inertia::render('Training/trainers/index', [
                'trainers' => $trainers,
                'branches' => $branches,
                'departments' => $departments,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTrainerRequest $request)
    {
        if(Auth::user()->can('create-trainers')){
            $validated = $request->validated();

            $trainer                = new Trainer();
            $trainer->name          = $validated['name'];
            $trainer->contact       = $validated['contact'];
            $trainer->email         = $validated['email'];
            $trainer->experience    = $validated['experience'];
            $trainer->branch_id     = $validated['branch_id'];
            $trainer->department_id = $validated['department_id'];
            $trainer->expertise     = $validated['expertise'] ?? null;
            $trainer->qualification = $validated['qualification'] ?? null;
            $trainer->creator_id    = Auth::id();
            $trainer->created_by    = creatorId();
            $trainer->save();

            CreateTrainer::dispatch($request, $trainer);

            return redirect()->route('training.trainers.index')->with('success', __('The trainer has been created successfully.'));
        }
        else{
            return redirect()->route('training.trainers.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer)
    {
        if(Auth::user()->can('edit-trainers')){
            $validated = $request->validated();

            $trainer->name          = $validated['name'];
            $trainer->contact       = $validated['contact'];
            $trainer->email         = $validated['email'];
            $trainer->experience    = $validated['experience'];
            $trainer->branch_id     = $validated['branch_id'];
            $trainer->department_id = $validated['department_id'];
            $trainer->expertise     = $validated['expertise'] ?? null;
            $trainer->qualification = $validated['qualification'] ?? null;
            $trainer->save();

            UpdateTrainer::dispatch($request, $trainer);

            return back()->with('success', __('The trainer details are updated successfully.'));
        }
        else{
            return redirect()->route('training.trainers.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Trainer $trainer)
    {
        if(Auth::user()->can('delete-trainers')){
            DestroyTrainer::dispatch($trainer);

            $trainer->delete();

            return back()->with('success', __('The trainer has been deleted.'));
        }
        else{
            return redirect()->route('training.trainers.index')->with('error', __('Permission denied'));
        }
    }
}