<?php

namespace Workdo\Twilio\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\CreateSalesProposal;
use App\Events\PostSalesInvoice;
use App\Events\SentSalesProposal;
use App\Events\CreatePurchaseInvoice;
use App\Events\CreateUser;
use App\Events\CreateWarehouse;
use App\Events\CreateSalesInvoice;


use Workdo\Account\Events\CreateBankTransfer;
use Workdo\Account\Events\CreateCustomer;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Account\Events\CreateVendor;

use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Appointment\Events\CreateSchedule;

use Workdo\CleaningManagement\Events\CreateCleaningBooking;
use Workdo\CleaningManagement\Events\CreateCleaningInvoice;
use Workdo\CleaningManagement\Events\CreateCleaningTeam;

use Workdo\CMMS\Events\CreateCmmsPos;
use Workdo\CMMS\Events\CreateComponent;
use Workdo\CMMS\Events\CreateLocation;
use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\CMMS\Events\CreateSupplier;
use Workdo\CMMS\Events\CreateWorkOrder;
use Workdo\CMMS\Events\CreateWorkRequest;

use Workdo\Contract\Events\CreateContract;

use Workdo\Documents\Events\CreateDocument;
use Workdo\Documents\Events\StatusChangeDocument;

use Workdo\Feedback\Events\CreateHistory;
use Workdo\Feedback\Events\CreateTemplate;

use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use Workdo\FixEquipment\Events\CreateFixEquipmentAsset;
use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\FixEquipment\Events\CreateFixEquipmentConsumable;
use Workdo\FixEquipment\Events\CreateFixEquipmentLicense;
use Workdo\FixEquipment\Events\CreateFixEquipmentLocation;
use Workdo\FixEquipment\Events\CreateFixEquipmentMaintenance;

use Workdo\HospitalManagement\Events\CreateHospitalAppointment;
use Workdo\HospitalManagement\Events\CreateHospitalDoctor;
use Workdo\HospitalManagement\Events\CreateHospitalMedicine;
use Workdo\HospitalManagement\Events\CreateHospitalPatient;

use Workdo\Hrm\Events\CreateAnnouncement;
use Workdo\Hrm\Events\CreateAward;
use Workdo\Hrm\Events\CreateEvent;
use Workdo\Hrm\Events\CreateHoliday;
use Workdo\Hrm\Events\CreatePayroll;
use Workdo\Hrm\Events\UpdateLeaveStatus;

use Workdo\InnovationCenter\Events\CreateCategory;
use Workdo\InnovationCenter\Events\CreateChallenge;
use Workdo\InnovationCenter\Events\CreateCreativity;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeArticle;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeBook;

use Workdo\Lead\Events\CreateDeal;
use Workdo\Lead\Events\CreateLead;
use Workdo\Lead\Events\DealMoved;
use Workdo\Lead\Events\LeadConvertDeal;
use Workdo\Lead\Events\LeadMoved;

use Workdo\MachineRepairManagement\Events\CreateMachine;
use Workdo\MachineRepairManagement\Events\CreateMachineRepairRequest;

use Workdo\Notes\Events\CreateNote;

use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\Sales\Events\CreateSalesOrder;
use Workdo\Sales\Events\CreateSalesQuote;

use Workdo\School\Events\CreateAdmission;
use Workdo\School\Events\CreateClassTimetable;
use Workdo\School\Events\CreateEmployee;
use Workdo\School\Events\CreateHomework;
use Workdo\School\Events\CreateParent;
use Workdo\School\Events\CreateStudent;

use Workdo\Taskly\Events\CreateProjectBug;
use Workdo\Taskly\Events\CreateProject;
use Workdo\Taskly\Events\CreateProjectMilestone;
use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Taskly\Events\CreateTaskComment;
use Workdo\Taskly\Events\UpdateProjectTaskStage;

use Workdo\Timesheet\Events\CreateTimesheet;

use Workdo\ToDo\Events\CompleteToDo;
use Workdo\ToDo\Events\CreateToDo;

