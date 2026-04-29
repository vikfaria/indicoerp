<?php

namespace Workdo\Telegram\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\CreateUser;
use Workdo\Telegram\Listeners\CreateUserLis;

use App\Events\CreatePurchaseInvoice;
use Workdo\Telegram\Listeners\CreatePurchaseInvoiceLis;

use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Telegram\Listeners\AppointmentStatusLis;

use Workdo\Appointment\Events\CreateSchedule;
use Workdo\Telegram\Listeners\CreateScheduleLis;

use Workdo\CMMS\Events\CreateComponent;
use Workdo\Telegram\Listeners\CreateComponentLis;

use Workdo\CMMS\Events\CreateLocation;
use Workdo\Telegram\Listeners\CreateLocationLis;

use Workdo\CMMS\Events\CreateSupplier;
use Workdo\Telegram\Listeners\CreateSupplierLis;

use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\Telegram\Listeners\CreatePreventiveMaintenanceLis;

use Workdo\CMMS\Events\CreateCmmsPos;
use Workdo\Telegram\Listeners\CreateCmmsPosLis;

use Workdo\CMMS\Events\CreateWorkOrder;
use Workdo\Telegram\Listeners\CreateWorkorderLis;

use Workdo\CMMS\Events\CreateWorkRequest;
use Workdo\Telegram\Listeners\CreateWorkRequestLis;

use Workdo\Contract\Events\CreateContract;
use Workdo\Telegram\Listeners\CreateContractLis;

use Workdo\Lead\Events\CreateLead;
use Workdo\Telegram\Listeners\CreateLeadLis;

use Workdo\Lead\Events\LeadConvertDeal;
use Workdo\Telegram\Listeners\LeadConvertDealLis;

use Workdo\Lead\Events\CreateDeal;
use Workdo\Telegram\Listeners\CreateDealLis;

use Workdo\Lead\Events\LeadMoved;
use Workdo\Telegram\Listeners\LeadMovedLis;

use Workdo\Lead\Events\DealMoved;
use Workdo\Telegram\Listeners\DealMovedLis;

use Workdo\Sales\Events\CreateSalesQuote;
use Workdo\Telegram\Listeners\CreateSalesQuoteLis;

use Workdo\Sales\Events\CreateSalesOrder;
use Workdo\Telegram\Listeners\CreateSalesOrderLis;

use App\Events\CreateSalesInvoice;
use Workdo\Telegram\Listeners\CreateSalesInvoiceLis;

use App\Events\CreateSalesProposal;
use Workdo\Telegram\Listeners\CreateSalesProposalLis;

use App\Events\CreateWarehouse;
use Workdo\Telegram\Listeners\CreateWarehouseLis;

use App\Events\PostSalesInvoice;
use Workdo\Telegram\Listeners\PostSalesInvoiceLis;

use App\Events\SentSalesProposal;
use Workdo\Telegram\Listeners\SentSalesProposalLis;

use Workdo\Account\Events\CreateBankTransfer;
use Workdo\Telegram\Listeners\CreateBankTransferLis;

use Workdo\Account\Events\CreateCustomer;
use Workdo\Telegram\Listeners\CreateCustomerLis;

use Workdo\Account\Events\CreateRevenue;
use Workdo\Telegram\Listeners\CreateRevenueLis;

use Workdo\Account\Events\CreateVendor;
use Workdo\Telegram\Listeners\CreateVendorLis;

use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\Telegram\Listeners\CreateSalesMeetingLis;

use Workdo\Taskly\Events\CreateProject;
use Workdo\Telegram\Listeners\CreateProjectLis;

use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Telegram\Listeners\CreateProjectTaskLis;

use Workdo\Taskly\Events\CreateProjectBug;
use Workdo\Telegram\Listeners\CreateProjectBugLis;

use Workdo\Taskly\Events\CreateProjectMilestone;
use Workdo\Telegram\Listeners\CreateProjectMilestoneLis;

use Workdo\Taskly\Events\UpdateProjectTaskStage;
use Workdo\Telegram\Listeners\UpdateProjectTaskStageLis;

use Workdo\Taskly\Events\CreateTaskComment;
use Workdo\Telegram\Listeners\CreateTaskCommentLis;

