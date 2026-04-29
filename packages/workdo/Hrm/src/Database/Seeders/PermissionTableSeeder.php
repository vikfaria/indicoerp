<?php

namespace Workdo\Hrm\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [

            // Manage Dashboard
            ['name' => 'manage-hrm-dashboard', 'module' => 'Dashboard', 'label' => 'Manage HRM Dashboard'],

            ['name' => 'manage-hrm', 'module' => 'hrm', 'label' => 'Manage Hrm'],

            // Branch management
            ['name' => 'manage-branches', 'module' => 'branches', 'label' => 'Manage Branches'],
            ['name' => 'manage-any-branches', 'module' => 'branches', 'label' => 'Manage All Branches'],
            ['name' => 'manage-own-branches', 'module' => 'branches', 'label' => 'Manage Own Branches'],
            ['name' => 'create-branches', 'module' => 'branches', 'label' => 'Create Branches'],
            ['name' => 'edit-branches', 'module' => 'branches', 'label' => 'Edit Branches'],
            ['name' => 'delete-branches', 'module' => 'branches', 'label' => 'Delete Branches'],

            // Department management
            ['name' => 'manage-departments', 'module' => 'departments', 'label' => 'Manage Departments'],
            ['name' => 'manage-any-departments', 'module' => 'departments', 'label' => 'Manage All Departments'],
            ['name' => 'manage-own-departments', 'module' => 'departments', 'label' => 'Manage Own Departments'],
            ['name' => 'create-departments', 'module' => 'departments', 'label' => 'Create Departments'],
            ['name' => 'edit-departments', 'module' => 'departments', 'label' => 'Edit Departments'],
            ['name' => 'delete-departments', 'module' => 'departments', 'label' => 'Delete Departments'],

            // Designation management
            ['name' => 'manage-designations', 'module' => 'designations', 'label' => 'Manage Designations'],
            ['name' => 'manage-any-designations', 'module' => 'designations', 'label' => 'Manage All Designations'],
            ['name' => 'manage-own-designations', 'module' => 'designations', 'label' => 'Manage Own Designations'],
            ['name' => 'create-designations', 'module' => 'designations', 'label' => 'Create Designations'],
            ['name' => 'edit-designations', 'module' => 'designations', 'label' => 'Edit Designations'],
            ['name' => 'delete-designations', 'module' => 'designations', 'label' => 'Delete Designations'],

            // EmployeeDocumentType management
            ['name' => 'manage-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Manage EmployeeDocumentTypes'],
            ['name' => 'manage-any-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Manage All EmployeeDocumentTypes'],
            ['name' => 'manage-own-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Manage Own EmployeeDocumentTypes'],
            ['name' => 'create-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Create EmployeeDocumentTypes'],
            ['name' => 'edit-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Edit EmployeeDocumentTypes'],
            ['name' => 'delete-employee-document-types', 'module' => 'employee-document-types', 'label' => 'Delete EmployeeDocumentTypes'],

            // Employee management
            ['name' => 'manage-employees', 'module' => 'employees', 'label' => 'Manage Employees'],
            ['name' => 'manage-any-employees', 'module' => 'employees', 'label' => 'Manage All Employees'],
            ['name' => 'manage-own-employees', 'module' => 'employees', 'label' => 'Manage Own Employees'],
            ['name' => 'view-employees', 'module' => 'employees', 'label' => 'View Employees'],
            ['name' => 'create-employees', 'module' => 'employees', 'label' => 'Create Employees'],
            ['name' => 'edit-employees', 'module' => 'employees', 'label' => 'Edit Employees'],
            ['name' => 'delete-employees', 'module' => 'employees', 'label' => 'Delete Employees'],

            // AwardType management
            ['name' => 'manage-award-types', 'module' => 'award-types', 'label' => 'Manage AwardTypes'],
            ['name' => 'manage-any-award-types', 'module' => 'award-types', 'label' => 'Manage All AwardTypes'],
            ['name' => 'manage-own-award-types', 'module' => 'award-types', 'label' => 'Manage Own AwardTypes'],
            ['name' => 'create-award-types', 'module' => 'award-types', 'label' => 'Create AwardTypes'],
            ['name' => 'edit-award-types', 'module' => 'award-types', 'label' => 'Edit AwardTypes'],
            ['name' => 'delete-award-types', 'module' => 'award-types', 'label' => 'Delete AwardTypes'],

            // Award management
            ['name' => 'manage-awards', 'module' => 'awards', 'label' => 'Manage Awards'],
            ['name' => 'manage-any-awards', 'module' => 'awards', 'label' => 'Manage All Awards'],
            ['name' => 'manage-own-awards', 'module' => 'awards', 'label' => 'Manage Own Awards'],
            ['name' => 'create-awards', 'module' => 'awards', 'label' => 'Create Awards'],
            ['name' => 'view-awards', 'module' => 'awards', 'label' => 'View Awards'],
            ['name' => 'edit-awards', 'module' => 'awards', 'label' => 'Edit Awards'],
            ['name' => 'delete-awards', 'module' => 'awards', 'label' => 'Delete Awards'],

            // Promotion management
            ['name' => 'manage-promotions', 'module' => 'promotions', 'label' => 'Manage Promotions'],
            ['name' => 'manage-any-promotions', 'module' => 'promotions', 'label' => 'Manage All Promotions'],
            ['name' => 'manage-own-promotions', 'module' => 'promotions', 'label' => 'Manage Own Promotions'],
            ['name' => 'manage-promotions-status', 'module' => 'promotions', 'label' => 'Manage Promotions Status'],
            ['name' => 'view-promotions', 'module' => 'promotions', 'label' => 'View Promotions'],
            ['name' => 'create-promotions', 'module' => 'promotions', 'label' => 'Create Promotions'],
            ['name' => 'edit-promotions', 'module' => 'promotions', 'label' => 'Edit Promotions'],
            ['name' => 'delete-promotions', 'module' => 'promotions', 'label' => 'Delete Promotions'],

            // Resignation management
            ['name' => 'manage-resignations', 'module' => 'resignations', 'label' => 'Manage Resignations'],
            ['name' => 'manage-any-resignations', 'module' => 'resignations', 'label' => 'Manage All Resignations'],
            ['name' => 'manage-own-resignations', 'module' => 'resignations', 'label' => 'Manage Own Resignations'],
            ['name' => 'manage-resignation-status', 'module' => 'resignations', 'label' => 'Manage Resignation Status'],
            ['name' => 'view-resignations', 'module' => 'resignations', 'label' => 'View Resignations'],
            ['name' => 'create-resignations', 'module' => 'resignations', 'label' => 'Create Resignations'],
            ['name' => 'edit-resignations', 'module' => 'resignations', 'label' => 'Edit Resignations'],
            ['name' => 'delete-resignations', 'module' => 'resignations', 'label' => 'Delete Resignations'],

            // TerminationType management
            ['name' => 'manage-termination-types', 'module' => 'termination-types', 'label' => 'Manage TerminationTypes'],
            ['name' => 'manage-any-termination-types', 'module' => 'termination-types', 'label' => 'Manage All TerminationTypes'],
            ['name' => 'manage-own-termination-types', 'module' => 'termination-types', 'label' => 'Manage Own TerminationTypes'],
            ['name' => 'create-termination-types', 'module' => 'termination-types', 'label' => 'Create TerminationTypes'],
            ['name' => 'edit-termination-types', 'module' => 'termination-types', 'label' => 'Edit TerminationTypes'],
            ['name' => 'delete-termination-types', 'module' => 'termination-types', 'label' => 'Delete TerminationTypes'],

            // Termination management
            ['name' => 'manage-terminations', 'module' => 'terminations', 'label' => 'Manage Terminations'],
            ['name' => 'manage-any-terminations', 'module' => 'terminations', 'label' => 'Manage All Terminations'],
            ['name' => 'manage-own-terminations', 'module' => 'terminations', 'label' => 'Manage Own Terminations'],
            ['name' => 'manage-termination-status', 'module' => 'terminations', 'label' => 'Manage Termination Status'],
            ['name' => 'view-terminations', 'module' => 'terminations', 'label' => 'View Terminations'],
            ['name' => 'create-terminations', 'module' => 'terminations', 'label' => 'Create Terminations'],
            ['name' => 'edit-terminations', 'module' => 'terminations', 'label' => 'Edit Terminations'],
            ['name' => 'delete-terminations', 'module' => 'terminations', 'label' => 'Delete Terminations'],

            // WarningType management
            ['name' => 'manage-warning-types', 'module' => 'warning-types', 'label' => 'Manage WarningTypes'],
            ['name' => 'manage-any-warning-types', 'module' => 'warning-types', 'label' => 'Manage All WarningTypes'],
            ['name' => 'manage-own-warning-types', 'module' => 'warning-types', 'label' => 'Manage Own WarningTypes'],
            ['name' => 'create-warning-types', 'module' => 'warning-types', 'label' => 'Create WarningTypes'],
            ['name' => 'edit-warning-types', 'module' => 'warning-types', 'label' => 'Edit WarningTypes'],
            ['name' => 'delete-warning-types', 'module' => 'warning-types', 'label' => 'Delete WarningTypes'],

            // Warning management
            ['name' => 'manage-warnings', 'module' => 'warnings', 'label' => 'Manage Warnings'],
            ['name' => 'manage-any-warnings', 'module' => 'warnings', 'label' => 'Manage All Warnings'],
            ['name' => 'manage-own-warnings', 'module' => 'warnings', 'label' => 'Manage Own Warnings'],
            ['name' => 'manage-warning-response', 'module' => 'warnings', 'label' => 'Manage Warning Response'],
            ['name' => 'view-warnings', 'module' => 'warnings', 'label' => 'View Warnings'],
            ['name' => 'create-warnings', 'module' => 'warnings', 'label' => 'Create Warnings'],
            ['name' => 'edit-warnings', 'module' => 'warnings', 'label' => 'Edit Warnings'],
            ['name' => 'delete-warnings', 'module' => 'warnings', 'label' => 'Delete Warnings'],

            // ComplaintType management
            ['name' => 'manage-complaint-types', 'module' => 'complaint-types', 'label' => 'Manage ComplaintTypes'],
            ['name' => 'manage-any-complaint-types', 'module' => 'complaint-types', 'label' => 'Manage All ComplaintTypes'],
            ['name' => 'manage-own-complaint-types', 'module' => 'complaint-types', 'label' => 'Manage Own ComplaintTypes'],
            ['name' => 'create-complaint-types', 'module' => 'complaint-types', 'label' => 'Create ComplaintTypes'],
            ['name' => 'edit-complaint-types', 'module' => 'complaint-types', 'label' => 'Edit ComplaintTypes'],
            ['name' => 'delete-complaint-types', 'module' => 'complaint-types', 'label' => 'Delete ComplaintTypes'],

            // Complaint management
            ['name' => 'manage-complaints', 'module' => 'complaints', 'label' => 'Manage Complaints'],
            ['name' => 'manage-any-complaints', 'module' => 'complaints', 'label' => 'Manage All Complaints'],
            ['name' => 'manage-own-complaints', 'module' => 'complaints', 'label' => 'Manage Own Complaints'],
            ['name' => 'manage-complaint-status', 'module' => 'complaints', 'label' => 'Manage Complaint Status'],
            ['name' => 'view-complaints', 'module' => 'complaints', 'label' => 'View Complaints'],
            ['name' => 'create-complaints', 'module' => 'complaints', 'label' => 'Create Complaints'],
            ['name' => 'edit-complaints', 'module' => 'complaints', 'label' => 'Edit Complaints'],
            ['name' => 'delete-complaints', 'module' => 'complaints', 'label' => 'Delete Complaints'],

            // EmployeeTransfer management
            ['name' => 'manage-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Manage EmployeeTransfers'],
            ['name' => 'manage-any-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Manage All EmployeeTransfers'],
            ['name' => 'manage-own-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Manage Own EmployeeTransfers'],
            ['name' => 'manage-employee-transfers-status', 'module' => 'employee-transfers', 'label' => 'Manage EmployeeTransfers Status'],
            ['name' => 'view-employee-transfers', 'module' => 'employee-transfers', 'label' => 'View EmployeeTransfers'],
            ['name' => 'create-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Create EmployeeTransfers'],
            ['name' => 'edit-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Edit EmployeeTransfers'],
            ['name' => 'delete-employee-transfers', 'module' => 'employee-transfers', 'label' => 'Delete EmployeeTransfers'],

            // HolidayType management
            ['name' => 'manage-holiday-types', 'module' => 'holiday-types', 'label' => 'Manage HolidayTypes'],
            ['name' => 'manage-any-holiday-types', 'module' => 'holiday-types', 'label' => 'Manage All HolidayTypes'],
            ['name' => 'manage-own-holiday-types', 'module' => 'holiday-types', 'label' => 'Manage Own HolidayTypes'],
            ['name' => 'create-holiday-types', 'module' => 'holiday-types', 'label' => 'Create HolidayTypes'],
            ['name' => 'edit-holiday-types', 'module' => 'holiday-types', 'label' => 'Edit HolidayTypes'],
            ['name' => 'delete-holiday-types', 'module' => 'holiday-types', 'label' => 'Delete HolidayTypes'],

            // Holiday management
            ['name' => 'manage-holidays', 'module' => 'holidays', 'label' => 'Manage Holidays'],
            ['name' => 'manage-any-holidays', 'module' => 'holidays', 'label' => 'Manage All Holidays'],
            ['name' => 'manage-own-holidays', 'module' => 'holidays', 'label' => 'Manage Own Holidays'],
            ['name' => 'view-holidays', 'module' => 'holidays', 'label' => 'View Holidays'],
            ['name' => 'create-holidays', 'module' => 'holidays', 'label' => 'Create Holidays'],
            ['name' => 'edit-holidays', 'module' => 'holidays', 'label' => 'Edit Holidays'],
            ['name' => 'delete-holidays', 'module' => 'holidays', 'label' => 'Delete Holidays'],

            // DocumentCategory management
            ['name' => 'manage-document-categories', 'module' => 'document-categories', 'label' => 'Manage DocumentCategories'],
            ['name' => 'manage-any-document-categories', 'module' => 'document-categories', 'label' => 'Manage All DocumentCategories'],
            ['name' => 'manage-own-document-categories', 'module' => 'document-categories', 'label' => 'Manage Own DocumentCategories'],
            ['name' => 'create-document-categories', 'module' => 'document-categories', 'label' => 'Create DocumentCategories'],
            ['name' => 'edit-document-categories', 'module' => 'document-categories', 'label' => 'Edit DocumentCategories'],
            ['name' => 'delete-document-categories', 'module' => 'document-categories', 'label' => 'Delete DocumentCategories'],

            // HrmDocument management
            ['name' => 'manage-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Manage HrmDocuments'],
            ['name' => 'manage-any-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Manage All HrmDocuments'],
            ['name' => 'manage-own-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Manage Own HrmDocuments'],
            ['name' => 'manage-hrm-documents-status', 'module' => 'hrm-documents', 'label' => 'Manage  HrmDocuments status'],
            ['name' => 'view-hrm-documents', 'module' => 'hrm-documents', 'label' => 'View HrmDocuments'],
            ['name' => 'download-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Download HrmDocuments'],
            ['name' => 'create-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Create HrmDocuments'],
            ['name' => 'edit-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Edit HrmDocuments'],
            ['name' => 'delete-hrm-documents', 'module' => 'hrm-documents', 'label' => 'Delete HrmDocuments'],

            // Acknowledgment management
            ['name' => 'manage-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Manage Acknowledgments'],
            ['name' => 'manage-any-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Manage All Acknowledgments'],
            ['name' => 'manage-own-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Manage Own Acknowledgments'],
            ['name' => 'manage-acknowledgment-status', 'module' => 'acknowledgments', 'label' => 'Manage Acknowledgment Status'],
            ['name' => 'download-acknowledgment', 'module' => 'acknowledgments', 'label' => 'Download Acknowledgment'],
            ['name' => 'view-acknowledgments', 'module' => 'acknowledgments', 'label' => 'View Acknowledgments'],
            ['name' => 'create-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Create Acknowledgments'],
            ['name' => 'edit-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Edit Acknowledgments'],
            ['name' => 'delete-acknowledgments', 'module' => 'acknowledgments', 'label' => 'Delete Acknowledgments'],

            // AnnouncementCategory management
            ['name' => 'manage-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Manage AnnouncementCategories'],
            ['name' => 'manage-any-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Manage All AnnouncementCategories'],
            ['name' => 'manage-own-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Manage Own AnnouncementCategories'],
            ['name' => 'create-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Create AnnouncementCategories'],
            ['name' => 'edit-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Edit AnnouncementCategories'],
            ['name' => 'delete-announcement-categories', 'module' => 'announcement-categories', 'label' => 'Delete AnnouncementCategories'],

            // Announcement management
            ['name' => 'manage-announcements', 'module' => 'announcements', 'label' => 'Manage Announcements'],
            ['name' => 'manage-any-announcements', 'module' => 'announcements', 'label' => 'Manage All Announcements'],
            ['name' => 'manage-own-announcements', 'module' => 'announcements', 'label' => 'Manage Own Announcements'],
            ['name' => 'manage-announcements-status', 'module' => 'announcements', 'label' => 'Manage Announcement Status'],
            ['name' => 'view-announcements', 'module' => 'announcements', 'label' => 'View Announcements'],
            ['name' => 'create-announcements', 'module' => 'announcements', 'label' => 'Create Announcements'],
            ['name' => 'edit-announcements', 'module' => 'announcements', 'label' => 'Edit Announcements'],
            ['name' => 'delete-announcements', 'module' => 'announcements', 'label' => 'Delete Announcements'],

            // EventType management
            ['name' => 'manage-event-types', 'module' => 'event-types', 'label' => 'Manage EventTypes'],
            ['name' => 'manage-any-event-types', 'module' => 'event-types', 'label' => 'Manage All EventTypes'],
            ['name' => 'manage-own-event-types', 'module' => 'event-types', 'label' => 'Manage Own EventTypes'],
            ['name' => 'create-event-types', 'module' => 'event-types', 'label' => 'Create EventTypes'],
            ['name' => 'edit-event-types', 'module' => 'event-types', 'label' => 'Edit EventTypes'],
            ['name' => 'delete-event-types', 'module' => 'event-types', 'label' => 'Delete EventTypes'],

            // Event management
            ['name' => 'manage-events', 'module' => 'events', 'label' => 'Manage Events'],
            ['name' => 'manage-any-events', 'module' => 'events', 'label' => 'Manage All Events'],
            ['name' => 'manage-own-events', 'module' => 'events', 'label' => 'Manage Own Events'],
            ['name' => 'manage-event-status', 'module' => 'events', 'label' => 'Manage Event Status'],
            ['name' => 'view-event-calendar', 'module' => 'events', 'label' => 'View Event Calendar'],
            ['name' => 'view-events', 'module' => 'events', 'label' => 'View Events'],
            ['name' => 'create-events', 'module' => 'events', 'label' => 'Create Events'],
            ['name' => 'edit-events', 'module' => 'events', 'label' => 'Edit Events'],
            ['name' => 'delete-events', 'module' => 'events', 'label' => 'Delete Events'],

            // LeaveType management
            ['name' => 'manage-leave-types', 'module' => 'leave-types', 'label' => 'Manage LeaveTypes'],
            ['name' => 'manage-any-leave-types', 'module' => 'leave-types', 'label' => 'Manage All LeaveTypes'],
            ['name' => 'manage-own-leave-types', 'module' => 'leave-types', 'label' => 'Manage Own LeaveTypes'],
            ['name' => 'view-leave-types', 'module' => 'leave-types', 'label' => 'View LeaveTypes'],
            ['name' => 'create-leave-types', 'module' => 'leave-types', 'label' => 'Create LeaveTypes'],
            ['name' => 'edit-leave-types', 'module' => 'leave-types', 'label' => 'Edit LeaveTypes'],
            ['name' => 'delete-leave-types', 'module' => 'leave-types', 'label' => 'Delete LeaveTypes'],

            // LeaveApplication management
            ['name' => 'manage-leave-applications', 'module' => 'leave-applications', 'label' => 'Manage LeaveApplications'],
            ['name' => 'manage-any-leave-applications', 'module' => 'leave-applications', 'label' => 'Manage All LeaveApplications'],
            ['name' => 'manage-own-leave-applications', 'module' => 'leave-applications', 'label' => 'Manage Own LeaveApplications'],
            ['name' => 'manage-leave-status', 'module' => 'leave-applications', 'label' => 'Manage Leave Status'],
            ['name' => 'view-leave-applications', 'module' => 'leave-applications', 'label' => 'View LeaveApplications'],
            ['name' => 'create-leave-applications', 'module' => 'leave-applications', 'label' => 'Create LeaveApplications'],
            ['name' => 'edit-leave-applications', 'module' => 'leave-applications', 'label' => 'Edit LeaveApplications'],
            ['name' => 'delete-leave-applications', 'module' => 'leave-applications', 'label' => 'Delete LeaveApplications'],

            // Leave Balace  management
            ['name' => 'manage-leave-balance', 'module' => 'leave-balance', 'label' => 'View Leave Balance'],
            ['name' => 'manage-any-leave-balance', 'module' => 'leave-balance', 'label' => 'Manage All Leave Balance'],
            ['name' => 'manage-own-leave-balance', 'module' => 'leave-balance', 'label' => 'Manage Own Leave Balance'],

            // Shift management
            ['name' => 'manage-shifts', 'module' => 'shifts', 'label' => 'Manage Shifts'],
            ['name' => 'manage-any-shifts', 'module' => 'shifts', 'label' => 'Manage All Shifts'],
            ['name' => 'manage-own-shifts', 'module' => 'shifts', 'label' => 'Manage Own Shifts'],
            ['name' => 'view-shifts', 'module' => 'shifts', 'label' => 'View Shifts'],
            ['name' => 'create-shifts', 'module' => 'shifts', 'label' => 'Create Shifts'],
            ['name' => 'edit-shifts', 'module' => 'shifts', 'label' => 'Edit Shifts'],
            ['name' => 'delete-shifts', 'module' => 'shifts', 'label' => 'Delete Shifts'],

            // Attendance management
            ['name' => 'manage-attendances', 'module' => 'attendances', 'label' => 'Manage Attendances'],
            ['name' => 'manage-any-attendances', 'module' => 'attendances', 'label' => 'Manage All Attendances'],
            ['name' => 'manage-own-attendances', 'module' => 'attendances', 'label' => 'Manage Own Attendances'],
            ['name' => 'view-attendances', 'module' => 'attendances', 'label' => 'View Attendances'],
            ['name' => 'create-attendances', 'module' => 'attendances', 'label' => 'Create Attendances'],
            ['name' => 'edit-attendances', 'module' => 'attendances', 'label' => 'Edit Attendances'],
            ['name' => 'delete-attendances', 'module' => 'attendances', 'label' => 'Delete Attendances'],
            ['name' => 'clock-in', 'module' => 'attendances', 'label' => 'Clock In'],
            ['name' => 'clock-out', 'module' => 'attendances', 'label' => 'Clock Out'],

            // Payslip management
            ['name' => 'manage-payslip', 'module' => 'payslip', 'label' => 'Manage Payslip'],
            ['name' => 'manage-any-payslip', 'module' => 'payslip', 'label' => 'Manage All Payslip'],
            ['name' => 'manage-own-payslip', 'module' => 'payslip', 'label' => 'Manage Own Payslip'],
            ['name' => 'pay-payslip', 'module' => 'payslip', 'label' => 'Pay Payslip'],
            ['name' => 'download-payslip', 'module' => 'payslip', 'label' => 'Download Payslip'],
            ['name' => 'view-payslip', 'module' => 'payslip', 'label' => 'View Payslip'],
            ['name' => 'delete-payslip', 'module' => 'payslip', 'label' => 'Delete Payslip'],

            // Set Salary management
            ['name' => 'manage-set-salary', 'module' => 'set-salary', 'label' => 'Manage Set Salary'],
            ['name' => 'manage-any-set-salary', 'module' => 'set-salary', 'label' => 'Manage All Set Salary'],
            ['name' => 'manage-own-set-salary', 'module' => 'set-salary', 'label' => 'Manage Own Set Salary'],
            ['name' => 'view-set-salary', 'module' => 'set-salary', 'label' => 'View Set Salary'],
            ['name' => 'create-set-salary', 'module' => 'set-salary', 'label' => 'Create Set Salary'],
            ['name' => 'edit-set-salary', 'module' => 'set-salary', 'label' => 'Edit Set Salary'],
            ['name' => 'delete-set-salary', 'module' => 'set-salary', 'label' => 'Delete Set Salary'],

            // AllowanceType management
            ['name' => 'manage-allowance-types', 'module' => 'allowance-types', 'label' => 'Manage AllowanceTypes'],
            ['name' => 'manage-any-allowance-types', 'module' => 'allowance-types', 'label' => 'Manage All AllowanceTypes'],
            ['name' => 'manage-own-allowance-types', 'module' => 'allowance-types', 'label' => 'Manage Own AllowanceTypes'],
            ['name' => 'create-allowance-types', 'module' => 'allowance-types', 'label' => 'Create AllowanceTypes'],
            ['name' => 'edit-allowance-types', 'module' => 'allowance-types', 'label' => 'Edit AllowanceTypes'],
            ['name' => 'delete-allowance-types', 'module' => 'allowance-types', 'label' => 'Delete AllowanceTypes'],

            // DeductionType management
            ['name' => 'manage-deduction-types', 'module' => 'deduction-types', 'label' => 'Manage DeductionTypes'],
            ['name' => 'manage-any-deduction-types', 'module' => 'deduction-types', 'label' => 'Manage All DeductionTypes'],
            ['name' => 'manage-own-deduction-types', 'module' => 'deduction-types', 'label' => 'Manage Own DeductionTypes'],
            ['name' => 'create-deduction-types', 'module' => 'deduction-types', 'label' => 'Create DeductionTypes'],
            ['name' => 'edit-deduction-types', 'module' => 'deduction-types', 'label' => 'Edit DeductionTypes'],
            ['name' => 'delete-deduction-types', 'module' => 'deduction-types', 'label' => 'Delete DeductionTypes'],

            // LoanType management
            ['name' => 'manage-loan-types', 'module' => 'loan-types', 'label' => 'Manage LoanTypes'],
            ['name' => 'manage-any-loan-types', 'module' => 'loan-types', 'label' => 'Manage All LoanTypes'],
            ['name' => 'manage-own-loan-types', 'module' => 'loan-types', 'label' => 'Manage Own LoanTypes'],
            ['name' => 'create-loan-types', 'module' => 'loan-types', 'label' => 'Create LoanTypes'],
            ['name' => 'edit-loan-types', 'module' => 'loan-types', 'label' => 'Edit LoanTypes'],
            ['name' => 'delete-loan-types', 'module' => 'loan-types', 'label' => 'Delete LoanTypes'],

            // Allowance management
            ['name' => 'manage-allowances', 'module' => 'allowances', 'label' => 'Manage Allowances'],
            ['name' => 'manage-any-allowances', 'module' => 'allowances', 'label' => 'Manage All Allowances'],
            ['name' => 'manage-own-allowances', 'module' => 'allowances', 'label' => 'Manage Own Allowances'],
            ['name' => 'create-allowances', 'module' => 'allowances', 'label' => 'Create Allowances'],
            ['name' => 'edit-allowances', 'module' => 'allowances', 'label' => 'Edit Allowances'],
            ['name' => 'delete-allowances', 'module' => 'allowances', 'label' => 'Delete Allowances'],

            // Deduction management
            ['name' => 'manage-deductions', 'module' => 'deductions', 'label' => 'Manage Deductions'],
            ['name' => 'manage-any-deductions', 'module' => 'deductions', 'label' => 'Manage All Deductions'],
            ['name' => 'manage-own-deductions', 'module' => 'deductions', 'label' => 'Manage Own Deductions'],
            ['name' => 'create-deductions', 'module' => 'deductions', 'label' => 'Create Deductions'],
            ['name' => 'edit-deductions', 'module' => 'deductions', 'label' => 'Edit Deductions'],
            ['name' => 'delete-deductions', 'module' => 'deductions', 'label' => 'Delete Deductions'],

            // Loan management
            ['name' => 'manage-loans', 'module' => 'loans', 'label' => 'Manage Loans'],
            ['name' => 'manage-any-loans', 'module' => 'loans', 'label' => 'Manage All Loans'],
            ['name' => 'manage-own-loans', 'module' => 'loans', 'label' => 'Manage Own Loans'],
            ['name' => 'view-loans', 'module' => 'loans', 'label' => 'View Loans'],
            ['name' => 'create-loans', 'module' => 'loans', 'label' => 'Create Loans'],
            ['name' => 'edit-loans', 'module' => 'loans', 'label' => 'Edit Loans'],
            ['name' => 'delete-loans', 'module' => 'loans', 'label' => 'Delete Loans'],

            // Overtime management
            ['name' => 'manage-overtimes', 'module' => 'overtimes', 'label' => 'Manage Overtimes'],
            ['name' => 'manage-any-overtimes', 'module' => 'overtimes', 'label' => 'Manage All Overtimes'],
            ['name' => 'manage-own-overtimes', 'module' => 'overtimes', 'label' => 'Manage Own Overtimes'],
            ['name' => 'view-overtimes', 'module' => 'overtimes', 'label' => 'View Overtimes'],
            ['name' => 'create-overtimes', 'module' => 'overtimes', 'label' => 'Create Overtimes'],
            ['name' => 'edit-overtimes', 'module' => 'overtimes', 'label' => 'Edit Overtimes'],
            ['name' => 'delete-overtimes', 'module' => 'overtimes', 'label' => 'Delete Overtimes'],

            // Payroll management
            ['name' => 'manage-payrolls', 'module' => 'payrolls', 'label' => 'Manage Payrolls'],
            ['name' => 'manage-any-payrolls', 'module' => 'payrolls', 'label' => 'Manage All Payrolls'],
            ['name' => 'manage-own-payrolls', 'module' => 'payrolls', 'label' => 'Manage Own Payrolls'],

            ['name' => 'view-payrolls', 'module' => 'payrolls', 'label' => 'View Payrolls'],
            ['name' => 'view-any-payrolls', 'module' => 'payrolls', 'label' => 'View All Payrolls'],
            ['name' => 'view-own-payrolls', 'module' => 'payrolls', 'label' => 'View Own Payrolls'],

            ['name' => 'run-payrolls', 'module' => 'payrolls', 'label' => 'Run Payrolls'],
            ['name' => 'create-payrolls', 'module' => 'payrolls', 'label' => 'Create Payrolls'],
            ['name' => 'edit-payrolls', 'module' => 'payrolls', 'label' => 'Edit Payrolls'],
            ['name' => 'delete-payrolls', 'module' => 'payrolls', 'label' => 'Delete Payrolls'],

            // Working Days management
            ['name' => 'manage-working-days', 'module' => 'working-days', 'label' => 'Manage Working Days'],
            ['name' => 'edit-working-days', 'module' => 'working-days', 'label' => 'Edit Working Days'],



            // IpRestrict management
            ['name' => 'manage-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Manage IpRestricts'],
            ['name' => 'manage-any-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Manage All IpRestricts'],
            ['name' => 'manage-own-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Manage Own IpRestricts'],
            ['name' => 'create-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Create IpRestricts'],
            ['name' => 'edit-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Edit IpRestricts'],
            ['name' => 'delete-ip-restricts', 'module' => 'ip-restricts', 'label' => 'Delete IpRestricts'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Hrm',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}
