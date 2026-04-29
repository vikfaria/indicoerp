<?php

namespace Workdo\Calendar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Calendar\Models\CalenderUtility;
use Inertia\Inertia;
use Workdo\CMMS\Models\WorkOrder;
use Workdo\Contract\Models\Contract;
use Workdo\GoogleMeet\Models\GoogleMeeting;
use Workdo\HospitalManagement\Models\HospitalAppointment;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Event as HrmEvent;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Workdo\Appointment\Models\Schedule;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\LeadTask;
use Workdo\Recruitment\Models\Interview;
use Workdo\Sales\Models\SalesCall;
use Workdo\Sales\Models\SalesMeeting;
use Workdo\School\Models\SchoolEvent;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\TeamWorkload\Models\Holiday as TeamWorkloadHoliday;
use Workdo\ToDo\Models\ToDo;
use Workdo\ZoomMeeting\Models\ZoomMeeting;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-calendar')) {
            $calendarType = $request->get('calendar_type', 'local');
            $moduleFilter = $request->get('module_filter', 'all');

            $moduleWiseAvailableType = [
                'Base' => [
                    'sales_invoice' => __('Sales Invoice'),
                    'purchase_invoice' => __('Purchase Invoice'),
                ],
                'Hrm' => [
                    'event' => __('Event'),
                    'holiday' => __('Holiday'),
                    'leave' => __('Leave'),
                ],
                'Lead' => [
                    'deal_task' => __('Deal Task'),
                    'lead_task' => __('Lead Task'),
                ],
                'CMMS' => [
                    'work_order' => __('Work Order')
                ],
                'Appointment' => [
                    'appointment' => __('Appointment')
                ],
                'Contract' => [
                    'contract_end' => __('Contract End')
                ],
                'GoogleMeet' => [
                    'google_meet' => __('Google Meet')
                ],
                'HospitalManagement' => [
                    'hospital_appointment' => __('Hospital Appointment')
                ],
                'Sales' => [
                    'call' => __('Call'),
                    'meeting' => __('Meeting'),
                ],
                'School' => [
                    'school_event' => __('School Event')
                ],
                'Taskly' => [
                    // 'due_invoice' => __('Due Invoice'),
                    'projecttask' => __('Project Due Task'),
                ],
                'ToDo' => [
                    'todo' => __('To Do')
                ],
                'ZoomMeeting' => [
                    'zoom_meeting' => __('Zoom Meeting')
                ],
                'TeamWorkload' => [
                    'holiday' => __('Holiday')
                ],
                'Recruitment' => [
                    'interview_schedule' => __('Interview Schedule')
                ],



                // 'LegalCaseManagement' => [
                //     'hearing_date' => __('Hearing Date')
                // ],
                // 'Procurement' => [
                //     'procurement_interview_schedule' => __('Procurement Interview Schedule')
                // ],
                // 'Rotas' => [
                //     'rotas' => __('Rota')
                // ],
                // 'VCard' => [
                //     'vcard_appointment' => __('vCard Appointment')
                // ],
                // 'ZohoMeeting' => [
                //     'zoho_meeting' => __('Zoho Meeting')
                // ],
            ];

            $activeModules = [];
            $availableFilters = [];
            foreach ($moduleWiseAvailableType as $module => $types) {
                if ($module === 'Base' || module_is_active($module)) {
                    $activeModules[] = $module;
                    $availableFilters = array_merge($availableFilters, $types);
                }
            }

            $events = [];
            $created_by = creatorId();

            if ($calendarType == 'google') {
                try {
                    $googleEvents = CalenderUtility::getCalendarData('google', $created_by);
                    if (isset($googleEvents['error'])) {
                        return redirect()->back()->with('error', $googleEvents['error']);
                    } else {
                        // Filter Google Calendar events based on moduleFilter
                        if ($moduleFilter != 'all') {
                            $googleEvents = array_filter($googleEvents, function ($event) use ($moduleFilter) {
                                $description = strtolower($event['description'] ?? '');
                                return strpos($description, $moduleFilter) !== false ||
                                    strpos($description, str_replace('_', ' ', $moduleFilter)) !== false;
                            });
                            $googleEvents = array_values($googleEvents); // Re-index array
                        }
                        $events = $googleEvents;
                    }
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Failed to fetch Google Calendar events');
                }
            }

            if ($calendarType == 'local') {
                // Base Application Events
                if (in_array('Base', $activeModules)) {
                    if ($moduleFilter == "sales_invoice" || $moduleFilter == 'all') {
                        $salesInvoices = SalesInvoice::where('created_by', creatorId())->get();

                        foreach ($salesInvoices as $invoice) {

                            $events[] = [
                                'id' => 'sales_invoice_' . $invoice->id,
                                'title' => 'Sales Invoice - ' . ($invoice->invoice_number ?? 'INV-' . $invoice->id),
                                'start' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'end' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'startDate' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'endDate' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'time' => '09:00',
                                'description' => 'Due Invoice',
                                'event_type' => 'local',
                                'type' => 'sales_invoice',
                                'color' => '#f59e0b',
                                'raw_id' => $invoice->id,
                            ];
                        }
                    }
                    if ($moduleFilter == "purchase_invoice" || $moduleFilter == 'all') {
                        $purchaseInvoices = PurchaseInvoice::where('created_by', creatorId())->get();

                        foreach ($purchaseInvoices as $invoice) {
                            $events[] = [
                                'id' => 'purchase_invoice_' . $invoice->id,
                                'title' => 'Purchase Invoice - ' . ($invoice->invoice_number ?? 'BILL-' . $invoice->id),
                                'start' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'end' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'startDate' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'endDate' => $invoice->due_date ?? $invoice->created_at->format('Y-m-d'),
                                'time' => '09:00',
                                'description' => 'Due Bill',
                                'event_type' => 'local',
                                'type' => 'purchase_invoice',
                                'color' => '#ef4444',
                                'raw_id' => $invoice->id,
                            ];
                        }
                    }
                }
                if (in_array('Lead', $activeModules)) {
                    if ($moduleFilter == "deal_task" || $moduleFilter == 'all') {
                        $deal_tasks = DealTask::where('created_by', creatorId())->get();
                        foreach ($deal_tasks as $task) {
                            $events[] = [
                                'id' => 'deal_task_' . $task->id,
                                'title' => $task->name,
                                'start' => $task->date,
                                'end' => $task->date,
                                'startDate' => $task->date,
                                'endDate' => $task->date,
                                'time' => $task->time ?? '00:00',
                                'description' => 'Deal Task',
                                'event_type' => 'local',
                                'type' => 'deal_task',
                                'color' => '#3b82f6',
                                'raw_id' => $task->id,
                            ];
                        }
                    }
                    if ($moduleFilter == "lead_task" || $moduleFilter == 'all') {
                        $lead_tasks = LeadTask::where('created_by', creatorId())->get();
                        foreach ($lead_tasks as $task) {
                            $events[] = [
                                'id' => 'lead_task_' . $task->id,
                                'title' => $task->name,
                                'start' => $task->date,
                                'end' => $task->date,
                                'startDate' => $task->date,
                                'endDate' => $task->date,
                                'time' => $task->time ?? '00:00',
                                'description' => 'Lead Task',
                                'event_type' => 'local',
                                'type' => 'lead_task',
                                'color' => '#ef4444',
                                'raw_id' => $task->id,
                            ];
                        }
                    }
                }

                if (in_array('CMMS', $activeModules)) {
                    if ($moduleFilter == "work_order" || $moduleFilter == 'all') {
                        $work_orders = WorkOrder::where('created_by', creatorId())->get();
                        foreach ($work_orders as $workorder) {
                            $events[] = [
                                'id' => 'work_order_' . $workorder->id,
                                'title' => $workorder->workorder_name,
                                'start' => $workorder->due_date,
                                'end' => $workorder->due_date,
                                'startDate' => $workorder->due_date,
                                'endDate' => $workorder->due_date,
                                'time' => $workorder->time ?? '00:00',
                                'description' => 'Work Order',
                                'event_type' => 'local',
                                'type' => 'work_order',
                                'color' => '#10b77f',
                                'raw_id' => $workorder->id,
                            ];
                        }
                    }
                }

                if (in_array('Appointment', $activeModules)) {
                    if ($moduleFilter == "appointment" || $moduleFilter == 'all') {
                        $appointments = Schedule::where('created_by', creatorId())->get();
                        foreach ($appointments as $appointment) {
                            $events[] = [
                                'id' => 'appointment_' . $appointment->id,
                                'title' => $appointment->name,
                                'start' => $appointment->date,
                                'end' => $appointment->date,
                                'startDate' => $appointment->date,
                                'endDate' => $appointment->date,
                                'time' => $appointment->start_time ?? '00:00',
                                'description' => 'Appointment',
                                'event_type' => 'local',
                                'type' => 'appointment',
                                'color' => '#f59e0b',
                                'raw_id' => $appointment->id,
                            ];
                        }
                    }
                }

                if (in_array('Contract', $activeModules)) {
                    if ($moduleFilter == "contract_end" || $moduleFilter == 'all') {
                        $contracts = Contract::where('created_by', creatorId())->get();
                        foreach ($contracts as $contract) {
                            $events[] = [
                                'id' => 'contract_end_' . $contract->id,
                                'title' => $contract->subject,
                                'start' => $contract->start_date,
                                'end' => $contract->end_date,
                                'startDate' => $contract->start_date,
                                'endDate' => $contract->end_date,
                                'time' => $contract->time ?? '00:00',
                                'description' => 'Contract End',
                                'event_type' => 'local',
                                'type' => 'contract_end',
                                'color' => '#8b5cf6',
                                'raw_id' => $contract->id,
                            ];
                        }
                    }
                }

                if (in_array('GoogleMeet', $activeModules)) {
                    if ($moduleFilter == "google_meet" || $moduleFilter == 'all') {
                        $google_meets = GoogleMeeting::where('created_by', creatorId())->get();
                        foreach ($google_meets as $meet) {
                            $events[] = [
                                'id' => 'google_meet_' . $meet->id,
                                'title' => $meet->title,
                                'start' => $meet->start_time ? $meet->start_time->format('Y-m-d') : null,
                                'end' => $meet->end_time ? $meet->end_time->format('Y-m-d') : null,
                                'startDate' => $meet->start_time ? $meet->start_time->format('Y-m-d') : null,
                                'endDate' => $meet->end_time ? $meet->end_time->format('Y-m-d') : null,
                                'time' => $meet->start_time ? $meet->start_time->format('H:i') : '00:00',
                                'description' => 'Google Meet',
                                'event_type' => 'local',
                                'type' => 'google_meet',
                                'color' => '#06b6d4',
                                'raw_id' => $meet->id,
                            ];
                        }
                    }
                }

                if (in_array('ZoomMeeting', $activeModules)) {
                    if ($moduleFilter == "zoom_meeting" || $moduleFilter == 'all') {
                        $zoom_meetings = ZoomMeeting::where('created_by', creatorId())->get();
                        foreach ($zoom_meetings as $meeting) {
                            $endTime = $meeting->start_time && $meeting->duration ? $meeting->start_time->copy()->addMinutes($meeting->duration) : $meeting->start_time;
                            $events[] = [
                                'id' => 'zoom_meeting_' . $meeting->id,
                                'title' => $meeting->title,
                                'start' => $meeting->start_time ? $meeting->start_time->format('Y-m-d') : null,
                                'end' => $endTime ? $endTime->format('Y-m-d') : null,
                                'startDate' => $meeting->start_time ? $meeting->start_time->format('Y-m-d') : null,
                                'endDate' => $endTime ? $endTime->format('Y-m-d') : null,
                                'time' => $meeting->start_time ? $meeting->start_time->format('H:i') : '00:00',
                                'description' => 'Zoom Meeting (' . ($meeting->duration ?? 0) . ' min)',
                                'event_type' => 'local',
                                'type' => 'zoom_meeting',
                                'color' => '#2563eb',
                                'raw_id' => $meeting->id,
                            ];
                        }
                    }
                }

                if (in_array('HospitalManagement', $activeModules)) {
                    if ($moduleFilter == "hospital_appointment" || $moduleFilter == 'all') {
                        $hospital_appointments = HospitalAppointment::where('created_by', creatorId())->get();
                        foreach ($hospital_appointments as $appointment) {
                            $events[] = [
                                'id' => 'hospital_appointment_' . $appointment->id,
                                'title' => $appointment->appointment_number,
                                'start' => $appointment->appointment_date,
                                'end' => $appointment->appointment_date,
                                'startDate' => $appointment->appointment_date,
                                'endDate' => $appointment->appointment_date,
                                'time' => $appointment->start_time ?? '00:00',
                                'description' => 'Hospital Appointment',
                                'event_type' => 'local',
                                'type' => 'hospital_appointment',
                                'color' => '#ec4899',
                                'raw_id' => $appointment->id,
                            ];
                        }
                    }
                }

                if (in_array('Sales', $activeModules)) {
                    if ($moduleFilter == "call" || $moduleFilter == 'all') {
                        $calls = SalesCall::where('created_by', creatorId())->get();
                        foreach ($calls as $call) {
                            $events[] = [
                                'id' => 'call_' . $call->id,
                                'title' => $call->name,
                                'start' => $call->start_date,
                                'end' => $call->end_date,
                                'startDate' => $call->start_date,
                                'endDate' => $call->end_date,
                                'time' => $call->start_date ? date('H:i', strtotime($call->start_date)) : '00:00',
                                'description' => 'Call',
                                'event_type' => 'local',
                                'type' => 'call',
                                'color' => '#84cc16',
                                'raw_id' => $call->id,
                            ];
                        }
                    }
                    if ($moduleFilter == "meeting" || $moduleFilter == 'all') {
                        $meetings = SalesMeeting::where('created_by', creatorId())->get();
                        foreach ($meetings as $meeting) {
                            $events[] = [
                                'id' => 'meeting_' . $meeting->id,
                                'title' => $meeting->name,
                                'start' => $meeting->start_date,
                                'end' => $meeting->end_date,
                                'startDate' => $meeting->start_date,
                                'endDate' => $meeting->end_date,
                                'time' => $meeting->start_date ? date('H:i', strtotime($meeting->start_date)) : '00:00',
                                'description' => 'Meeting',
                                'event_type' => 'local',
                                'type' => 'meeting',
                                'color' => '#f97316',
                                'raw_id' => $meeting->id,
                            ];
                        }
                    }
                }

                if (in_array('School', $activeModules)) {
                    if ($moduleFilter == "school_event" || $moduleFilter == 'all') {
                        $school_events = SchoolEvent::where('created_by', creatorId())->get();
                        foreach ($school_events as $event) {
                            $events[] = [
                                'id' => 'school_event_' . $event->id,
                                'title' => $event->title,
                                'start' => $event->event_date,
                                'end' => $event->event_date,
                                'startDate' => $event->event_date,
                                'endDate' => $event->event_date,
                                'time' => $event->start_time ?? '00:00',
                                'description' => 'School Event',
                                'event_type' => 'local',
                                'type' => 'school_event',
                                'color' => '#14b8a6',
                                'raw_id' => $event->id,
                            ];
                        }
                    }
                }

                if (in_array('Taskly', $activeModules)) {
                    if ($moduleFilter == "projecttask" || $moduleFilter == 'all') {
                        $tasks = ProjectTask::with('milestone')->where('created_by', creatorId())->get();
                        foreach ($tasks as $task) {
                            if ($task->milestone && $task->milestone->start_date) {
                                $startDate = $task->milestone->start_date->format('Y-m-d');
                                $endDate = $task->duration ? $task->milestone->start_date->copy()->addDays((int) $task->duration)->format('Y-m-d') : ($task->milestone->end_date ? $task->milestone->end_date->format('Y-m-d') : $startDate);
                            } else {
                                $startDate = $task->created_at->format('Y-m-d');
                                $endDate = $task->duration ? $task->created_at->copy()->addDays((int) $task->duration)->format('Y-m-d') : $startDate;
                            }
                            $events[] = [
                                'id' => 'projecttask_' . $task->id,
                                'title' => $task->title,
                                'start' => $startDate,
                                'end' => $endDate,
                                'startDate' => $startDate,
                                'endDate' => $endDate,
                                'time' => '00:00',
                                'description' => 'Project Task (' . ($task->duration ?? '0') . ' days)',
                                'event_type' => 'local',
                                'type' => 'projecttask',
                                'color' => '#7c3aed',
                                'raw_id' => $task->id,
                            ];
                        }
                    }
                }

                if (in_array('ToDo', $activeModules)) {
                    if ($moduleFilter == "todo" || $moduleFilter == 'all') {
                        $todos = ToDo::where('created_by', creatorId())->get();
                        foreach ($todos as $todo) {
                            $events[] = [
                                'id' => 'todo_' . $todo->id,
                                'title' => $todo->title,
                                'start' => $todo->due_date,
                                'end' => $todo->due_date,
                                'startDate' => $todo->due_date,
                                'endDate' => $todo->due_date,
                                'time' => $todo->time ?? '00:00',
                                'description' => 'To Do',
                                'event_type' => 'local',
                                'type' => 'todo',
                                'color' => '#059669',
                                'raw_id' => $todo->id,
                            ];
                        }
                    }
                }

                if (in_array('Hrm', $activeModules)) {
                    if ($moduleFilter == "event" || $moduleFilter == 'all') {
                        $hrmEvents = HrmEvent::where('created_by', creatorId())->where('status', 'approved')->get();
                        foreach ($hrmEvents as $hrmEvent) {
                            $startTime = $hrmEvent->start_time ?? '00:00:00';
                            $endTime = $hrmEvent->end_time ?? '23:59:59';

                            $events[] = [
                                'id' => 'hrm_event_' . $hrmEvent->id,
                                'title' => $hrmEvent->title,
                                'start' => $hrmEvent->start_date,
                                'end' => $hrmEvent->end_date,
                                'startDate' => $hrmEvent->start_date,
                                'endDate' => $hrmEvent->end_date,
                                'time' => $startTime,
                                'description' => 'Event',
                                'event_type' => 'local',
                                'type' => 'event',
                                'color' => $hrmEvent->color ?? '#3b82f6',
                                'raw_id' => $hrmEvent->id,
                            ];
                        }
                    }
                    if ($moduleFilter == "holiday" || $moduleFilter == 'all') {
                        $holidays = Holiday::where('created_by', creatorId())->get();
                        foreach ($holidays as $holiday) {
                            $events[] = [
                                'id' => 'hrm_holiday_' . $holiday->id,
                                'title' => $holiday->name ?? $holiday->title ?? 'Holiday',
                                'start' => $holiday->start_date,
                                'end' => $holiday->end_date,
                                'startDate' => $holiday->start_date,
                                'endDate' => $holiday->end_date,
                                'time' => '00:00',
                                'description' => 'Holiday',
                                'event_type' => 'local',
                                'type' => 'holiday',
                                'color' => '#ef4444',
                                'raw_id' => $holiday->id,
                            ];
                        }
                    }
                    if ($moduleFilter == "leave" || $moduleFilter == 'all') {
                        $leaves = LeaveApplication::with('employee')->where('created_by', creatorId())->whereIn('status', ['approved', 'pending'])->get();
                        foreach ($leaves as $leave) {
                            $events[] = [
                                'id' => 'hrm_leave_' . $leave->id,
                                'title' => 'Leave - ' . ($leave->employee->name ?? 'Employee'),
                                'start' => $leave->start_date,
                                'end' => $leave->end_date,
                                'startDate' => $leave->start_date,
                                'endDate' => $leave->end_date,
                                'time' => '00:00',
                                'description' => 'Leave Application',
                                'event_type' => 'local',
                                'type' => 'leave',
                                'color' => '#f59e0b',
                                'raw_id' => $leave->id,
                            ];
                        }
                    }
                }

                if (in_array('TeamWorkload', $activeModules)) {
                    if ($moduleFilter == "holiday" || $moduleFilter == 'all') {
                        $holidays = TeamWorkloadHoliday::where('created_by', creatorId())->get();
                        foreach ($holidays as $holiday) {
                            $events[] = [
                                'id' => 'teamworkload_holiday_' . $holiday->id,
                                'title' => $holiday->name ?? $holiday->title ?? 'Holiday',
                                'start' => $holiday->start_date,
                                'end' => $holiday->end_date,
                                'startDate' => $holiday->start_date,
                                'endDate' => $holiday->end_date,
                                'time' => '00:00',
                                'description' => 'Holiday',
                                'event_type' => 'local',
                                'type' => 'holiday',
                                'color' => '#f59e0b',
                                'raw_id' => $holiday->id,
                            ];
                        }
                    }
                }

                if (in_array('Recruitment', $activeModules)) {
                    if ($moduleFilter == "interview_schedule" || $moduleFilter == 'all') {
                        $interviews = Interview::with('candidate')->where('created_by', creatorId())->get();
                        foreach ($interviews as $interview) {
                            $events[] = [
                                'id' => 'interview_schedule_' . $interview->id,
                                'title' => 'Interview - ' . ($interview->candidate->first_name ?? '') . ' ' . ($interview->candidate->last_name ?? ''),
                                'start' => $interview->scheduled_date . ' ' . $interview->scheduled_time,
                                'end' => $interview->scheduled_date . ' ' . $interview->scheduled_time,
                                'startDate' => $interview->scheduled_date . ' ' . $interview->scheduled_time,
                                'endDate' => $interview->scheduled_date . ' ' . $interview->scheduled_time,
                                'time' => $interview->scheduled_time ?? '00:00',
                                'description' => 'Interview Schedule',
                                'event_type' => 'local',
                                'type' => 'interview_schedule',
                                'color' => '#8b5cf6',
                                'raw_id' => $interview->id,
                            ];
                        }
                    }
                }
            }

            $jsonFile = company_setting('google_calendar_json_file', $created_by);
            $calendarId = company_setting('google_calendar_id', $created_by);
            $calendarEnabled = company_setting('google_calendar_enable', $created_by);

            $hasGoogleCalendarConfig = !empty($jsonFile) && !empty($calendarId) && $calendarEnabled === 'on';

            return Inertia::render('Calendar/Calendar/Index', [
                'events' => $events,
                'filters' => [
                    'calendar_type' => $calendarType,
                    'module_filter' => $moduleFilter,
                ],
                'availableFilters' => $availableFilters,
                'hasGoogleCalendarConfig' => $hasGoogleCalendarConfig,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
