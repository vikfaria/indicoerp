<?php

namespace Workdo\Slack\Providers;

use App\Events\CreatePurchaseInvoice;
use App\Events\CreateSalesInvoice;
use App\Events\CreateSalesProposal;
use App\Events\CreateUser;
use App\Events\CreateWarehouse;
use App\Events\PostSalesInvoice;
use App\Events\SentSalesProposal;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Account\Events\CreateCustomer;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Account\Events\CreateVendor;
use Workdo\Appointment\Events\AppointmentStatus;
use Workdo\Appointment\Events\CreateAppointment;
use Workdo\CleaningManagement\Events\CreateCleaningBooking;
use Workdo\CleaningManagement\Events\CreateCleaningInvoice;
use Workdo\CleaningManagement\Events\CreateCleaningTeam;
use Workdo\CMMS\Events\CreateCmmsPos;
use Workdo\CMMS\Events\CreateComponent;
use Workdo\CMMS\Events\CreateLocation;
use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\CMMS\Events\CreateSupplier;
use Workdo\CMMS\Events\CreateWorkOrder;
use Workdo\CMMS\Events\CreateWorkrequest;
use Workdo\Contract\Events\CreateContract;
use Workdo\Documents\Events\CreateDocument;
use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use Workdo\FixEquipment\Events\CreateFixEquipmentAsset;
use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\FixEquipment\Events\CreateFixEquipmentConsumable;
use Workdo\FixEquipment\Events\CreateFixEquipmentLicense;
use Workdo\FixEquipment\Events\CreateFixEquipmentLocation;
use Workdo\FixEquipment\Events\CreateFixEquipmentMaintenance;
use Workdo\FormBuilder\Events\CreateForm;
use Workdo\FormBuilder\Events\FormConverted;
use Workdo\HospitalManagement\Events\CreateHospitalAppointment;
use Workdo\HospitalManagement\Events\CreateHospitalDoctor;
use Workdo\HospitalManagement\Events\CreateHospitalMedicine;
use Workdo\HospitalManagement\Events\CreateHospitalPatient;
use Workdo\Hrm\Events\CreateAward;
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
use Workdo\Portfolio\Events\CreatePortfolio;
use Workdo\Recruitment\Events\ConvertOfferToEmployee;
use Workdo\Recruitment\Events\CreateCandidate;
use Workdo\Recruitment\Events\CreateInterview;
use Workdo\Recruitment\Events\CreateJobPosting;
use Workdo\Retainer\Events\CreateRetainer;
use Workdo\Retainer\Events\CreateRetainerPayment;
use Workdo\Sales\Events\CreateSalesMeeting;
use Workdo\Sales\Events\CreateSalesOrder;
use Workdo\Sales\Events\CreateSalesQuote;
use Workdo\School\Events\CreateAdmission;
use Workdo\School\Events\CreateClassTimetable;
use Workdo\School\Events\CreateHomework;
use Workdo\School\Events\CreateParent;
use Workdo\School\Events\CreateStudent;
use Workdo\School\Events\CreateSubject;
use Workdo\Slack\Listeners\AppointmentStatusLis;
use Workdo\Slack\Listeners\CompleteToDoLis;
use Workdo\Slack\Listeners\ConvertOfferToEmployeeLis;
use Workdo\Slack\Listeners\CreateAdmissionLis;
use Workdo\Slack\Listeners\CreateAppointmentLis;
use Workdo\Slack\Listeners\CreateAwardLis;
use Workdo\Slack\Listeners\CreateCandidateLis;
use Workdo\Slack\Listeners\CreateCategoryLis;
use Workdo\Slack\Listeners\CreateChallengeLis;
use Workdo\Slack\Listeners\CreateClassTimetableLis;
use Workdo\Slack\Listeners\CreateCleaningBookingLis;
use Workdo\Slack\Listeners\CreateCleaningInvoiceLis;
use Workdo\Slack\Listeners\CreateCleaningTeamLis;
use Workdo\Slack\Listeners\CreateCmmsPosLis;
use Workdo\Slack\Listeners\CreateComponentLis;
use Workdo\Slack\Listeners\CreateContractLis;
use Workdo\Slack\Listeners\CreateCourseLis;
use Workdo\Slack\Listeners\CreateCreativityLis;
use Workdo\Slack\Listeners\CreateCustomerLis;
use Workdo\Slack\Listeners\CreateCustomPageLis;
use Workdo\Slack\Listeners\CreateDealLis;
use Workdo\Slack\Listeners\CreateDocumentLis;
use Workdo\Slack\Listeners\CreateFixEquipmentAccessoryLis;
use Workdo\Slack\Listeners\CreateFixEquipmentAssetLis;
use Workdo\Slack\Listeners\CreateFixEquipmentAuditLis;
use Workdo\Slack\Listeners\CreateFixEquipmentComponentLis;
use Workdo\Slack\Listeners\CreateFixEquipmentConsumableLis;
use Workdo\Slack\Listeners\CreateFixEquipmentLicenseLis;
use Workdo\Slack\Listeners\CreateFixEquipmentLocationLis;
use Workdo\Slack\Listeners\CreateFixEquipmentMaintenanceLis;
use Workdo\Slack\Listeners\CreateFormLis;
use Workdo\Slack\Listeners\CreateHistoryLis;
use Workdo\Slack\Listeners\CreateHomeworkLis;
use Workdo\Slack\Listeners\CreateHospitalAppointmentLis;
use Workdo\Slack\Listeners\CreateHospitalDoctorLis;
use Workdo\Slack\Listeners\CreateHospitalMedicineLis;
use Workdo\Slack\Listeners\CreateHospitalPatientLis;
use Workdo\Slack\Listeners\CreateInternalknowledgeArticleLis;
use Workdo\Slack\Listeners\CreateInternalknowledgeBookLis;
use Workdo\Slack\Listeners\CreateInterviewLis;
use Workdo\Slack\Listeners\CreateJobPostingLis;
use Workdo\Slack\Listeners\CreateLeadLis;
use Workdo\Slack\Listeners\CreateLocationLis;
use Workdo\Slack\Listeners\CreateMachineLis;
use Workdo\Slack\Listeners\CreateMachineRepairRequestLis;
use Workdo\Slack\Listeners\CreateNoteLis;
use Workdo\Slack\Listeners\CreateOrderLis;
use Workdo\Slack\Listeners\CreateParentLis;
use Workdo\Slack\Listeners\CreatePortfolioLis;
use Workdo\Slack\Listeners\CreatePreventiveMaintenanceLis;
use Workdo\Slack\Listeners\CreateProjectBugLis;
use Workdo\Slack\Listeners\CreateProjectLis;
use Workdo\Slack\Listeners\CreateProjectMilestoneLis;
use Workdo\Slack\Listeners\CreateProjectTaskLis;
use Workdo\Slack\Listeners\CreatePurchaseInvoiceLis;
use Workdo\Slack\Listeners\CreateRetainerLis;
use Workdo\Slack\Listeners\CreateRetainerPaymentLis;
use Workdo\Slack\Listeners\CreateRevenueLis;
use Workdo\Slack\Listeners\CreateSalesInvoiceLis;
use Workdo\Slack\Listeners\CreateSalesMeetingLis;
use Workdo\Slack\Listeners\CreateSalesOrderLis;
use Workdo\Slack\Listeners\CreateSalesProposalLis;
use Workdo\Slack\Listeners\CreateSalesQuoteLis;
use Workdo\Slack\Listeners\CreateSpreadsheetLis;
use Workdo\Slack\Listeners\CreateStudentLis;
use Workdo\Slack\Listeners\CreateSubjectLis;
use Workdo\Slack\Listeners\CreateSupplierLis;
use Workdo\Slack\Listeners\CreateTaskCommentLis;
use Workdo\Slack\Listeners\CreateTimesheetLis;
use Workdo\Slack\Listeners\CreateTimeTrackerLis;
use Workdo\Slack\Listeners\CreateToDoLis;
use Workdo\Slack\Listeners\CreateTrainerLis;
use Workdo\Slack\Listeners\CreateUserLis;
use Workdo\Slack\Listeners\CreateVendorLis;
use Workdo\Slack\Listeners\CreateVisitorLis;
use Workdo\Slack\Listeners\CreateWarehouseLis;
use Workdo\Slack\Listeners\CreateWoocommerceProductLis;
use Workdo\Slack\Listeners\CreateWorkorderLis;
use Workdo\Slack\Listeners\CreateWorkrequestLis;
use Workdo\Slack\Listeners\CreateZoommeetingLis;
use Workdo\Slack\Listeners\DealMovedLis;
use Workdo\Slack\Listeners\FormConvertedLis;
use Workdo\Slack\Listeners\LeadConvertDealLis;
use Workdo\Slack\Listeners\LeadMovedLis;
use Workdo\Slack\Listeners\PostSalesInvoiceLis;
use Workdo\Slack\Listeners\SentSalesProposalLis;
use Workdo\Slack\Listeners\UpdateProjectTaskStageLis;
use Workdo\Spreadsheet\Events\CreateSpreadsheet;
use Workdo\Taskly\Events\CreateProject;
use Workdo\Taskly\Events\CreateProjectBug;
use Workdo\Taskly\Events\CreateProjectMilestone;
use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Taskly\Events\CreateTaskComment;
use Workdo\Taskly\Events\UpdateProjectTaskStage;
use Workdo\Timesheet\Events\CreateTimesheet;
use Workdo\TimeTracker\Events\CreateTimeTracker;
use Workdo\ToDo\Events\CompleteToDo;
use Workdo\ToDo\Events\CreateToDo;
use Workdo\Training\Events\CreateTrainer;
use Workdo\VisitorManagement\Events\CreateVisitor;
use Workdo\WordpressWoocommerce\Events\CreateWoocommerceProduct;
use Workdo\ZoomMeeting\Events\CreateZoomMeeting;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CreateUser::class => [
            CreateUserLis::class,
        ],
        CreateSalesInvoice::class => [
            CreateSalesInvoiceLis::class
        ],
        PostSalesInvoice::class => [
            PostSalesInvoiceLis::class
        ],
        CreateSalesProposal::class => [
            CreateSalesProposalLis::class
        ],
        SentSalesProposal::class => [
            SentSalesProposalLis::class
        ],
        CreatePurchaseInvoice::class => [
            CreatePurchaseInvoiceLis::class
        ],
        CreateWarehouse::class => [
            CreateWarehouseLis::class
        ],
        CreateCustomer::class => [
            CreateCustomerLis::class
        ],
        CreateVendor::class => [
            CreateVendorLis::class
        ],
        CreateRevenue::class => [
            CreateRevenueLis::class
        ],
        CreateAppointment::class => [
            CreateAppointmentLis::class
        ],
        AppointmentStatus::class => [
            AppointmentStatusLis::class
        ],
        CreateWorkrequest::class => [
            CreateWorkrequestLis::class
        ],
        CreateSupplier::class => [
            CreateSupplierLis::class
        ],
        CreateCmmsPos::class => [
            CreateCmmsPosLis::class
        ],
        CreateWorkOrder::class => [
            CreateWorkorderLis::class
        ],
        CreateComponent::class => [
            CreateComponentLis::class
        ],
        CreateLocation::class => [
            CreateLocationLis::class
        ],
        CreatePreventiveMaintenance::class => [
            CreatePreventiveMaintenanceLis::class
        ],
        CreateContract::class => [
            CreateContractLis::class
        ],
        CreateAward::class => [
            CreateAwardLis::class,
        ],
        CreateLead::class => [
            CreateLeadLis::class
        ],
        LeadConvertDeal::class => [
            LeadConvertDealLis::class
        ],
        CreateDeal::class => [
            CreateDealLis::class
        ],
        LeadMoved::class => [
            LeadMovedLis::class
        ],
        DealMoved::class => [
            DealMovedLis::class
        ],
        CreateCandidate::class => [
            CreateCandidateLis::class
        ],
        CreateInterview::class => [
            CreateInterviewLis::class
        ],
        ConvertOfferToEmployee::class => [
            ConvertOfferToEmployeeLis::class
        ],
        CreateJobPosting::class => [
            CreateJobPostingLis::class
        ],
        CreateRetainer::class => [
            CreateRetainerLis::class
        ],
        CreateRetainerPayment::class => [
            CreateRetainerPaymentLis::class
        ],
        CreateSalesQuote::class => [
            CreateSalesQuoteLis::class
        ],
        CreateSalesOrder::class => [
            CreateSalesOrderLis::class
        ],
        CreateSalesMeeting::class => [
            CreateSalesMeetingLis::class
        ],
        CreateProject::class => [
            CreateProjectLis::class
        ],
        CreateProjectTask::class => [
            CreateProjectTaskLis::class
        ],
        CreateProjectBug::class => [
            CreateProjectBugLis::class
        ],
        CreateProjectMilestone::class => [
            CreateProjectMilestoneLis::class
        ],
        UpdateProjectTaskStage::class => [
            UpdateProjectTaskStageLis::class
        ],
        CreateTaskComment::class => [
            CreateTaskCommentLis::class
        ],
        CreateTrainer::class => [
            CreateTrainerLis::class
        ],
        CreateZoomMeeting::class => [
            CreateZoommeetingLis::class
        ],
        CreatePortfolio::class => [
            CreatePortfolioLis::class
        ],
        CreateSpreadsheet::class => [
            CreateSpreadsheetLis::class
        ],
        CreateFixEquipmentAccessory::class => [
            CreateFixEquipmentAccessoryLis::class
        ],
        CreateFixEquipmentAsset::class => [
            CreateFixEquipmentAssetLis::class
        ],
        CreateFixEquipmentAudit::class => [
            CreateFixEquipmentAuditLis::class
        ],
        CreateFixEquipmentComponent::class => [
            CreateFixEquipmentComponentLis::class
        ],
        CreateFixEquipmentConsumable::class => [
            CreateFixEquipmentConsumableLis::class
        ],
        CreateFixEquipmentLicense::class => [
            CreateFixEquipmentLicenseLis::class
        ],
        CreateFixEquipmentLocation::class => [
            CreateFixEquipmentLocationLis::class
        ],
        CreateFixEquipmentMaintenance::class => [
            CreateFixEquipmentMaintenanceLis::class
        ],
        CreateVisitor::class => [
            CreateVisitorLis::class
        ],
        CreateWoocommerceProduct::class => [
            CreateWoocommerceProductLis::class
        ],
        CreateAdmission::class => [
            CreateAdmissionLis::class
        ],
        CreateParent::class => [
            CreateParentLis::class
        ],
        CreateStudent::class => [
            CreateStudentLis::class
        ],
        CreateHomework::class => [
            CreateHomeworkLis::class
        ],
        CreateSubject::class => [
            CreateSubjectLis::class
        ],
        CreateClassTimetable::class => [
            CreateClassTimetableLis::class
        ],
        CreateCleaningTeam::class => [
            CreateCleaningTeamLis::class
        ],
        CreateCleaningBooking::class => [
            CreateCleaningBookingLis::class
        ],
        CreateCleaningInvoice::class => [
            CreateCleaningInvoiceLis::class
        ],
        CreateTimeTracker::class => [
            CreateTimeTrackerLis::class
        ],
        CreateMachine::class => [
            CreateMachineLis::class
        ],
        CreateMachineRepairRequest::class => [
            CreateMachineRepairRequestLis::class
        ],
        CreateHospitalDoctor::class => [
            CreateHospitalDoctorLis::class
        ],
        CreateHospitalPatient::class => [
            CreateHospitalPatientLis::class
        ],
        CreateHospitalAppointment::class => [
            CreateHospitalAppointmentLis::class
        ],
        CreateHospitalMedicine::class => [
            CreateHospitalMedicineLis::class
        ],
        CreateForm::class => [
            CreateFormLis::class
        ],
        FormConverted::class => [
            FormConvertedLis::class
        ],
        CreateTimesheet::class => [
            CreateTimesheetLis::class
        ],
        CreateNote::class => [
            CreateNoteLis::class
        ],
        CreateInternalknowledgeArticle::class => [
            CreateInternalknowledgeArticleLis::class
        ],
        CreateInternalknowledgeBook::class => [
            CreateInternalknowledgeBookLis::class
        ],
        CreateCreativity::class => [
            CreateCreativityLis::class
        ],
        CreateChallenge::class => [
            CreateChallengeLis::class
        ],
        CreateCategory::class => [
            CreateCategoryLis::class
        ],
        CreateToDo::class => [
            CreateToDoLis::class
        ],
        CompleteToDo::class => [
            CompleteToDoLis::class
        ],
        CreateDocument::class => [
            CreateDocumentLis::class
        ],

    ];
}
