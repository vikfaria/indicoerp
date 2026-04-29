<?php

namespace Workdo\Lead\Http\Controllers;

use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Http\Requests\StoreLeadStageRequest;
use Workdo\Lead\Http\Requests\UpdateLeadStageRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Events\CreateLeadStage;
use Workdo\Lead\Events\UpdateLeadStage;
use Workdo\Lead\Events\DestroyLeadStage;

class LeadStageController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-lead-stages')){
            $leadstages = LeadStage::select('id', 'name', 'order', 'pipeline_id', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-lead-stages')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-lead-stages')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Lead/SystemSetup/LeadStages/Index', [
                'leadstages' => $leadstages,
                'pipelines' => Pipeline::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLeadStageRequest $request)
    {
        if(Auth::user()->can('create-lead-stages')){
            $validated = $request->validated();

            $maxOrder = LeadStage::where('pipeline_id', $validated['pipeline_id'])
                ->where('created_by', creatorId())
                ->max('order') ?? 0;

            $leadstage              = new LeadStage();
            $leadstage->name        = $validated['name'];
            $leadstage->pipeline_id = $validated['pipeline_id'];
            $leadstage->order       = $maxOrder + 1;
            $leadstage->creator_id  = Auth::id();
            $leadstage->created_by  = creatorId();
            $leadstage->save();

            CreateLeadStage::dispatch($request, $leadstage);

            return redirect()->route('lead.lead-stages.index')->with('success', __('The lead stage has been created successfully.'));
        }
        else{
            return redirect()->route('lead.lead-stages.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateLeadStageRequest $request, LeadStage $leadstage)
    {
        if(Auth::user()->can('edit-lead-stages')){
            $validated = $request->validated();

            $oldPipelineId = $leadstage->pipeline_id;
            $newPipelineId = $validated['pipeline_id'];
            $oldOrder = $leadstage->order;

            if ($oldPipelineId != $newPipelineId) {
                $maxOrder = LeadStage::where('pipeline_id', $newPipelineId)
                    ->where('created_by', creatorId())
                    ->max('order') ?? 0;
                
                $leadstage->order = $maxOrder + 1;
                
                LeadStage::where('pipeline_id', $oldPipelineId)
                    ->where('created_by', creatorId())
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');
            }

            $leadstage->name = $validated['name'];
            $leadstage->pipeline_id = $newPipelineId;
            $leadstage->save();

            UpdateLeadStage::dispatch($request, $leadstage);

            return back()->with('success', __('The lead stage details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(LeadStage $leadstage)
    {
        if(Auth::user()->can('delete-lead-stages')){

            DestroyLeadStage::dispatch($leadstage);
            $leadstage->delete();

            return back()->with('success', __('The lead stage has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateOrder(Request $request)
    {
        if(Auth::user()->can('edit-lead-stages')){
            $request->validate([
                'stage_ids' => 'required|array',
                'pipeline_id' => 'required|integer'
            ]);

            foreach($request->stage_ids as $index => $stageId) {
                LeadStage::where('id', $stageId)
                    ->where('pipeline_id', $request->pipeline_id)
                    ->where('created_by', creatorId())
                    ->update(['order' => $index + 1]);
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}