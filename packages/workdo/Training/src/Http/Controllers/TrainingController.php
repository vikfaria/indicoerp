<?php

namespace Workdo\Training\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Training\Models\Training;
use Workdo\Training\Models\TrainingType;
use Workdo\Training\Models\Trainer;
use Workdo\Training\Http\Requests\StoreTrainingRequest;
use Workdo\Training\Http\Requests\UpdateTrainingRequest;
use Workdo\Training\Events\CreateTraining;
use Workdo\Training\Events\UpdateTraining;
use Workdo\Training\Events\DestroyTraining;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use App\Models\User;

class TrainingController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-trainings')){
            $trainings = Training::query()
            ->where(function($q) {
                if(Auth::user()->can('manage-any-trainings')) {
                    $q->where('created_by', creatorId());
                } elseif(Auth::user()->can('manage-own-trainings')) {
                        $user = Auth::user();
                        $q->where('creator_id', $user->id);
                        $q->orWhereHas('tasks', function($taskQuery) use ($user) {
                            $taskQuery->where('assigned_to', $user->id);
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->where('created_by', creatorId())
                ->with(['trainingType', 'trainer', 'branch', 'department'])
                ->when(request('title'), fn($q) => $q->where('title', 'like', '%' . request('title') . '%'))
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')))
                ->when(request('department_id'), fn($q) => $q->where('department_id', request('department_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $trainingTypes = TrainingType::where('created_by', creatorId())->get();
            $trainers = Trainer::where('created_by', creatorId())->get();
            $branches = Branch::where('created_by', creatorId())->get();
            $departments = Department::where('created_by', creatorId())->get();
            $users = User::emp()->where('created_by', creatorId())->select('id', 'name')->get();                

            return Inertia::render('Training/trainings/index', [
                'trainings' => $trainings,
                'trainingTypes' => $trainingTypes,
                'trainers' => $trainers,
                'branches' => $branches,
                'departments' => $departments,
                'users' => $users,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTrainingRequest $request)
    {
        if(Auth::user()->can('create-trainings')){
            $validated = $request->validated();
            
            $training                   = new Training();
            $training->title            = $validated['title'];
            $training->description      = $validated['description'];
            $training->training_type_id = $validated['training_type_id'];
            $training->trainer_id       = $validated['trainer_id'];
            $training->branch_id        = $validated['branch_id'];
            $training->department_id    = $validated['department_id'];
            $training->start_date       = $validated['start_date'];
            $training->end_date         = $validated['end_date'];
            $training->start_time       = $validated['start_time'];
            $training->end_time         = $validated['end_time'];
            $training->location         = $validated['location'];
            $training->max_participants = $validated['max_participants'];
            $training->cost             = $validated['cost'];
            $training->status           = $validated['status'];
            $training->creator_id       = Auth::id();
            $training->created_by       = creatorId();
            $training->save();

            CreateTraining::dispatch($request, $training);

            return redirect()->route('training.trainings.index')->with('success', __('The training list has been created successfully.'));
        }
        else{
            return redirect()->route('training.trainings.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTrainingRequest $request, Training $training)
    {
        if(Auth::user()->can('edit-trainings')){
            $validated = $request->validated();

            $training->title            = $validated['title'];
            $training->description      = $validated['description'];
            $training->training_type_id = $validated['training_type_id'];
            $training->trainer_id       = $validated['trainer_id'];
            $training->branch_id        = $validated['branch_id'];
            $training->department_id    = $validated['department_id'];
            $training->start_date       = $validated['start_date'];
            $training->end_date         = $validated['end_date'];
            $training->start_time       = $validated['start_time'];
            $training->end_time         = $validated['end_time'];
            $training->location         = $validated['location'];
            $training->max_participants = $validated['max_participants'];
            $training->cost             = $validated['cost'];
            $training->status           = $validated['status'];
            $training->save();

            UpdateTraining::dispatch($request, $training);

            return back()->with('success', __('The training list details are updated successfully.'));
        }
        else{
            return redirect()->route('training.trainings.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Training $training)
    {
        if(Auth::user()->can('delete-trainings')){
            DestroyTraining::dispatch($training);

            $training->delete();

            return back()->with('success', __('The training list has been deleted.'));
        }
        else{
            return redirect()->route('training.trainings.index')->with('error', __('Permission denied'));
        }
    }
}