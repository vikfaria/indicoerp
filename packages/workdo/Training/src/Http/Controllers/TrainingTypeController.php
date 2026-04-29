<?php

namespace Workdo\Training\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Training\Models\TrainingType;
use Workdo\Training\Http\Requests\StoreTrainingTypeRequest;
use Workdo\Training\Http\Requests\UpdateTrainingTypeRequest;
use Workdo\Training\Events\CreateTrainingType;
use Workdo\Training\Events\UpdateTrainingType;
use Workdo\Training\Events\DestroyTrainingType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;

class TrainingTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-training-types')){
            $trainingTypes = TrainingType::query()
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-training-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-training-types')) {
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

            return Inertia::render('Training/training-types/index', [
                'trainingTypes' => $trainingTypes,
                'branches' => $branches,
                'departments' => $departments,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTrainingTypeRequest $request)
    {
        if(Auth::user()->can('create-training-types')){
            $validated = $request->validated();

            $trainingType                = new TrainingType();
            $trainingType->name          = $validated['name'];
            $trainingType->description   = $validated['description'];
            $trainingType->branch_id     = $validated['branch_id'];
            $trainingType->department_id = $validated['department_id'] ?? [];
            $trainingType->creator_id    = Auth::id();
            $trainingType->created_by    = creatorId();
            $trainingType->save();

            CreateTrainingType::dispatch($request, $trainingType);

            return redirect()->route('training.training-types.index')->with('success', __('The training type has been created successfully.'));
        }
        else{
            return redirect()->route('training.training-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTrainingTypeRequest $request, TrainingType $trainingType)
    {
        if(Auth::user()->can('edit-training-types')){
            $validated = $request->validated();

            $trainingType->name          = $validated['name'];
            $trainingType->description   = $validated['description'];
            $trainingType->branch_id     = $validated['branch_id'];
            $trainingType->department_id = $validated['department_id'] ?? [];
            $trainingType->save();

            UpdateTrainingType::dispatch($request, $trainingType);

            return back()->with('success', __('The training type details are updated successfully.'));
        }
        else{
            return redirect()->route('training.training-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(TrainingType $trainingType)
    {
        if(Auth::user()->can('delete-training-types')){
            DestroyTrainingType::dispatch($trainingType);

            $trainingType->delete();

            return back()->with('success', __('The training type has been deleted.'));
        }
        else{
            return redirect()->route('training.training-types.index')->with('error', __('Permission denied'));
        }
    }
}