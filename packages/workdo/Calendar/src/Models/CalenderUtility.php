<?php

namespace Workdo\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\GoogleCalendar\Event as GoogleEvent;
use Illuminate\Support\Carbon;

class CalenderUtility extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Workdo\Calendar\Database\factories\CalenderUtilityFactory::new();
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $client_permissions = [
            'manage-calendar',
            'view-calendar'
        ];

        $staff_permissions = [
            'manage-calendar',
        ];

        if ($role_id == null) {
            // client
            $roles_c = Role::where('name', 'client')->get();
            foreach ($roles_c as $role) {
                foreach ($client_permissions as $permission_c) {
                    $permission = Permission::where('name', $permission_c)->first();
                    if ($permission && !$role->hasPermissionTo($permission_c)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }

            // staff
            $roles_s = Role::where('name', 'staff')->get();
            foreach ($roles_s as $role) {
                foreach ($staff_permissions as $permission_s) {
                    $permission = Permission::where('name', $permission_s)->first();
                    if ($permission && !$role->hasPermissionTo($permission_s)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        } else {

            if ($rolename == 'client') {
                $roles_c = Role::where('name', 'client')->where('id', $role_id)->first();
                if ($roles_c) {
                    foreach ($client_permissions as $permission_c) {
                        $permission = Permission::where('name', $permission_c)->first();
                        if ($permission && !$roles_c->hasPermissionTo($permission_c)) {
                            $roles_c->givePermissionTo($permission);
                        }
                    }
                }
            } elseif ($rolename == 'staff') {
                $roles_s = Role::where('name', 'staff')->where('id', $role_id)->first();
                if ($roles_s) {
                    foreach ($staff_permissions as $permission_s) {
                        $permission = Permission::where('name', $permission_s)->first();
                        if ($permission && !$roles_s->hasPermissionTo($permission_s)) {
                            $roles_s->givePermissionTo($permission);
                        }
                    }
                }
            }
        }
    }

    public static function colorCodeData($type)
    {
        if ($type == 'holiday' || $type == 'leave') {
            return 1;
        } elseif ($type == 'deal_task' || $type == 'lead_task' || $type == 'rotas' || $type == 'projecttask' || $type == 'work_order' || $type == 'todo' || $type == 'hearing_date') {
            return 2;
        } elseif ($type == 'event' || $type == 'google_meet' || $type == 'interview_schedule' || $type == 'meeting' || $type == 'zoom_meeting' || $type == 'vcard_appointment' || $type == 'procurement_interview_schedule' || $type == 'school_event') {
            return 3;
        } elseif ($type == 'due_invoice' || $type == 'sales_invoice' || $type == 'purchase_invoice' || $type == 'contract_end') {
            return 4;
        } elseif ($type == 'call' || $type == 'appointment' || $type == 'hospital_appointment') {
            return 5;
        } else {
            return 6;
        }
    }

    public static $colorCode = [
        1 => 'event-danger border-danger',
        2 => 'event-primary border-primary',
        3 => 'event-info border-info',
        4 => 'event-warning border-warning',
        5 => 'event-success border-success',
        6 => 'event-secondary border-secondary',
        7 => 'event-black',
        8 => 'event-info',
        9 => 'event-dark',
        10 => 'event-success',
        11 => 'event-warning',
    ];

    public static function googleCalendarConfig($created_by = null)
    {
        try {
            $calendarJsonContent = company_setting('google_calendar_json_file', $created_by ?? creatorId());
            $calendarId = company_setting('google_calendar_id', $created_by ?? creatorId());

            if (empty($calendarJsonContent) || empty($calendarId)) {
                return false;
            }

            // Validate JSON content
            $jsonData = json_decode($calendarJsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Create temporary file for JSON content
            $tempFile = tempnam(sys_get_temp_dir(), 'google_calendar_');
            if (!$tempFile || !file_put_contents($tempFile, $calendarJsonContent)) {
                return false;
            }

            config([
                'google-calendar.default_auth_profile' => 'service_account',
                'google-calendar.auth_profiles.service_account.credentials_json' => $tempFile,
                'google-calendar.auth_profiles.oauth.credentials_json' => $tempFile,
                'google-calendar.auth_profiles.oauth.token_json' => $tempFile,
                'google-calendar.calendar_id' => $calendarId,
                'google-calendar.user_to_impersonate' => '',
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function addCalendarData($request, $type, $created_by = null)
    {
        try {
            $googleCalendarIsEnabled = company_setting('google_calendar_enable', $created_by);
            if (empty($googleCalendarIsEnabled) || $googleCalendarIsEnabled !== "on") {
                return ['error' => __('Google Calendar sync is disabled.')];
            }

            if (!self::googleCalendarConfig($created_by)) {
                return ['error' => __('Google Calendar configuration is invalid.')];
            }
            $event = new GoogleEvent();
            $event->name = $request->title ?? $request->name ?? 'Untitled Event';
            $event->summary = $request->title ?? $request->name ?? 'Untitled Event';

            // Handle different date formats
            $startDate = $request->start_date ?? ($request->date . ' ' . ($request->time ?? '00:00:00'));
            $endDate = $request->end_date ?? $startDate;

            $event->startDateTime = Carbon::parse($startDate);
            $event->endDateTime = Carbon::parse($endDate);
            $event->colorId = self::colorCodeData($type);
            $event->description = $type . ' - ' . ($request->description ?? '');

            $savedEvent = $event->save();

            return ['success' => true, 'event_id' => $savedEvent->id ?? null];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function getCalendarData($type, $created_by = null)
    {
        try {
            $googleCalendarIsEnabled = company_setting('google_calendar_enable', $created_by);
            if (empty($googleCalendarIsEnabled) || $googleCalendarIsEnabled !== "on") {
                return ['error' => __('Google Calendar sync is disabled.')];
            }

            if (!self::googleCalendarConfig($created_by)) {
                return ['error' => __('Google Calendar configuration is invalid.')];
            }

            $events = GoogleEvent::get();
            if (!$events) {
                return [];
            }

            $result = [];
            foreach ($events as $event) {
                if (!$event->summary) {
                    continue; // Skip events without title
                }

                $startDateTime = $event->startDateTime ?? $event->startDate;
                $endDateTime = $event->endDateTime ?? $event->endDate;

                if (!$startDateTime) {
                    continue; // Skip events without start time
                }

                // Handle end date
                if ($endDateTime) {
                    $endDate = date_create($endDateTime);
                } else {
                    $endDate = date_create($startDateTime);
                    date_add($endDate, date_interval_create_from_date_string("1 hour"));
                }

                $result[] = [
                    "id" => 'google_' . $event->id,
                    "title" => $event->summary,
                    "start" => $startDateTime,
                    "end" => date_format($endDate, "Y-m-d H:i:s"),
                    "startDate" => $startDateTime,
                    "endDate" => date_format($endDate, "Y-m-d H:i:s"),
                    "time" => date('H:i', strtotime($startDateTime)),
                    "description" => $event->description ?? 'Google Calendar Event',
                    "event_type" => 'google',
                    "type" => 'google_event',
                    "color" => '#4285f4',
                    "attendees" => [],
                    "className" => self::$colorCode[$event->colorId] ?? 'event-secondary border-secondary',
                    "allDay" => empty($event->startDateTime),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch Google Calendar events: ' . $e->getMessage()];
        }
    }
}
