<?php

namespace Workdo\Lead\Http\Controllers;

use App\Models\User;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Http\Requests\StorePipelineRequest;
use Workdo\Lead\Http\Requests\UpdatePipelineRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Lead\Events\CreatePipeline;
use Workdo\Lead\Events\UpdatePipeline;
use Workdo\Lead\Events\DestroyPipeline;


class PipelineController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-pipelines')){
            $pipelines = Pipeline::select('id', 'name', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-pipelines')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-pipelines')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Lead/SystemSetup/Pipelines/Index', [
                'pipelines' => $pipelines,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePipelineRequest $request)
    {
        if(Auth::user()->can('create-pipelines')){
            $validated = $request->validated();

            $pipeline             = new Pipeline();
            $pipeline->name       = $validated['name'];
            $pipeline->creator_id = Auth::id();
            $pipeline->created_by = creatorId();
            $pipeline->save();

            CreatePipeline::dispatch($request, $pipeline);

            return redirect()->route('lead.pipelines.index')->with('success', __('The pipeline has been created successfully.'));
        }
        else{
            return redirect()->route('lead.pipelines.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePipelineRequest $request, Pipeline $pipeline)
    {
        try {
            if(Auth::user()->can('edit-pipelines')){
            $validated = $request->validated();
            $pipeline->name = $validated['name'];

            $pipeline->save();

            UpdatePipeline::dispatch($request, $pipeline);

            return back()->with('success', __('The pipeline details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
        } catch (\Exception $e) {
            return back()->with('error', __('Pipeline not found'));
        }
    }

    public function destroy(Pipeline $pipeline)
    {
        try {
            if(Auth::user()->can('delete-pipelines')){
                User::where('default_pipeline', $pipeline->id)->update(['default_pipeline' => null]);
                DestroyPipeline::dispatch($pipeline);
                $pipeline->delete();

            return back()->with('success', __('The pipeline has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
        } catch (\Exception $e) {
            return back()->with('error', __('Pipeline not found'));
        }
    }


}