use Workdo\ZoomMeeting\Events\CreateZoomMeeting;
use Workdo\Telegram\Listeners\CreateZoommeetingLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use Workdo\Telegram\Listeners\CreateFixEquipmentAccessoryLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentAsset;
use Workdo\Telegram\Listeners\CreateFixEquipmentAssetLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\Telegram\Listeners\CreateFixEquipmentAuditLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\Telegram\Listeners\CreateFixEquipmentComponentLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentConsumable;
use Workdo\Telegram\Listeners\CreateFixEquipmentConsumableLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentLicense;
use Workdo\Telegram\Listeners\CreateFixEquipmentLicenseLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentLocation;
use Workdo\Telegram\Listeners\CreateFixEquipmentLocationLis;

use Workdo\FixEquipment\Events\CreateFixEquipmentMaintenance;
use Workdo\Telegram\Listeners\CreateFixEquipmentMaintenanceLis;

use Workdo\Feedback\Events\CreateHistory;
use Workdo\Telegram\Listeners\CreateHistoryLis;

use Workdo\Feedback\Events\CreateTemplate;
use Workdo\Telegram\Listeners\CreateTemplateLis;

use Workdo\VisitorManagement\Events\CreateVisitor;
use Workdo\Telegram\Listeners\CreateVisitorLis;

use Workdo\VisitorManagement\Events\CreateVisitPurpose;
use Workdo\Telegram\Listeners\CreateVisitPurposeLis;

use Workdo\School\Events\CreateEmployee;
use Workdo\Telegram\Listeners\CreateSchoolEmployeeLis;

use Workdo\School\Events\CreateAdmission;
use Workdo\Telegram\Listeners\CreateAdmissionLis;

use Workdo\School\Events\CreateParent;
use Workdo\Telegram\Listeners\CreateParentLis;

use Workdo\School\Events\CreateStudent;
use Workdo\Telegram\Listeners\CreateSchoolStudentLis;

use Workdo\School\Events\CreateHomework;
use Workdo\Telegram\Listeners\CreateHomeworkLis;

use Workdo\School\Events\CreateSubject;
use Workdo\Telegram\Listeners\CreateSubjectLis;

use Workdo\School\Events\CreateClassTimetable;
use Workdo\Telegram\Listeners\CreateClassTimetableLis;

use Workdo\CleaningManagement\Events\CreateCleaningTeam;
use Workdo\Telegram\Listeners\CreateCleaningTeamLis;

use Workdo\Telegram\Listeners\CreateCleaningBookingLis;
use Workdo\CleaningManagement\Events\CreateCleaningBooking;

use Workdo\CleaningManagement\Events\CreateCleaningInvoice;
use Workdo\Telegram\Listeners\CreateCleaningInvoiceLis;

use Workdo\MachineRepairManagement\Events\CreateMachine;
use Workdo\MachineRepairManagement\Events\CreateMachineRepairRequest;

use Workdo\Telegram\Listeners\CreateMachineLis;
use Workdo\Telegram\Listeners\CreateMachineRepairRequestLis;

use Workdo\HospitalManagement\Events\CreateHospitalDoctor;
use Workdo\Telegram\Listeners\CreateHospitalDoctorLis;

use Workdo\HospitalManagement\Events\CreateHospitalMedicine;
use Workdo\Telegram\Listeners\CreateHospitalMedicineLis;

use Workdo\HospitalManagement\Events\CreateHospitalPatient;
use Workdo\Telegram\Listeners\CreateHospitalPatientLis;

use Workdo\HospitalManagement\Events\CreateHospitalAppointment;
use Workdo\Telegram\Listeners\CreateHospitalAppointmentLis;

use Workdo\Timesheet\Events\CreateTimesheet;
use Workdo\Telegram\Listeners\CreateTimesheetLis;

use Workdo\Notes\Events\CreateNote;
use Workdo\Telegram\Listeners\CreateNoteLis;

use Workdo\Internalknowledge\Events\CreateInternalknowledgeBook;
use Workdo\Telegram\Listeners\CreateInternalknowledgeBookLis;

use Workdo\Internalknowledge\Events\CreateInternalknowledgeArticle;
use Workdo\Telegram\Listeners\CreateInternalknowledgeArticleLis;

use Workdo\InnovationCenter\Events\CreateCreativity;
use Workdo\Telegram\Listeners\CreateCreativityLis;

use Workdo\InnovationCenter\Events\CreateChallenge;
use Workdo\Telegram\Listeners\CreateChallengeLis;

use Workdo\InnovationCenter\Events\CreateCategory;
use Workdo\Telegram\Listeners\CreateCategoryLis;

