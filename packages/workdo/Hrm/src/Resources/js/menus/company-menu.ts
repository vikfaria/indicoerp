import {  Package, Building, Building2, Users, FileText, Tag, UserX, AlertOctagon, MessageSquareWarning, ArrowRightLeft, Calendar, FileCheck, Megaphone, Clock , Calculator, UserCheck, UserCog } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const hrmCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('HRM Dashboard'),
        href: route('hrm.index'),
        permission: 'manage-hrm-dashboard',
        parent: 'dashboard',
        order: 30,
    },
    {
        title: t('HRM'),
        icon: UserCog,
        permission: 'manage-hrm',
        order: 450,
        children: [
            {
                title: t('Employees'),
                href: route('hrm.employees.index'),
                permission: 'manage-employees',
            },
            {
                title: t('Payslip'),
                permission: 'manage-payrolls',
                children: [
                    {
                        title: t('Set Salary'),
                        href: route('hrm.set-salary.index'),
                        permission: 'manage-set-salary',
                    },
                    {
                        title: t('Payroll'),
                        href: route('hrm.payrolls.index'),
                        permission: 'manage-payrolls',
                    },
                ],
            },
            {
                title: t('Attedance'),
                permission: 'manage-attendances',
                children: [
                    {
                        title: t('Shifts'),
                        href: route('hrm.shifts.index'),
                        permission: 'manage-shifts',
                    },
                    {
                        title: t('Attendances'),
                        href: route('hrm.attendances.index'),
                        permission: 'manage-attendances',
                    },
                ],
            },
            {
                title: t('Leave Management'),
                permission: 'manage-leave-applications',
                children: [
                    {
                        title: t('Leave Types'),
                        href: route('hrm.leave-types.index'),
                        permission: 'manage-leave-types',
                    },
                    {
                        title: t('Leave Applications'),
                        href: route('hrm.leave-applications.index'),
                        permission: 'manage-leave-applications',
                    },
                    {
                        title: t('Leave Balance'),
                        href: route('hrm.leave-balance.index'),
                        permission: 'manage-leave-balance',
                    },
                ],
            },
            {
                title: t('Holidays'),
                href: route('hrm.holidays.index'),
                permission: 'manage-holidays',
            },
            {
                title: t('Awards'),
                href: route('hrm.awards.index'),
                permission: 'manage-awards',
            },
            {
                title: t('Promotions'),
                href: route('hrm.promotions.index'),
                permission: 'manage-promotions',
            },
            {
                title: t('Resignations'),
                href: route('hrm.resignations.index'),
                permission: 'manage-resignations',
            },
            {
                title: t('Terminations'),
                href: route('hrm.terminations.index'),
                permission: 'manage-terminations',
            },
            {
                title: t('Warnings'),
                href: route('hrm.warnings.index'),
                permission: 'manage-warnings',
            },
            {
                title: t('Complaints'),
                href: route('hrm.complaints.index'),
                permission: 'manage-complaints',
            },
            {
                title: t('Transfers'),
                href: route('hrm.employee-transfers.index'),
                permission: 'manage-employee-transfers',
            },
            {
                title: t('Documents'),
                href: route('hrm.documents.index'),
                permission: 'manage-hrm-documents',
            },
            {
                title: t('Acknowledgments'),
                href: route('hrm.acknowledgments.index'),
                permission: 'manage-acknowledgments',
            },
            {
                title: t('Announcements'),
                href: route('hrm.announcements.index'),
                permission: 'manage-announcements',
            },
            {
                title: t('Events'),
                href: route('hrm.events.index'),
                permission: 'manage-events',
            },
            {
                title: t('System Setup'),
                href: route('hrm.branches.index'),
                permission: 'manage-hrm',
                activePaths: [
                    route('hrm.departments.index'),
                    route('hrm.designations.index'),
                    route('hrm.employee-document-types.index'),
                    route('hrm.award-types.index'),
                    route('hrm.termination-types.index'),
                    route('hrm.warning-types.index'),
                    route('hrm.complaint-types.index'),
                    route('hrm.holiday-types.index'),
                    route('hrm.document-categories.index'),
                    route('hrm.announcement-categories.index'),
                    route('hrm.event-types.index'),
                    route('hrm.allowance-types.index'),
                    route('hrm.deduction-types.index'),
                    route('hrm.loan-types.index'),
                    route('hrm.working-days.index'),
                    route('hrm.ip-restricts.index')
                ],
            },

        ],
    },
];