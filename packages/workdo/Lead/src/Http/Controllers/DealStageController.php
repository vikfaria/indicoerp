<?php

namespace Workdo\Lead\Http\Controllers;

use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Http\Requests\StoreDealStageRequest;
use Workdo\Lead\Http\Requests\UpdateDealStageRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Events\CreateDealStage;
use Workdo\Lead\Events\UpdateDealStage;
use Workdo\Lead\Events\DestroyDealStage;

class DealStageController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-deal-stages')){
            $dealstages = DealStage::select('id', 'name', 'order', 'pipeline_id', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-deal-stages')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-deal-stages')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Lead/SystemSetup/DealStages/Index', [
                'dealstages' => $dealstages,
                'pipelines' => Pipeline::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreDealStageRequest $request)
    {
        if(Auth::user()->can('create-deal-stages')){
            $validated = $request->validated();

            $maxOrder = DealStage::where('pipeline_id', $validated['pipeline_id'])
                ->where('created_by', creatorId())
                ->max('order') ?? 0;

            $dealstage              = new DealStage();
            $dealstage->name        = $validated['name'];
            $dealstage->pipeline_id = $validated['pipeline_id'];
            $dealstage->order       = $maxOrder + 1;
            $dealstage->creator_id  = Auth::id();
            $dealstage->created_by  = creatorId();
            $dealstage->save();

            CreateDealStage::dispatch($request, $dealstage);

            return redirect()->route('lead.deal-stages.index')->with('success', __('The deal stage has been created successfully.'));
        }
        else{
            return redirect()->route('lead.deal-stages.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDealStageRequest $request, DealStage $dealstage)
    {
        if(Auth::user()->can('edit-deal-stages')){
            $validated = $request->validated();

            $oldPipelineId = $dealstage->pipeline_id;
            $newPipelineId = $validated['pipeline_id'];
            $oldOrder = $dealstage->order;

            // If pipeline is changing, handle order properly
            if ($oldPipelineId != $newPipelineId) {
                // Get max order in new pipeline and set new order
                $maxOrder = DealStage::where('pipeline_id', $newPipelineId)
                    ->where('created_by', creatorId())
                    ->max('order') ?? 0;
                
                $dealstage->order = $maxOrder + 1;
                
                // Reorder stages in old pipeline
                DealStage::where('pipeline_id', $oldPipelineId)
                    ->where('created_by', creatorId())
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');
            }

            $dealstage->name = $validated['name'];
            $dealstage->pipeline_id = $newPipelineId;
            $dealstage->save();

            UpdateDealStage::dispatch($request, $dealstage);

            return back()->with('success', __('The deal stage details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(DealStage $dealstage)
    {
        if(Auth::user()->can('delete-deal-stages')){

            DestroyDealStage::dispatch($dealstage);
            $dealstage->delete();

            return back()->with('success', __('The deal stage has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateOrder(Request $request)
    {
        if(Auth::user()->can('edit-deal-stages')){
            $request->validate([
                'stage_ids' => 'required|array',
                'pipeline_id' => 'required|integer'
            ]);

            foreach($request->stage_ids as $index => $stageId) {
                DealStage::where('id', $stageId)
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