use Workdo\ToDo\Events\CreateToDo;
use Workdo\Telegram\Listeners\CreateToDoLis;

use Workdo\ToDo\Events\CompleteToDo;
use Workdo\Telegram\Listeners\CompleteToDoLis;

use Workdo\Documents\Events\CreateDocument;
use Workdo\Telegram\Listeners\CreateDocumentLis;

use Workdo\Documents\Events\StatusChangeDocument;
use Workdo\Telegram\Listeners\StatusChangeDocumentLis;

use Workdo\Hrm\Events\CreateAnnouncement;
use Workdo\Telegram\Listeners\CreateAnnouncementLis;

use Workdo\Hrm\Events\CreateAward;
use Workdo\Telegram\Listeners\CreateAwardLis;

use Workdo\Hrm\Events\CreateEvent;
use Workdo\Telegram\Listeners\CreateEventLis;

use Workdo\Hrm\Events\CreateHoliday;
use Workdo\Telegram\Listeners\CreateHolidayLis;

use Workdo\Hrm\Events\CreatePayroll;
use Workdo\Telegram\Listeners\CreatePayrollLis;

use Workdo\Hrm\Events\UpdateLeaveStatus;
use Workdo\Telegram\Listeners\UpdateLeaveStatusLis;

use Workdo\Taskly\Events\UpdateTaskStage;
use Workdo\Telegram\Listeners\UpdateTaskStageLis;

