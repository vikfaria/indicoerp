<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Event;
use Workdo\Hrm\Http\Requests\StoreEventRequest;
use Workdo\Hrm\Http\Requests\UpdateEventRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\Department;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Events\CreateEvent;
use Workdo\Hrm\Events\DestroyEvent;
use Workdo\Hrm\Events\UpdateEvent;

class EventController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-events')) {
            $events = Event::query()
                ->with(['eventType', 'approvedBy', 'departments'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-events')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-events')) {
                        // Get current user's employee record to find their department
                        $employee = Employee::where('user_id', Auth::id())->first();
                        if ($employee && $employee->department_id) {
                            $q->whereHas('departments', function ($query) use ($employee) {
                                $query->where('department_id', $employee->department_id)->where('status', 'approved');
                            });
                        } else {
                            // If user has no department, show no announcements
                            $q->whereRaw('1 = 0');
                        }
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('title', 'like', '%' . request('title') . '%')
                            ->orWhereHas('eventType', function ($eventTypeQuery) {
                                $eventTypeQuery->where('event_type', 'like', '%' . request('title') . '%');
                            });
                    });
                })
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('event_type_id') !== null && request('event_type_id') !== '', fn($q) => $q->where('event_type_id', request('event_type_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Events/Index', [
                'events' => $events,
                'eventtypes' => EventType::where('created_by', creatorId())->select('id', 'event_type')->get(),
                'users' => User::where('created_by', creatorId())->select('id', 'name')->get(),
                'departments' => Department::where('created_by', creatorId())->with('branch')->select('id', 'department_name', 'branch_id')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreEventRequest $request)
    {
        if (Auth::user()->can('create-events')) {
            $validated = $request->validated();



            $event = new Event();
            $event->title = $validated['title'];
            $event->event_type_id = $validated['event_type_id'];
            $event->start_date = $validated['start_date'];
            $event->end_date = $validated['end_date'];
            $event->start_time = $validated['start_time'];
            $event->end_time = $validated['end_time'];
            $event->location = $validated['location'];
            $event->description = $validated['description'];
            $event->color = $validated['color'] ?? null;
            $event->status = 'pending';
            $event->creator_id = Auth::id();
            $event->created_by = creatorId();
            $event->save();

            CreateEvent::dispatch($request, $event);

            // Attach departments with creator info
            if (isset($validated['departments']) && is_array($validated['departments'])) {
                $departmentData = [];
                foreach ($validated['departments'] as $departmentId) {
                    $departmentData[$departmentId] = [
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId()
                    ];
                }
                $event->departments()->attach($departmentData);
            }

            return redirect()->route('hrm.events.index')->with('success', __('The event has been created successfully.'));
        } else {
            return redirect()->route('hrm.events.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        if (Auth::user()->can('edit-events')) {
            $validated = $request->validated();



            $event->title = $validated['title'];
            $event->event_type_id = $validated['event_type_id'];
            $event->start_date = $validated['start_date'];
            $event->end_date = $validated['end_date'];
            $event->start_time = $validated['start_time'];
            $event->end_time = $validated['end_time'];
            $event->location = $validated['location'];
            $event->description = $validated['description'];
            $event->color = $validated['color'] ?? null;
            $event->save();

            UpdateEvent::dispatch($request, $event);

            // Sync departments with creator info
            if (isset($validated['departments']) && is_array($validated['departments'])) {
                $departmentData = [];
                foreach ($validated['departments'] as $departmentId) {
                    $departmentData[$departmentId] = [
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId()
                    ];
                }
                $event->departments()->sync($departmentData);
            }

            return redirect()->back()->with('success', __('The event details are updated successfully.'));
        } else {
            return redirect()->route('hrm.events.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Event $event)
    {
        if (Auth::user()->can('delete-events')) {
            DestroyEvent::dispatch($event);
            $event->delete();

            return redirect()->back()->with('success', __('The event has been deleted.'));
        } else {
            return redirect()->route('hrm.events.index')->with('error', __('Permission denied'));
        }
    }

    public function statusUpdate(Request $request, Event $event)
    {
        if (Auth::user()->can('manage-event-status')) {
            $request->validate([
                'status' => 'required|in:pending,approved,reject'
            ]);

            $event->status = $request->status;
            if ($request->status === 'approved') {
                $event->approved_by = Auth::id();
            }
            $event->save();

            return redirect()->back()->with('success', __('Event status updated successfully.'));
        } else {
            return redirect()->route('hrm.events.index')->with('error', __('Permission denied'));
        }
    }

    public function calendar()
    {
        if (Auth::user()->can('view-event-calendar')) {
            $query = Event::query()->with(['eventType', 'departments']);
            
            if (Auth::user()->can('manage-any-events')) {
                // Admin can see all approved events
                $query->where('created_by', creatorId())->where('status', 'approved');
            } else {
                // Regular users see approved events for their department only
                $employee = Employee::where('user_id', Auth::id())->first();
                if ($employee && $employee->department_id) {
                    $query->where('created_by', creatorId())
                          ->where('status', 'approved')
                          ->whereHas('departments', function ($q) use ($employee) {
                              $q->where('department_id', $employee->department_id);
                          });
                } else {
                    // If user has no department, show no events
                    $query->whereRaw('1 = 0');
                }
            }
            
            $events = $query->orderBy('id', 'desc')
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'startDate' => $event->start_date,
                        'endDate' => $event->end_date,
                        'time' => $event->start_time,
                        'description' => $event->description ?? '',
                        'type' => $event->eventType->event_type ?? 'event',
                        'color' => $event->color ?? '#3b82f6'
                    ];
                });

            return Inertia::render('Hrm/Events/Calendar', [
                'events' => $events
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
