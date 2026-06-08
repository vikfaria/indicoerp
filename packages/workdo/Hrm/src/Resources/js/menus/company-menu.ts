import {  Package, Building, Building2, Users, FileText, Tag, UserX, AlertOctagon, MessageSquareWarning, ArrowRightLeft, Calendar, FileCheck, Megaphone, Clock , Calculator, UserCheck, UserCog } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const hrmCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Painel RH'),
        href: route('hrm.index'),
        permission: 'manage-hrm-dashboard',
        parent: 'dashboard',
        order: 30,
    },
    {
        title: t('Recursos Humanos'),
        icon: UserCog,
        permission: 'manage-hrm',
        name: 'hrm',
        order: 450,
        children: [
            {
                title: t('Colaboradores'),
                href: route('hrm.employees.index'),
                permission: 'manage-employees',
            },
            {
                title: t('Folha de pagamento'),
                permission: 'manage-payrolls',
                children: [
                    {
                        title: t('Definir salário'),
                        href: route('hrm.set-salary.index'),
                        permission: 'manage-set-salary',
                    },
                    {
                        title: t('Processamento salarial'),
                        href: route('hrm.payrolls.index'),
                        permission: 'manage-payrolls',
                    },
                ],
            },
            {
                title: t('Assiduidade'),
                permission: 'manage-attendances',
                children: [
                    {
                        title: t('Turnos'),
                        href: route('hrm.shifts.index'),
                        permission: 'manage-shifts',
                    },
                    {
                        title: t('Registos de assiduidade'),
                        href: route('hrm.attendances.index'),
                        permission: 'manage-attendances',
                    },
                ],
            },
            {
                title: t('Férias e ausências'),
                permission: 'manage-leave-applications',
                children: [
                    {
                        title: t('Tipos de férias'),
                        href: route('hrm.leave-types.index'),
                        permission: 'manage-leave-types',
                    },
                    {
                        title: t('Pedidos de férias'),
                        href: route('hrm.leave-applications.index'),
                        permission: 'manage-leave-applications',
                    },
                    {
                        title: t('Saldo de férias'),
                        href: route('hrm.leave-balance.index'),
                        permission: 'manage-leave-balance',
                    },
                ],
            },
            {
                title: t('Feriados'),
                href: route('hrm.holidays.index'),
                permission: 'manage-holidays',
            },
            {
                title: t('Prémios'),
                href: route('hrm.awards.index'),
                permission: 'manage-awards',
            },
            {
                title: t('Promoções'),
                href: route('hrm.promotions.index'),
                permission: 'manage-promotions',
            },
            {
                title: t('Rescisões'),
                href: route('hrm.resignations.index'),
                permission: 'manage-resignations',
            },
            {
                title: t('Desligamentos'),
                href: route('hrm.terminations.index'),
                permission: 'manage-terminations',
            },
            {
                title: t('Advertências'),
                href: route('hrm.warnings.index'),
                permission: 'manage-warnings',
            },
            {
                title: t('Reclamações'),
                href: route('hrm.complaints.index'),
                permission: 'manage-complaints',
            },
            {
                title: t('Transferências'),
                href: route('hrm.employee-transfers.index'),
                permission: 'manage-employee-transfers',
            },
            {
                title: t('Documentos'),
                href: route('hrm.documents.index'),
                permission: 'manage-hrm-documents',
            },
            {
                title: t('Ciência de documentos'),
                href: route('hrm.acknowledgments.index'),
                permission: 'manage-acknowledgments',
            },
            {
                title: t('Anúncios'),
                href: route('hrm.announcements.index'),
                permission: 'manage-announcements',
            },
            {
                title: t('Eventos'),
                href: route('hrm.events.index'),
                permission: 'manage-events',
            },
            {
                title: t('Configuração'),
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