use Workdo\WordpressWoocommerce\Events\CreateWoocommerceProduct;
use Workdo\Telegram\Listeners\CreateWoocommerceProductLis;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CreateUser::class => [
            CreateUserLis::class,
        ],

        PostSalesInvoice::class => [
            PostSalesInvoiceLis::class,
        ],

        SentSalesProposal::class => [
            SentSalesProposalLis::class,
        ],

        CreatePurchaseInvoice::class => [
            CreatePurchaseInvoiceLis::class,
        ],

        CreateWarehouse::class => [
            CreateWarehouseLis::class,
        ],

        CreateSalesProposal::class => [
            CreateSalesProposalLis::class,
        ],

        CreateCustomer::class => [
            CreateCustomerLis::class,
        ],

        CreateVendor::class => [
            CreateVendorLis::class,
        ],

        CreateRevenue::class => [
            CreateRevenueLis::class,
        ],

        CreateBankTransfer::class => [
            CreateBankTransferLis::class,
        ],

        AppointmentStatus::class => [
            AppointmentStatusLis::class,
        ],

        CreateSchedule::class => [
            CreateScheduleLis::class,
        ],

        CreateLocation::class => [
            CreateLocationLis::class,
        ],

        CreateSupplier::class => [
            CreateSupplierLis::class,
        ],

        CreateComponent::class => [
            CreateComponentLis::class,
        ],

        CreatePreventiveMaintenance::class => [
            CreatePreventiveMaintenanceLis::class,
        ],

        CreateCmmsPos::class => [
            CreateCmmsPosLis::class,
        ],

        CreateWorkOrder::class => [
            CreateWorkorderLis::class,
        ],

        CreateWorkRequest::class => [
            CreateWorkRequestLis::class,
        ],

        CreateContract::class => [
            CreateContractLis::class,
        ],

        CreatePayroll::class => [
            CreatePayrollLis::class,
        ],

        CreateAward::class => [
            CreateAwardLis::class,
        ],

        CreateEvent::class => [
            CreateEventLis::class,
        ],

        UpdateLeaveStatus::class => [
            UpdateLeaveStatusLis::class,
        ],

        CreateAnnouncement::class => [
            CreateAnnouncementLis::class,
        ],

        CreateHoliday::class => [
            CreateHolidayLis::class,
        ],

        CreateLead::class => [
            CreateLeadLis::class,
        ],

        LeadConvertDeal::class => [
            LeadConvertDealLis::class,
        ],

        CreateDeal::class => [
            CreateDealLis::class,
        ],

        LeadMoved::class => [
            LeadMovedLis::class,
        ],

        DealMoved::class => [
            DealMovedLis::class,
        ],

        CreateSalesQuote::class => [
            CreateSalesQuoteLis::class,
        ],

        CreateSalesOrder::class => [
            CreateSalesOrderLis::class,
        ],

        CreateSalesInvoice::class => [
            CreateSalesInvoiceLis::class,
        ],

        CreateSalesMeeting::class => [
            CreateSalesMeetingLis::class,
        ],

        CreateProject::class => [
            CreateProjectLis::class,
        ],

        CreateProjectTask::class => [
            CreateProjectTaskLis::class,
        ],

        CreateProjectBug::class => [
            CreateProjectBugLis::class,
        ],

        CreateProjectMilestone::class => [
            CreateProjectMilestoneLis::class,
        ],

        UpdateProjectTaskStage::class => [
            UpdateProjectTaskStageLis::class,
        ],

        UpdateTaskStage::class => [
            UpdateTaskStageLis::class,
        ],

        CreateTaskComment::class => [
            CreateTaskCommentLis::class,
        ],

        CreateZoomMeeting::class => [
            CreateZoommeetingLis::class
        ],

        CreateFixEquipmentAccessory::class => [
            CreateFixEquipmentAccessoryLis::class,
        ],

        CreateFixEquipmentAsset::class => [
            CreateFixEquipmentAssetLis::class,
        ],

        CreateFixEquipmentAudit::class => [
            CreateFixEquipmentAuditLis::class,
        ],

        CreateFixEquipmentComponent::class => [
            CreateFixEquipmentComponentLis::class,
        ],

        CreateFixEquipmentConsumable::class => [
            CreateFixEquipmentConsumableLis::class,
        ],

        CreateFixEquipmentLicense::class => [
            CreateFixEquipmentLicenseLis::class,
        ],

        CreateFixEquipmentLocation::class => [
            CreateFixEquipmentLocationLis::class,
        ],

        CreateFixEquipmentMaintenance::class => [
            CreateFixEquipmentMaintenanceLis::class,
        ],

        CreateVisitor::class => [
            CreateVisitorLis::class,
        ],

        CreateVisitPurpose::class => [
            CreateVisitPurposeLis::class,
        ],

        CreateTemplate::class => [
            CreateTemplateLis::class,
        ],

        CreateHistory::class => [
            CreateHistoryLis::class,
        ],

        CreateEmployee::class => [
            CreateSchoolEmployeeLis::class,
        ],

        CreateAdmission::class => [
            CreateAdmissionLis::class,
        ],

        CreateParent::class => [
            CreateParentLis::class,
        ],

        CreateStudent::class => [
            CreateSchoolStudentLis::class,
        ],

        CreateHomework::class => [
            CreateHomeworkLis::class,
        ],

        CreateSubject::class => [
            CreateSubjectLis::class,
        ],

        CreateClassTimetable::class => [
            CreateClassTimetableLis::class,
        ],

        CreateCleaningTeam::class => [
            CreateCleaningTeamLis::class,
        ],

        CreateCleaningBooking::class => [
            CreateCleaningBookingLis::class,
        ],

        CreateCleaningInvoice::class => [
            CreateCleaningInvoiceLis::class,
        ],

        CreateMachine::class => [
            CreateMachineLis::class,
        ],

        CreateMachineRepairRequest::class => [
            CreateMachineRepairRequestLis::class,
        ],

        CreateHospitalDoctor::class => [
            CreateHospitalDoctorLis::class,
        ],

        CreateHospitalPatient::class => [
            CreateHospitalPatientLis::class,
        ],

        CreateHospitalAppointment::class => [
            CreateHospitalAppointmentLis::class,
        ],

        CreateHospitalMedicine::class => [
            CreateHospitalMedicineLis::class,
        ],

        CreateTimesheet::class => [
            CreateTimesheetLis::class,
        ],

        CreateNote::class => [
            CreateNoteLis::class,
        ],

        CreateInternalknowledgeArticle::class => [
            CreateInternalknowledgeArticleLis::class,
        ],

        CreateInternalknowledgeBook::class => [
            CreateInternalknowledgeBookLis::class,
        ],

        CreateCreativity::class => [
            CreateCreativityLis::class,
        ],

        CreateChallenge::class => [
            CreateChallengeLis::class,
        ],

        CreateCategory::class => [
            CreateCategoryLis::class,
        ],

        CreateToDo::class => [
            CreateToDoLis::class,
        ],

        CompleteToDo::class => [
            CompleteToDoLis::class,
        ],

        CreateDocument::class => [
            CreateDocumentLis::class,
        ],

        StatusChangeDocument::class => [
            StatusChangeDocumentLis::class,
        ],

        CreateWoocommerceProduct::class => [
            CreateWoocommerceProductLis::class,
        ],
    ];
}
