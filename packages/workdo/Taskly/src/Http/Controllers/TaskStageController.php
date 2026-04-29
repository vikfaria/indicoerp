<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Taskly\Events\CreateTaskStage;
use Workdo\Taskly\Events\DestroyTaskStage;
use Workdo\Taskly\Events\UpdateTaskStage;
use Workdo\Taskly\Models\TaskStage;

class TaskStageController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-task-stages')){
            $taskStages = TaskStage::select('id', 'name', 'color', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-task-stages')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-task-stages')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->orderBy('order')
                ->get();

            return Inertia::render('Taskly/SystemSetup/TaskStages/Index', [
                'taskStages' => $taskStages,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(Request $request)
    {
        if(Auth::user()->can('create-task-stages')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            // Increment order of existing stages
            TaskStage::where('created_by', creatorId())->increment('order');
            
            // Set all existing stages as incomplete
            TaskStage::where('created_by', creatorId())->update(['complete' => false]);
            
            $taskStage = new TaskStage();
            $taskStage->name = $validated['name'];
            $taskStage->color = $validated['color'];
            $taskStage->complete = false;
            $taskStage->order = 0; // Always first
            $taskStage->creator_id = Auth::id();
            $taskStage->created_by = creatorId();
            $taskStage->save();
            
            // Set the last stage (highest order) as complete
            TaskStage::where('created_by', creatorId())
                ->orderBy('order', 'desc')
                ->first()
                ?->update(['complete' => true]);

            // Dispatch event for packages to handle their fields
            CreateTaskStage::dispatch($request, $taskStage);

            return redirect()->route('project.task-stages.index')->with('success', __('The task stage has been created successfully.'));
        } else {
            return redirect()->route('project.task-stages.index')->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request, TaskStage $taskStage)
    {
        if(Auth::user()->can('edit-task-stages')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            $taskStage->name = $validated['name'];
            $taskStage->color = $validated['color'];
            $taskStage->save();

             // Dispatch event for packages to handle their fields
            UpdateTaskStage::dispatch($request, $taskStage);

            return back()->with('success', __('The task stage details are updated successfully.'));
        }
        return redirect()->back()->with('error', __('Permission denied'));
    }

    public function destroy(TaskStage $taskStage)
    {
        if(Auth::user()->can('delete-task-stages')){

            DestroyTaskStage::dispatch($taskStage);

            $taskStage->delete();

            return back()->with('success', __('The task stage has been deleted.'));
        }
        return redirect()->back()->with('error', __('Permission denied'));
    }

    public function reorder(Request $request)
    {
        if(Auth::user()->can('edit-task-stages')){
            $validated = $request->validate([
                'stages' => 'required|array',
                'stages.*.id' => 'required|exists:task_stages,id',
                'stages.*.order' => 'required|integer',
                'stages.*.complete' => 'required|boolean',
            ]);

            foreach ($validated['stages'] as $stageData) {
                TaskStage::where('id', $stageData['id'])
                    ->where('created_by', creatorId())
                    ->update(['order' => $stageData['order']]);
            }
            
            // Set all stages as incomplete first
            TaskStage::where('created_by', creatorId())->update(['complete' => false]);
            
            // Set the last stage (highest order) as complete
            TaskStage::where('created_by', creatorId())
                ->orderBy('order', 'desc')
                ->first()
                ?->update(['complete' => true]);

            return back()->with('success', __('Task stages reordered successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }
}