use Workdo\Twilio\Listeners\AppointmentStatusLis;
use Workdo\Twilio\Listeners\CompleteToDoLis;
use Workdo\Twilio\Listeners\CreateAdmissionLis;
use Workdo\Twilio\Listeners\CreateAnnouncementLis;
use Workdo\Twilio\Listeners\CreateAwardLis;
use Workdo\Twilio\Listeners\CreateBankTransferLis;
use Workdo\Twilio\Listeners\CreateCategoryLis;
use Workdo\Twilio\Listeners\CreateChallengeLis;
use Workdo\Twilio\Listeners\CreateClassTimetableLis;
use Workdo\Twilio\Listeners\CreateCleaningBookingLis;
use Workdo\Twilio\Listeners\CreateCleaningInvoiceLis;
use Workdo\Twilio\Listeners\CreateCleaningTeamLis;
use Workdo\Twilio\Listeners\CreateCmmsPosLis;
use Workdo\Twilio\Listeners\CreateComponentLis;
use Workdo\Twilio\Listeners\CreateContractLis;
use Workdo\Twilio\Listeners\CreateCreativityLis;
use Workdo\Twilio\Listeners\CreateCustomerLis;
use Workdo\Twilio\Listeners\CreateDealLis;
use Workdo\Twilio\Listeners\CreateDocumentsLis;
use Workdo\Twilio\Listeners\CreateEmployeeLis;
use Workdo\Twilio\Listeners\CreateEventLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentAccessoryLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentAssetLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentAuditLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentComponentLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentConsumableLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentLicenseLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentLocationLis;
use Workdo\Twilio\Listeners\CreateFixEquipmentMaintenanceLis;
use Workdo\Twilio\Listeners\CreateHistoryLis;
use Workdo\Twilio\Listeners\CreateHolidayLis;
use Workdo\Twilio\Listeners\CreateHomeworkLis;
use Workdo\Twilio\Listeners\CreateHospitalAppointmentLis;
use Workdo\Twilio\Listeners\CreateHospitalDoctorLis;
use Workdo\Twilio\Listeners\CreateHospitalMedicineLis;
use Workdo\Twilio\Listeners\CreateHospitalPatientLis;
use Workdo\Twilio\Listeners\CreateInternalknowledgeArticleLis;
use Workdo\Twilio\Listeners\CreateInternalknowledgeBookLis;
use Workdo\Twilio\Listeners\CreateLeadLis;
use Workdo\Twilio\Listeners\CreateLocationLis;
use Workdo\Twilio\Listeners\CreateMachineLis;
use Workdo\Twilio\Listeners\CreateMachineRepairRequestLis;
use Workdo\Twilio\Listeners\CreateNoteLis;
use Workdo\Twilio\Listeners\CreateParentLis;
use Workdo\Twilio\Listeners\CreatePayrollLis;
use Workdo\Twilio\Listeners\CreatePreventiveMaintenanceLis;
use Workdo\Twilio\Listeners\CreateProjectBugLis;
use Workdo\Twilio\Listeners\CreateProjectLis;
use Workdo\Twilio\Listeners\CreateProjectMilestoneLis;
use Workdo\Twilio\Listeners\CreateProjectTaskLis;
use Workdo\Twilio\Listeners\CreatePurchaseInvoiceLis;
use Workdo\Twilio\Listeners\CreateRevenueLis;
use Workdo\Twilio\Listeners\CreateSalesInvoiceLis;
use Workdo\Twilio\Listeners\CreateSalesMeetingLis;
use Workdo\Twilio\Listeners\CreateSalesOrderLis;
use Workdo\Twilio\Listeners\CreateSalesProposalLis;
use Workdo\Twilio\Listeners\CreateSalesQuoteLis;
use Workdo\Twilio\Listeners\CreateScheduleLis;
use Workdo\Twilio\Listeners\CreateStudentLis;
use Workdo\Twilio\Listeners\CreateSupplierLis;
use Workdo\Twilio\Listeners\CreateTaskCommentLis;
use Workdo\Twilio\Listeners\CreateTemplateLis;
use Workdo\Twilio\Listeners\CreateTimesheetLis;
use Workdo\Twilio\Listeners\CreateToDoLis;
use Workdo\Twilio\Listeners\CreateUserLis;
use Workdo\Twilio\Listeners\CreateVendorLis;
use Workdo\Twilio\Listeners\CreateVisitorLis;
use Workdo\Twilio\Listeners\CreateVisitPurposeLis;
use Workdo\Twilio\Listeners\CreateWarehouseLis;
use Workdo\Twilio\Listeners\CreateWoocommerceProductLis;
use Workdo\Twilio\Listeners\CreateWorkOrderLis;
use Workdo\Twilio\Listeners\CreateWorkRequestLis;
use Workdo\Twilio\Listeners\CreateZoomMeetingLis;
use Workdo\Twilio\Listeners\DealMovedLis;
use Workdo\Twilio\Listeners\LeadConvertDealLis;
use Workdo\Twilio\Listeners\LeadMovedLis;
use Workdo\Twilio\Listeners\PostSalesInvoiceLis;
use Workdo\Twilio\Listeners\SentSalesProposalLis;
use Workdo\Twilio\Listeners\StatusChangeDocumentLis;
use Workdo\Twilio\Listeners\UpdateLeaveStatusLis;
use Workdo\Twilio\Listeners\UpdateProjectTaskStageLis;

