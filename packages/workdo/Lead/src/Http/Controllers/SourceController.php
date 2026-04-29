<?php

namespace Workdo\Lead\Http\Controllers;

use Workdo\Lead\Models\Source;
use Workdo\Lead\Http\Requests\StoreSourceRequest;
use Workdo\Lead\Http\Requests\UpdateSourceRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Lead\Events\CreateSource;
use Workdo\Lead\Events\UpdateSource;
use Workdo\Lead\Events\DestroySource;


class SourceController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-sources')){
            $sources = Source::select('id', 'name', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-sources')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-sources')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Lead/SystemSetup/Sources/Index', [
                'sources' => $sources,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreSourceRequest $request)
    {
        if(Auth::user()->can('create-sources')){
            $validated = $request->validated();

            $source             = new Source();
            $source->name       = $validated['name'];
            $source->creator_id = Auth::id();
            $source->created_by = creatorId();
            $source->save();

            CreateSource::dispatch($request, $source);

            return redirect()->route('lead.sources.index')->with('success', __('The source has been created successfully.'));
        }
        else{
            return redirect()->route('lead.sources.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateSourceRequest $request, Source $source)
    {
        try {
            if(Auth::user()->can('edit-sources')){
            $validated = $request->validated();

            $source->name = $validated['name'];

            $source->save();

            UpdateSource::dispatch($request, $source);

            return back()->with('success', __('The source details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
        } catch (\Exception $e) {
            return back()->with('error', __('Source not found'));
        }
    }

    public function destroy(Source $source)
    {
        try {
            if(Auth::user()->can('delete-sources')){
                DestroySource::dispatch($source);
                $source->delete();

            return back()->with('success', __('The source has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
        } catch (\Exception $e) {
            return back()->with('error', __('Source not found'));
        }
    }


}