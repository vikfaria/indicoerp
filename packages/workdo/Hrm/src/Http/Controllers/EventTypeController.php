<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Http\Requests\StoreEventTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateEventTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateEventType;
use Workdo\Hrm\Events\DestroyEventType;
use Workdo\Hrm\Events\UpdateEventType;


class EventTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-event-types')){
            $eventtypes = EventType::select('id', 'event_type', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-event-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-event-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/EventTypes/Index', [
                'eventtypes' => $eventtypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreEventTypeRequest $request)
    {
        if(Auth::user()->can('create-event-types')){
            $validated = $request->validated();



            $eventtype = new EventType();
            $eventtype->event_type = $validated['event_type'];

            $eventtype->creator_id = Auth::id();
            $eventtype->created_by = creatorId();
            $eventtype->save();

            CreateEventType::dispatch($request, $eventtype);

            return redirect()->route('hrm.event-types.index')->with('success', __('The eventtype has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.event-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventtype)
    {
        if(Auth::user()->can('edit-event-types')){
            $validated = $request->validated();



            $eventtype->event_type = $validated['event_type'];

            $eventtype->save();

            UpdateEventType::dispatch($request, $eventtype);

            return redirect()->route('hrm.event-types.index')->with('success', __('The eventtype details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.event-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(EventType $eventtype)
    {
        if(Auth::user()->can('delete-event-types')){
            DestroyEventType::dispatch($eventtype);
            $eventtype->delete();

            return redirect()->route('hrm.event-types.index')->with('success', __('The eventtype has been deleted.'));
        }
        else{
            return redirect()->route('hrm.event-types.index')->with('error', __('Permission denied'));
        }
    }


}