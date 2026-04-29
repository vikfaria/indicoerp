<?php

namespace Workdo\Calendar\Providers;

use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Lead\Events\CreateDealTask;
use Workdo\Lead\Events\CreateLeadTask;
use Workdo\CMMS\Events\CreateWorkOrder;
use Workdo\Contract\Events\CreateContract;
use Workdo\GoogleMeet\Events\CreateGoogleMeeting;
use Workdo\HospitalManagement\Events\UpdateHospitalAppointmentStatus;
use Workdo\Sales\Events\CreateSalesCall;
use Workdo\ZoomMeeting\Events\CreateZoomMeeting;
use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\School\Events\CreateEvent;
use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\ToDo\Events\CreateToDo;
use Workdo\TeamWorkload\Events\CreateTeamWorkloadHoliday;
use Workdo\Recruitment\Events\CreateInterview;
use Workdo\Hrm\Events\CreateLeaveApplication;
use Workdo\Hrm\Events\CreateEvent as HrmCreateEvent;
use App\Events\CreateSalesInvoice;
use App\Events\CreatePurchaseInvoice;

use Workdo\Calendar\Listeners\CreateDealTaskLis;
use Workdo\Calendar\Listeners\CreateLeadTaskLis;
use Workdo\Calendar\Listeners\CreateWorkorderLis;
use Workdo\Calendar\Listeners\CreateAppointmentStatusListener;
use Workdo\Calendar\Listeners\CreateContractListener;
use Workdo\Calendar\Listeners\CreateGoogleMeetingListener;
use Workdo\Calendar\Listeners\CreateHospitalAppointmentListener;
use Workdo\Calendar\Listeners\CreateSalesCallListener;
use Workdo\Calendar\Listeners\CreateZoomMeetingListener;
use Workdo\Calendar\Listeners\CreateSalesMeetingListener;
use Workdo\Calendar\Listeners\CreateSchoolEventListener;
use Workdo\Calendar\Listeners\CreateProjectTaskListener;
use Workdo\Calendar\Listeners\CreateToDoListener;
use Workdo\Calendar\Listeners\CreateTeamWorkloadHolidayListener;
use Workdo\Calendar\Listeners\CreateInterviewListener;
use Workdo\Calendar\Listeners\CreateLeaveApplicationListener;
use Workdo\Calendar\Listeners\CreateEventListener;
use Workdo\Calendar\Listeners\CreateSalesInvoiceListener;
use Workdo\Calendar\Listeners\CreatePurchaseInvoiceListener;
use Workdo\Calendar\Listeners\DataDefault;
use Workdo\Calendar\Listeners\GiveRoleToPermission;


class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DefaultData::class => [
            DataDefault::class,
        ],
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
        CreateDealTask::class => [
            CreateDealTaskLis::class,
        ],
        CreateLeadTask::class => [
            CreateLeadTaskLis::class,
        ],
        CreateWorkOrder::class => [
            CreateWorkorderLis::class,
        ],
        AppointmentStatus::class => [
            CreateAppointmentStatusListener::class,
        ],
        CreateContract::class => [
            CreateContractListener::class,
        ],
        CreateGoogleMeeting::class => [
            CreateGoogleMeetingListener::class,
        ],
        UpdateHospitalAppointmentStatus::class => [
            CreateHospitalAppointmentListener::class,
        ],
        CreateZoomMeeting::class => [
            CreateZoomMeetingListener::class,
        ],
        CreateSalesCall::class => [
            CreateSalesCallListener::class,
        ],
        CreateSalesMeeting::class => [
            CreateSalesMeetingListener::class,
        ],
        CreateEvent::class => [
            CreateSchoolEventListener::class,
        ],
        CreateProjectTask::class => [
            CreateProjectTaskListener::class,
        ],
        CreateToDo::class => [
            CreateToDoListener::class,
        ],
        CreateTeamWorkloadHoliday::class => [
            CreateTeamWorkloadHolidayListener::class,
        ],
        CreateInterview::class => [
            CreateInterviewListener::class,
        ],
        CreateLeaveApplication::class => [
            CreateLeaveApplicationListener::class,
        ],
        HrmCreateEvent::class => [
            CreateEventListener::class,
        ],
        CreateSalesInvoice::class => [
            CreateSalesInvoiceListener::class,
        ],
        CreatePurchaseInvoice::class => [
            CreatePurchaseInvoiceListener::class,
        ],
    ];
}
