<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Taskly\Events\CreateBugStage;
use Workdo\Taskly\Events\DestroyBugStage;
use Workdo\Taskly\Events\UpdateBugStage;
use Workdo\Taskly\Models\BugStage;

class BugStageController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-bug-stages')){
            $bugStages = BugStage::select('id', 'name', 'color', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-bug-stages')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-bug-stages')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->orderBy('order')
                ->get();

            return Inertia::render('Taskly/SystemSetup/BugStages/Index', [
                'bugStages' => $bugStages,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(Request $request)
    {
        if(Auth::user()->can('create-bug-stages')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            // Increment order of existing stages
            BugStage::where('created_by', creatorId())->increment('order');
            
            // Set all existing stages as incomplete
            BugStage::where('created_by', creatorId())->update(['complete' => false]);
            
            $bugStage = new BugStage();
            $bugStage->name = $validated['name'];
            $bugStage->color = $validated['color'];
            $bugStage->complete = false;
            $bugStage->order = 0; // Always first
            $bugStage->creator_id = Auth::id();
            $bugStage->created_by = creatorId();
            $bugStage->save();
            
            // Set the last stage (highest order) as complete
            BugStage::where('created_by', creatorId())
                ->orderBy('order', 'desc')
                ->first()
                ?->update(['complete' => true]);

            // Dispatch event for packages to handle their fields
            CreateBugStage::dispatch($request, $bugStage);

            return redirect()->route('project.bug-stages.index')->with('success', __('The bug stage has been created successfully.'));
        } else {
            return redirect()->route('project.bug-stages.index')->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request, BugStage $bugStage)
    {
        if(Auth::user()->can('edit-bug-stages')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            $bugStage->name = $validated['name'];
            $bugStage->color = $validated['color'];
            $bugStage->save();

             // Dispatch event for packages to handle their fields
            UpdateBugStage::dispatch($request, $bugStage);

           return back()->with('success', __('The bug stage details are updated successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function destroy(BugStage $bugStage)
    {
        if(Auth::user()->can('delete-bug-stages')){

            DestroyBugStage::dispatch($bugStage);

            $bugStage->delete();

            return back()->with('success', __('The bug stage has been deleted.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function reorder(Request $request)
    {
        if(Auth::user()->can('edit-bug-stages')){
            $validated = $request->validate([
                'stages' => 'required|array',
                'stages.*.id' => 'required|exists:bug_stages,id',
                'stages.*.order' => 'required|integer',
                'stages.*.complete' => 'required|boolean',
            ]);

            foreach ($validated['stages'] as $stageData) {
                BugStage::where('id', $stageData['id'])
                    ->where('created_by', creatorId())
                    ->update(['order' => $stageData['order']]);
            }
            
            // Set all stages as incomplete first
            BugStage::where('created_by', creatorId())->update(['complete' => false]);
            
            // Set the last stage (highest order) as complete
            BugStage::where('created_by', creatorId())
                ->orderBy('order', 'desc')
                ->first()
                ?->update(['complete' => true]);

            return back()->with('success', __('Bug stages reordered successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }
}
