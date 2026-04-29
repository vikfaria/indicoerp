<?php

namespace Workdo\ZoomMeeting\Http\Controllers;

use Workdo\ZoomMeeting\Models\ZoomMeeting;
use Workdo\ZoomMeeting\Http\Requests\StoreZoomMeetingRequest;
use Workdo\ZoomMeeting\Http\Requests\UpdateZoomMeetingRequest;
use Workdo\ZoomMeeting\Services\ZoomService;
use Workdo\ZoomMeeting\Events\CreateZoomMeeting;
use Workdo\ZoomMeeting\Events\UpdateZoomMeeting;
use Workdo\ZoomMeeting\Events\DestroyZoomMeeting;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

class ZoomMeetingController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-zoom-meetings')){
            $zoommeetings = ZoomMeeting::query()
                ->with(['host'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-zoom-meetings')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-zoom-meetings')) {
                        $q->where(function($query) {
                            $query->where('creator_id', Auth::id())
                                  ->orWhere('host_id', Auth::id())
                                  ->orWhereJsonContains('participants', (string)Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function($q) {
                    $q->where(function($query) {
                    $query->where('title', 'like', '%' . request('title') . '%');
                    $query->orWhere('meeting_id', 'like', '%' . request('title') . '%');
                    });
                })
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('date_range'), function($q) {
                    $dateRange = request('date_range');
                    if (strpos($dateRange, ' - ') !== false) {
                        [$startDate, $endDate] = explode(' - ', $dateRange);
                        $q->whereDate('start_time', '>=', trim($startDate))
                          ->whereDate('start_time', '<=', trim($endDate));
                    } else {
                        $q->whereDate('start_time', $dateRange);
                    }
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();



            return Inertia::render('ZoomMeeting/ZoomMeetings/Index', [
                'zoommeetings' => $zoommeetings,
                'users' => User::where('created_by', creatorId())->select('id', 'name', 'avatar')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreZoomMeetingRequest $request)
    {
        if(Auth::user()->can('create-zoom-meetings')){
            if (company_setting('zoom_enabled') !== 'on') {
                return redirect()->back()->with('error', __('Zoom meeting integration is disabled'));
            }

            $validated = $request->validated();

            $validated['host_video'] = $request->boolean('host_video', false);
            $validated['participant_video'] = $request->boolean('participant_video', false);
            $validated['waiting_room'] = $request->boolean('waiting_room', false);
            $validated['recording'] = $request->boolean('recording', false);

            try {
                // Create meeting via Zoom API
                $zoomService = new ZoomService();
                $zoomResponse = $zoomService->createMeeting($validated);

                $zoommeeting = new ZoomMeeting();
                $zoommeeting->title = $validated['title'];
                $zoommeeting->description = $validated['description'];
                $zoommeeting->meeting_id = $zoomResponse['id'];
                $zoommeeting->meeting_password = $validated['meeting_password'];
                $zoommeeting->start_url = $zoomResponse['start_url'] ?? null;
                $zoommeeting->join_url = $zoomResponse['join_url'] ?? null;
                $zoommeeting->start_time = $validated['start_time'];
                $zoommeeting->duration = $validated['duration'];
                $zoommeeting->host_video = $validated['host_video'];
                $zoommeeting->participant_video = $validated['participant_video'];
                $zoommeeting->waiting_room = $validated['waiting_room'];
                $zoommeeting->recording = $validated['recording'];
                $zoommeeting->status = $validated['status'];
                $zoommeeting->participants = $validated['participants'];
                $zoommeeting->host_id = $validated['host_id'];
                $zoommeeting->creator_id = Auth::id();
                $zoommeeting->created_by = creatorId();
                $zoommeeting->save();

                // Dispatch event for packages to handle their fields
                CreateZoomMeeting::dispatch($request, $zoommeeting);

                return redirect()->route('zoommeeting.zoom-meetings.index')->with('success', __('The zoom meeting has been created successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else{
            return redirect()->route('zoommeeting.zoom-meetings.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateZoomMeetingRequest $request, ZoomMeeting $zoommeeting)
    {
        if(Auth::user()->can('edit-zoom-meetings') && $zoommeeting->status === 'Scheduled'){
            if (company_setting('zoom_enabled') !== 'on') {
                return redirect()->back()->with('error', __('Zoom integration is disabled'));
            }

            $validated = $request->validated();

            $validated['host_video'] = $request->boolean('host_video', false);
            $validated['participant_video'] = $request->boolean('participant_video', false);
            $validated['waiting_room'] = $request->boolean('waiting_room', false);
            $validated['recording'] = $request->boolean('recording', false);

            try {
                // Update meeting via Zoom API if meeting_id exists
                if ($zoommeeting->meeting_id) {
                    $zoomService = new ZoomService();
                    $zoomResponse = $zoomService->updateMeeting($zoommeeting->meeting_id, $validated);

                    // Update URLs if returned from API
                    if (isset($zoomResponse['start_url'])) {
                        $zoommeeting->start_url = $zoomResponse['start_url'];
                    }
                    if (isset($zoomResponse['join_url'])) {
                        $zoommeeting->join_url = $zoomResponse['join_url'];
                    }
                }

                $zoommeeting->title = $validated['title'];
                $zoommeeting->description = $validated['description'];
                $zoommeeting->meeting_password = $validated['meeting_password'];
                $zoommeeting->start_time = $validated['start_time'];
                $zoommeeting->duration = $validated['duration'];
                $zoommeeting->host_video = $validated['host_video'];
                $zoommeeting->participant_video = $validated['participant_video'];
                $zoommeeting->waiting_room = $validated['waiting_room'];
                $zoommeeting->recording = $validated['recording'];
                $zoommeeting->participants = $validated['participants'];
                $zoommeeting->host_id = $validated['host_id'];
                $zoommeeting->save();

                // Dispatch event for packages to handle their fields
                UpdateZoomMeeting::dispatch($request, $zoommeeting);

                return redirect()->back()->with('success', __('The zoom meeting details are updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else{
            return redirect()->route('zoommeeting.zoom-meetings.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(ZoomMeeting $zoommeeting)
    {
        if(Auth::user()->can('delete-zoom-meetings')){
            try {

                // Delete meeting via Zoom API if meeting_id exists and integration is enabled
                if ($zoommeeting->meeting_id && company_setting('zoom_enabled') === 'on') {
                    $zoomService = new ZoomService();
                    $zoomService->deleteMeeting($zoommeeting->meeting_id);
                }


                // Dispatch event for packages to handle their fields
                DestroyZoomMeeting::dispatch($zoommeeting);

                $zoommeeting->delete();
                return redirect()->back()->with('success', __('The zoom meeting has been deleted.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else{
            return redirect()->route('zoommeeting.zoom-meetings.index')->with('error', __('Permission denied'));
        }
    }



    public function updateStatus(ZoomMeeting $zoommeeting)
    {
        if(Auth::user()->can('update-zoom-meeting-status')){
            $status = request('status');

            if (!in_array($status, ['Scheduled', 'Started', 'Ended', 'Cancelled'])) {
                return redirect()->back()->with('error', __('Invalid status'));
            }

            $zoommeeting->status = $status;
            $zoommeeting->save();

            return redirect()->back()->with('success', __('Status updated successfully'));
        }
        else{
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }


}