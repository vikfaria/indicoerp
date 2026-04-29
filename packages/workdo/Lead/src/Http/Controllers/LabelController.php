<?php

namespace Workdo\Lead\Http\Controllers;

use Workdo\Lead\Models\Label;
use Workdo\Lead\Http\Requests\StoreLabelRequest;
use Workdo\Lead\Http\Requests\UpdateLabelRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Events\CreateLabel;
use Workdo\Lead\Events\UpdateLabel;
use Workdo\Lead\Events\DestroyLabel;

class LabelController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-labels')){
            $labels = Label::select('id', 'name', 'color', 'pipeline_id', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-labels')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-labels')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Lead/SystemSetup/Labels/Index', [
                'labels' => $labels,
                'pipelines' => Pipeline::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLabelRequest $request)
    {
        if(Auth::user()->can('create-labels')){
            $validated = $request->validated();
            $label              = new Label();
            $label->name        = $validated['name'];
            $label->color       = $validated['color'];
            $label->pipeline_id = $validated['pipeline_id'];
            $label->creator_id  = Auth::id();
            $label->created_by  = creatorId();
            $label->save();

            CreateLabel::dispatch($request, $label);

            return redirect()->route('lead.labels.index')->with('success', __('The label has been created successfully.'));
        }
        else{
            return redirect()->route('lead.labels.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateLabelRequest $request, Label $label)
    {
        if(Auth::user()->can('edit-labels')){
            $validated = $request->validated();

            $label->name        = $validated['name'];
            $label->color       = $validated['color'];
            $label->pipeline_id = $validated['pipeline_id'];

            $label->save();

            UpdateLabel::dispatch($request, $label);

            return back()->with('success', __('The label details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Label $label)
    {
        if(Auth::user()->can('delete-labels')){
            DestroyLabel::dispatch($label);
            $label->delete();

            return back()->with('success', __('The label has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }


}