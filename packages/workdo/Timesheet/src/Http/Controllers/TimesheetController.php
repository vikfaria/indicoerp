<?php

namespace Workdo\Timesheet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Timesheet\Models\Timesheet;
use Workdo\Timesheet\Http\Requests\StoreTimesheetRequest;
use Workdo\Timesheet\Http\Requests\UpdateTimesheetRequest;
use Workdo\Timesheet\Events\CreateTimesheet;
use Workdo\Timesheet\Events\UpdateTimesheet;
use Workdo\Timesheet\Events\DestroyTimesheet;
use App\Models\User;

class TimesheetController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-timesheet')) {
            $timesheets = Timesheet::with(['user:id,name'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-timesheet')) {
                        $q->where('timesheets.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-timesheet')) {
                        $q->where('timesheets.user_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function ($q, $search) {
                    $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                })
                ->when(request('type'), fn($q, $type) => $q->where('type', $type))
                ->when(request('date'), fn($q, $date) => $q->whereDate('date', $date))
                ->when(request('user_id'), fn($q, $userId) => $q->where('timesheets.user_id', (int)$userId))
                ->when(request('sort'), function ($q) {
                    $sort = request('sort');
                    $direction = in_array(request('direction'), ['asc', 'desc']) ? request('direction') : 'asc';
                    $allowedSorts = ['id', 'date', 'hours', 'minutes', 'type', 'user_id', 'created_at'];

                    if (!in_array($sort, $allowedSorts)) {
                        return $q->latest();
                    }

                    if ($sort === 'user_id') {
                        return $q->join('users', 'timesheets.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction)
                            ->select('timesheets.*');
                    }

                    return $q->orderBy('timesheets.' . $sort, $direction);
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Add project and task names if modules are active
            if (module_is_active('Taskly')) {
                $timesheets->getCollection()->transform(function ($timesheet) {
                    try {
                        if ($timesheet->project_id && class_exists('\Workdo\Taskly\Models\Project')) {
                            $project = \Workdo\Taskly\Models\Project::find($timesheet->project_id);
                            $timesheet->project_name = $project ? $project->name : 'N/A';
                        }
                        if ($timesheet->task_id && class_exists('\Workdo\Taskly\Models\ProjectTask')) {
                            $task = \Workdo\Taskly\Models\ProjectTask::find($timesheet->task_id);
                            $timesheet->task_name = $task ? $task->title : 'N/A';
                        }
                    } catch (\Exception $e) {
                        $timesheet->project_name = 'N/A';
                        $timesheet->task_name = 'N/A';
                    }
                    return $timesheet;
                });
            }

            $users = [];
            if (Auth::user()->can('manage-any-timesheet')) {
                $users = User::where('created_by', creatorId())
                    ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                    ->select('id', 'name')
                    ->get();
            }

            $projects = [];
            if (module_is_active('Taskly') && class_exists('\Workdo\Taskly\Models\Project')) {
                try {
                    if (Auth::user()->can('manage-any-timesheet')) {
                        $projects = \Workdo\Taskly\Models\Project::where('created_by', creatorId())
                            ->select('id', 'name')
                            ->get();
                    } else {
                        $projects = \Workdo\Taskly\Models\Project::whereHas('users', fn($q) => $q->where('user_id', Auth::id()))
                            ->select('id', 'name')
                            ->get();
                    }
                } catch (\Exception $e) {
                    $projects = [];
                }
            }

            return Inertia::render('Timesheet/Index', [
                'timesheets' => $timesheets,
                'hasHRM' => module_is_active('Hrm'),
                'hasTaskly' => module_is_active('Taskly'),
                'users' => $users,
                'projects' => $projects,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function store(StoreTimesheetRequest $request)
    {
        if (Auth::user()->can('create-timesheet')) {
            try {
                $validated = $request->validated();

                // Validate remaining hours for clock_in_out type
                if ($validated['type'] === 'clock_in_out' && module_is_active('Hrm')) {
                    $remainingHours = $this->getRemainingHours($validated['user_id'] ?? Auth::id(), $validated['date']);
                    $requestedMinutes = ($validated['hours'] * 60) + $validated['minutes'];
                    $availableMinutes = ($remainingHours['hours'] * 60) + $remainingHours['minutes'];
                    
                    if ($requestedMinutes > $availableMinutes) {
                        return back()->with('error', __('Insufficient remaining hours. Available: :hours hours :minutes minutes', [
                            'hours' => $remainingHours['hours'],
                            'minutes' => $remainingHours['minutes']
                        ]));
                    }
                }

                // Ensure user_id is set
                if (empty($validated['user_id'])) {
                    $validated['user_id'] = Auth::id();
                }

                $timesheet = new Timesheet();
                $timesheet->fill($validated);
                $timesheet->creator_id = Auth::id();
                $timesheet->created_by = creatorId();
                $timesheet->save();

                CreateTimesheet::dispatch($request, $timesheet);

                return back()->with('success', __('The timesheet has been created successfully.'));
            } catch (\Exception $e) {
                \Log::error('Error creating timesheet: ' . $e->getMessage());
                return back()->with('error', __('Failed to create timesheet. Please try again.'));
            }
        }
        return redirect()->route('timesheet.index')->with('error', __('Permission denied'));
    }

    public function update(UpdateTimesheetRequest $request, Timesheet $timesheet)
    {
        if (Auth::user()->can('edit-timesheet') && $this->canAccessTimesheet($timesheet)) {
            try {
                $validated = $request->validated();
                
                // Validate remaining hours for clock_in_out type
                if ($validated['type'] === 'clock_in_out' && module_is_active('Hrm')) {
                    $remainingHours = $this->getRemainingHours($validated['user_id'] ?? $timesheet->user_id, $validated['date'], $timesheet->id);
                    $requestedMinutes = ($validated['hours'] * 60) + $validated['minutes'];
                    $availableMinutes = ($remainingHours['hours'] * 60) + $remainingHours['minutes'];
                    
                    if ($requestedMinutes > $availableMinutes) {
                        return back()->with('error', __('Insufficient remaining hours. Available: :hours hours :minutes minutes', [
                            'hours' => $remainingHours['hours'],
                            'minutes' => $remainingHours['minutes']
                        ]));
                    }
                }
                
                $timesheet->update($validated);

                UpdateTimesheet::dispatch($request, $timesheet);

                return back()->with('success', __('The timesheet details are updated successfully.'));
            } catch (\Exception $e) {
                \Log::error('Error updating timesheet: ' . $e->getMessage());
                return back()->with('error', __('Failed to update timesheet. Please try again.'));
            }
        }
        return redirect()->route('timesheet.index')->with('error', __('Permission denied'));
    }

    public function destroy(Timesheet $timesheet)
    {
        if (Auth::user()->can('delete-timesheet') && $this->canAccessTimesheet($timesheet)) {
            try {
                DestroyTimesheet::dispatch($timesheet);
                
                $timesheet->delete();
                return back()->with('success', __('The timesheet has been deleted.'));
            } catch (\Exception $e) {
                \Log::error('Error deleting timesheet: ' . $e->getMessage());
                return back()->with('error', __('Failed to delete timesheet. Please try again.'));
            }
        }
        return redirect()->route('timesheet.index')->with('error', __('Permission denied'));
    }

    private function canAccessTimesheet(Timesheet $timesheet)
    {
        if (Auth::user()->can('manage-any-timesheet')) {
            return $timesheet->created_by === creatorId();
        }
        return $timesheet->user_id === Auth::id();
    }

    public function getAttendanceHours($userId, $date)
    {
        if (!module_is_active('Hrm')) {
            return null;
        }

        $attendance = \Workdo\Hrm\Models\Attendance::where('employee_id', $userId)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance || !$attendance->total_hour) {
            return null;
        }

        $totalHours = $attendance->total_hour;
        $hours = floor($totalHours);
        $minutes = ($totalHours - $hours) * 60;

        return [
            'hours' => $hours,
            'minutes' => round($minutes / 15) * 15 // Round to nearest 15 minutes
        ];
    }

    public function fetchAttendanceHours()
    {
        $userId = request('user_id');
        $date = request('date');
        $excludeId = request('exclude_id');

        if (!$userId || !$date) {
            return response()->json(['error' => __('User ID and date are required')], 400);
        }

        $attendance = $this->getAttendanceHours($userId, $date);
        $remainingHours = $this->getRemainingHours($userId, $date, $excludeId);
        
        return response()->json([
            'total_hours' => $attendance['hours'] ?? 0,
            'total_minutes' => $attendance['minutes'] ?? 0,
            'remaining_hours' => $remainingHours['hours'] ?? 0,
            'remaining_minutes' => $remainingHours['minutes'] ?? 0,
            'used_hours' => ($attendance['hours'] ?? 0) - ($remainingHours['hours'] ?? 0),
            'used_minutes' => ($attendance['minutes'] ?? 0) - ($remainingHours['minutes'] ?? 0)
        ]);
    }

    public function getRemainingHours($userId, $date, $excludeTimesheetId = null)
    {
        if (!module_is_active('Hrm')) {
            return ['hours' => 0, 'minutes' => 0];
        }

        $totalAttendance = $this->getAttendanceHours($userId, $date);
        if (!$totalAttendance) {
            return ['hours' => 0, 'minutes' => 0];
        }

        // Get existing timesheets for the day, excluding current one if editing
        $query = Timesheet::where('user_id', $userId)->whereDate('date', $date);
        if ($excludeTimesheetId) {
            $query->where('id', '!=', $excludeTimesheetId);
        }
        $existingTimesheets = $query->get();

        $usedHours = $existingTimesheets->sum('hours');
        $usedMinutes = $existingTimesheets->sum('minutes');

        // Convert to total minutes for calculation
        $totalMinutes = ($totalAttendance['hours'] * 60) + $totalAttendance['minutes'];
        $usedTotalMinutes = ($usedHours * 60) + $usedMinutes;
        $remainingMinutes = max(0, $totalMinutes - $usedTotalMinutes);

        return [
            'hours' => intval($remainingMinutes / 60),
            'minutes' => $remainingMinutes % 60
        ];
    }
}