use Workdo\VisitorManagement\Events\CreateVisitor;
use Workdo\VisitorManagement\Events\CreateVisitPurpose;

use Workdo\WordpressWoocommerce\Events\CreateWoocommerceProduct;

use Workdo\ZoomMeeting\Events\CreateZoomMeeting;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CreateUser::class                     => [
            CreateUserLis::class,
        ],
        CreateSalesInvoice::class             => [
            CreateSalesInvoiceLis::class
        ],
        PostSalesInvoice::class               => [
            PostSalesInvoiceLis::class
        ],
        CreateSalesProposal::class            => [
            CreateSalesProposalLis::class
        ],
        SentSalesProposal::class              => [
            SentSalesProposalLis::class
        ],
        CreateBankTransfer::class             => [
            CreateBankTransferLis::class
        ],
        CreatePurchaseInvoice::class          => [
            CreatePurchaseInvoiceLis::class
        ],
        CreateWarehouse::class                => [
            CreateWarehouseLis::class
        ],
            // Appointment
        AppointmentStatus::class              => [
            AppointmentStatusLis::class
        ],
        CreateSchedule::class                 => [
            CreateScheduleLis::class
        ],
            // CMMS
        CreateCmmsPos::class                  => [
            CreateCmmsPosLis::class
        ],
        CreateComponent::class                => [
            CreateComponentLis::class
        ],
        CreateLocation::class                 => [
            CreateLocationLis::class
        ],
        CreatePreventiveMaintenance::class    => [
            CreatePreventiveMaintenanceLis::class
        ],
        CreateSupplier::class                 => [
            CreateSupplierLis::class
        ],
        CreateWorkOrder::class                => [
            CreateWorkOrderLis::class
        ],
        CreateWorkRequest::class              => [
            CreateWorkRequestLis::class
        ],
            // contract
        CreateContract::class                 => [
            CreateContractLis::class
        ],
            // lead
        CreateDeal::class                     => [
            CreateDealLis::class
        ],
        CreateLead::class                     => [
            CreateLeadLis::class
        ],
        DealMoved::class                      => [
            DealMovedLis::class
        ],
        LeadConvertDeal::class                => [
            LeadConvertDealLis::class
        ],
        LeadMoved::class                      => [
            LeadMovedLis::class
        ],
            // Sales
        CreateSalesMeeting::class             => [
            CreateSalesMeetingLis::class
        ],
        CreateSalesQuote::class               => [
            CreateSalesQuoteLis::class
        ],
        CreateSalesOrder::class               => [
            CreateSalesOrderLis::class
        ],
            // Taskly
        CreateProjectBug::class               => [
            CreateProjectBugLis::class
        ],
        CreateProject::class                  => [
            CreateProjectLis::class
        ],
        CreateProjectMilestone::class         => [
            CreateProjectMilestoneLis::class
        ],
        CreateProjectTask::class              => [
            CreateProjectTaskLis::class
        ],
        CreateTaskComment::class              => [
            CreateTaskCommentLis::class
        ],
        UpdateProjectTaskStage::class         => [
            UpdateProjectTaskStageLis::class
        ],
            // ZoomMeeting
        CreateZoomMeeting::class              => [
            CreateZoomMeetingLis::class
        ],
            // FixEquipment
        CreateFixEquipmentAccessory::class    => [
            CreateFixEquipmentAccessoryLis::class
        ],
        CreateFixEquipmentAsset::class        => [
            CreateFixEquipmentAssetLis::class
        ],
        CreateFixEquipmentAudit::class        => [
            CreateFixEquipmentAuditLis::class
        ],
        CreateFixEquipmentComponent::class    => [
            CreateFixEquipmentComponentLis::class
        ],
        CreateFixEquipmentConsumable::class   => [
            CreateFixEquipmentConsumableLis::class
        ],
        CreateFixEquipmentLicense::class      => [
            CreateFixEquipmentLicenseLis::class
        ],
        CreateFixEquipmentMaintenance::class  => [
            CreateFixEquipmentMaintenanceLis::class
        ],
        CreateFixEquipmentLocation::class     => [
            CreateFixEquipmentLocationLis::class
        ],
            // VisitorManagement
        CreateVisitor::class                  => [
            CreateVisitorLis::class
        ],
        CreateVisitPurpose::class             => [
            CreateVisitPurposeLis::class
        ],
            // WordpressWoocommerce
        CreateWoocommerceProduct::class       => [
            CreateWoocommerceProductLis::class
        ],
            // Feedback
        CreateHistory::class                  => [
            CreateHistoryLis::class
        ],
        CreateTemplate::class                 => [
            CreateTemplateLis::class
        ],
            // School
        CreateAdmission::class                => [
            CreateAdmissionLis::class
        ],
        CreateClassTimetable::class           => [
            CreateClassTimetableLis::class
        ],
        CreateEmployee::class                 => [
            CreateEmployeeLis::class
        ],
        CreateHomework::class                 => [
            CreateHomeworkLis::class
        ],
        CreateParent::class                   => [
            CreateParentLis::class
        ],
        CreateStudent::class                  => [
            CreateStudentLis::class
        ],
            // CleaningManagement
        CreateCleaningBooking::class          => [
            CreateCleaningBookingLis::class
        ],
        CreateCleaningInvoice::class          => [
            CreateCleaningInvoiceLis::class
        ],
        CreateCleaningTeam::class             => [
            CreateCleaningTeamLis::class
        ],
            // MachineRepairManagement
        CreateMachine::class                  => [
            CreateMachineLis::class
        ],
        CreateMachineRepairRequest::class     => [
            CreateMachineRepairRequestLis::class
        ],
            // HospitalManagement
        CreateHospitalAppointment::class      => [
            CreateHospitalAppointmentLis::class
        ],
        CreateHospitalDoctor::class           => [
            CreateHospitalDoctorLis::class
        ],
        CreateHospitalMedicine::class         => [
            CreateHospitalMedicineLis::class
        ],
        CreateHospitalPatient::class          => [
            CreateHospitalPatientLis::class
        ],
            // Timesheet
        CreateTimesheet::class                => [
            CreateTimesheetLis::class
        ],
            // Notes
        CreateNote::class                     => [
            CreateNoteLis::class
        ],
            // Internalknowledge
        CreateInternalknowledgeArticle::class => [
            CreateInternalknowledgeArticleLis::class
        ],
        CreateInternalknowledgeBook::class    => [
            CreateInternalknowledgeBookLis::class
        ],
            // InnovationCenter
        CreateCategory::class                 => [
            CreateCategoryLis::class
        ],
        CreateChallenge::class                => [
            CreateChallengeLis::class
        ],
        CreateCreativity::class               => [
            CreateCreativityLis::class
        ],
            // ToDo
        CompleteToDo::class                   => [
            CompleteToDoLis::class
        ],
        CreateToDo::class                     => [
            CreateToDoLis::class
        ],
            // Documents
        CreateDocument::class                 => [
            CreateDocumentsLis::class
        ],
        StatusChangeDocument::class           => [
            StatusChangeDocumentLis::class
        ],
            // Account
        CreateCustomer::class                 => [
            CreateCustomerLis::class
        ],
        CreateRevenue::class                  => [
            CreateRevenueLis::class
        ],
        CreateVendor::class                   => [
            CreateVendorLis::class
        ],
            // Hrm
        CreateAnnouncement::class             => [
            CreateAnnouncementLis::class
        ],
        CreateAward::class                    => [
            CreateAwardLis::class
        ],
        CreateEvent::class                    => [
            CreateEventLis::class
        ],
        CreateHoliday::class                  => [
            CreateHolidayLis::class
        ],
        CreatePayroll::class                  => [
            CreatePayrollLis::class
        ],
        UpdateLeaveStatus::class              => [
            UpdateLeaveStatusLis::class
        ],
    ];
}
