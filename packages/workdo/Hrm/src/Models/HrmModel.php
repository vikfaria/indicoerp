<?php

namespace Workdo\Hrm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrmModel extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    protected $casts = [
        //
    ];

    public static function defaultdata($company_id = null)
    {
        $hrRolePermissions = [
            // HRM
            'manage-hrm-dashboard',
            'manage-hrm',

            // Branches
            'manage-branches',
            'manage-any-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',

            // Departments
            'manage-departments',
            'manage-any-departments',
            'create-departments',
            'edit-departments',
            'delete-departments',

            // Designations
            'manage-designations',
            'manage-any-designations',
            'create-designations',
            'edit-designations',
            'delete-designations',

            // Employee Document Types
            'manage-employee-document-types',
            'manage-any-employee-document-types',
            'create-employee-document-types',
            'edit-employee-document-types',
            'delete-employee-document-types',

            // Employees
            'manage-employees',
            'manage-any-employees',
            'view-employees',
            'create-employees',
            'edit-employees',
            'delete-employees',

            // Award Types
            'manage-award-types',
            'manage-any-award-types',
            'create-award-types',
            'edit-award-types',
            'delete-award-types',

            // Awards
            'manage-awards',
            'manage-any-awards',
            'create-awards',
            'edit-awards',
            'delete-awards',

            // Promotions
            'manage-promotions',
            'manage-any-promotions',
            'manage-promotions-status',
            'view-promotions',
            'create-promotions',
            'edit-promotions',
            'delete-promotions',

            // Resignations
            'manage-resignations',
            'manage-any-resignations',
            'manage-resignation-status',
            'view-resignations',
            'create-resignations',
            'edit-resignations',
            'delete-resignations',

            // Termination Types
            'manage-termination-types',
            'manage-any-termination-types',
            'create-termination-types',
            'edit-termination-types',
            'delete-termination-types',

            // Terminations
            'manage-terminations',
            'manage-any-terminations',
            'manage-termination-status',
            'view-terminations',
            'create-terminations',
            'edit-terminations',
            'delete-terminations',

            // Warning Types
            'manage-warning-types',
            'manage-any-warning-types',
            'create-warning-types',
            'edit-warning-types',
            'delete-warning-types',

            // Warnings
            'manage-warnings',
            'manage-any-warnings',
            'manage-warning-response',
            'view-warnings',
            'create-warnings',
            'edit-warnings',
            'delete-warnings',

            // Complaint Types
            'manage-complaint-types',
            'manage-any-complaint-types',
            'create-complaint-types',
            'edit-complaint-types',
            'delete-complaint-types',

            // Complaints
            'manage-complaints',
            'manage-any-complaints',
            'manage-complaint-status',
            'view-complaints',
            'create-complaints',
            'edit-complaints',
            'delete-complaints',

            // Employee Transfers
            'manage-employee-transfers',
            'manage-any-employee-transfers',
            'manage-employee-transfers-status',
            'view-employee-transfers',
            'create-employee-transfers',
            'edit-employee-transfers',
            'delete-employee-transfers',

            // Holiday Types
            'manage-holiday-types',
            'manage-any-holiday-types',
            'create-holiday-types',
            'edit-holiday-types',
            'delete-holiday-types',

            // Holidays
            'manage-holidays',
            'manage-any-holidays',
            'view-holidays',
            'create-holidays',
            'edit-holidays',
            'delete-holidays',

            // Document Categories
            'manage-document-categories',
            'manage-any-document-categories',
            'create-document-categories',
            'edit-document-categories',
            'delete-document-categories',

            // HRM Documents
            'manage-hrm-documents',
            'manage-any-hrm-documents',
            'manage-hrm-documents-status',
            'view-hrm-documents',
            'download-hrm-documents',
            'create-hrm-documents',
            'edit-hrm-documents',
            'delete-hrm-documents',

            // Acknowledgments
            'manage-acknowledgments',
            'manage-any-acknowledgments',
            'manage-acknowledgment-status',
            'download-acknowledgment',
            'view-acknowledgments',
            'create-acknowledgments',
            'edit-acknowledgments',
            'delete-acknowledgments',

            // AnnouncementCategory management
            'manage-announcement-categories',
            'manage-any-announcement-categories',
            'create-announcement-categories',
            'edit-announcement-categories',
            'delete-announcement-categories',

            // Announcement management
            'manage-announcements',
            'manage-any-announcements',
            'manage-announcements-status',
            'view-announcements',
            'create-announcements',
            'edit-announcements',
            'delete-announcements',

            // EventType management
            'manage-event-types',
            'manage-any-event-types',
            'create-event-types',
            'edit-event-types',
            'delete-event-types',

            // Event management
            'manage-events',
            'manage-any-events',
            'manage-event-status',
            'view-event-calendar',
            'view-events',
            'create-events',
            'edit-events',
            'delete-events',

            // LeaveType management
            'manage-leave-types',
            'manage-any-leave-types',
            'view-leave-types',
            'create-leave-types',
            'edit-leave-types',
            'delete-leave-types',

            // LeaveApplication management
            'manage-leave-applications',
            'manage-any-leave-applications',
            'manage-leave-status',
            'view-leave-applications',
            'create-leave-applications',
            'edit-leave-applications',
            'delete-leave-applications',

            // Leave Balance management
            'manage-leave-balance',
            'manage-any-leave-balance',

            // Shift management
            'manage-shifts',
            'manage-any-shifts',
            'view-shifts',
            'create-shifts',
            'edit-shifts',
            'delete-shifts',

            // Attendance management
            'manage-attendances',
            'manage-any-attendances',
            'view-attendances',
            'create-attendances',
            'edit-attendances',
            'delete-attendances',
            'clock-in',
            'clock-out',

            // Payslip management
            'manage-payslip',
            'manage-any-payslip',
            'pay-payslip',
            'download-payslip',
            'view-payslip',
            'delete-payslip',

            // Set Salary management
            'manage-set-salary',
            'manage-any-set-salary',
            'view-set-salary',
            'create-set-salary',
            'edit-set-salary',
            'delete-set-salary',

            // AllowanceType management
            'manage-allowance-types',
            'manage-any-allowance-types',
            'create-allowance-types',
            'edit-allowance-types',
            'delete-allowance-types',

            // DeductionType management
            'manage-deduction-types',
            'manage-any-deduction-types',
            'create-deduction-types',
            'edit-deduction-types',
            'delete-deduction-types',

            // LoanType management
            'manage-loan-types',
            'manage-any-loan-types',
            'create-loan-types',
            'edit-loan-types',
            'delete-loan-types',

            // Allowance management
            'manage-allowances',
            'manage-any-allowances',
            'create-allowances',
            'edit-allowances',
            'delete-allowances',

            // Deduction management
            'manage-deductions',
            'manage-any-deductions',
            'create-deductions',
            'edit-deductions',
            'delete-deductions',

            // Loan management
            'manage-loans',
            'manage-any-loans',
            'view-loans',
            'create-loans',
            'edit-loans',
            'delete-loans',

            // Overtime management
            'manage-overtimes',
            'manage-any-overtimes',
            'view-overtimes',
            'create-overtimes',
            'edit-overtimes',
            'delete-overtimes',

            // Payroll management
            'manage-payrolls',
            'manage-any-payrolls',
            'view-payrolls',
            'view-any-payrolls',
            'view-own-payrolls',
            'run-payrolls',
            'create-payrolls',
            'edit-payrolls',
            'delete-payrolls',

            // Working Days management
            'manage-working-days',
            'edit-working-days',

        ];

        $hrRole = Role::where('name', 'hr')->where('created_by', $company_id)->where('guard_name', 'web')->first();
        if (empty($hrRole)) {
            $hrRole = new Role();
            $hrRole->name = 'hr';
            $hrRole->guard_name = 'web';
            $hrRole->label = 'Hr';
            $hrRole->editable = 0;
            $hrRole->created_by = $company_id;
            $hrRole->save();

            foreach ($hrRolePermissions as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission) && !$hrRole->hasPermissionTo($permission_v)) {
                    $hrRole->givePermissionTo($permission);
                }
            }
        }

        // Set default working days (Monday to Friday)
        setSetting('working_days', json_encode([1, 2, 3, 4, 5]), $company_id);
    }


    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {

        $staff_permission = [
            // HRM
            'manage-hrm-dashboard',
            'manage-hrm',

            // Employees
            'manage-employees',
            'manage-own-employees',
            'view-employees',

            // Awards
            'manage-awards',
            'manage-own-awards',

            // Promotions
            'manage-promotions',
            'manage-own-promotions',
            'view-promotions',

            // Resignations
            'manage-resignations',
            'manage-own-resignations',
            'view-resignations',
            'create-resignations',
            'edit-resignations',

            // Terminations
            'manage-terminations',
            'manage-own-terminations',
            'view-terminations',

            // Warnings
            'manage-warnings',
            'manage-own-warnings',
            'manage-warning-response',
            'view-warnings',


            // Complaints
            'manage-complaints',
            'manage-own-complaints',
            'view-complaints',
            'create-complaints',

            // Employee Transfers
            'manage-employee-transfers',
            'manage-own-employee-transfers',
            'view-employee-transfers',


            // Holidays
            'manage-holidays',
            'manage-any-holidays',
            'view-holidays',

            // HRM Documents
            'manage-hrm-documents',
            'manage-own-hrm-documents',
            'view-hrm-documents',
            'download-hrm-documents',

            // Acknowledgments
            'manage-acknowledgments',
            'manage-own-acknowledgments',
            'download-acknowledgment',
            'view-acknowledgments',

            // Announcement management
            'manage-announcements',
            'manage-any-announcements',
            'view-announcements',

            // Event management
            'manage-events',
            'manage-any-events',
            'view-event-calendar',
            'view-events',

            // LeaveApplication management
            'manage-leave-applications',
            'manage-own-leave-applications',
            'create-leave-applications',
            'view-leave-applications',

            // Leave Balance management
            'manage-leave-balance',
            'manage-own-leave-balance',


            // Attendance management
            'manage-attendances',
            'manage-own-attendances',
            'view-attendances',
            'create-attendances',
            'edit-attendances',
            'clock-in',
            'clock-out',

            // Payslip management
            'manage-payslip',
            'manage-own-payslip',
            'download-payslip',
            'view-payslip',

            // Set Salary management
            'manage-set-salary',
            'manage-own-set-salary',
            'view-set-salary',

            // Allowance management
            'manage-allowances',
            'manage-own-allowances',

            // Deduction management
            'manage-deductions',
            'manage-own-deductions',

            // Loan management
            'manage-loans',
            'manage-own-loans',
            'view-loans',

            // Overtime management
            'manage-overtimes',
            'manage-own-overtimes',
            'view-overtimes',

            // Payroll management
            'manage-payrolls',
            'manage-any-payrolls',
            'view-payrolls',
            'view-own-payrolls',
        ];


        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            foreach ($staff_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }
    }
}
