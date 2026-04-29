import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from '@/lib/utils';
import {       Building, Building2, Users, FileText, Settings, AlertTriangle,ShieldAlert,AlertOctagon , Calendar , Tag , DollarSign , Minus , CreditCard, Clock , Shield, Calculator } from "lucide-react";

interface SidebarItem {
    key: string;
    label: string;
    icon: React.ComponentType<{ className?: string }>;
    route: string;
    permission: string;
}

interface SystemSetupSidebarProps {
    activeItem?: string;
    onSectionChange?: (section: string) => void;
}

export default function SystemSetupSidebar({ activeItem, onSectionChange }: SystemSetupSidebarProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;
    const currentRoute = route().current();

    const sidebarItems: SidebarItem[] = [
        {
            key: 'branches',
            label: t('Branches'),
            icon: Building,
            route: 'hrm.branches.index',
            permission: 'manage-branches'
        },
        {
            key: 'departments',
            label: t('Departments'),
            icon: Building2,
            route: 'hrm.departments.index',
            permission: 'manage-departments'
        },
        {
            key: 'designations',
            label: t('Designations'),
            icon: Users,
            route: 'hrm.designations.index',
            permission: 'manage-designations'
        },
        {
            key: 'employee-document-types',
            label: t('Document Types'),
            icon: FileText,
            route: 'hrm.employee-document-types.index',
            permission: 'manage-employee-document-types'
        },
        {
            key: 'award-types',
            label: t('Award Types'),
            icon: Settings,
            route: 'hrm.award-types.index',
            permission: 'manage-award-types'
        },
        {
            key: 'termination-types',
            label: t('Termination Types'),
            icon: AlertTriangle,
            route: 'hrm.termination-types.index',
            permission: 'manage-termination-types'
        },
        {
            key: 'warning-types',
            label: t('Warning Types'),
            icon: ShieldAlert,
            route: 'hrm.warning-types.index',
            permission: 'manage-warning-types'
        },
        {
            key: 'complaint-types',
            label: t('Complaint Types'),
            icon: AlertOctagon,
            route: 'hrm.complaint-types.index',
            permission: 'manage-complaint-types'
        },
        {
            key: 'holiday-types',
            label: t('Holiday Types'),
            icon: Calendar,
            route: 'hrm.holiday-types.index',
            permission: 'manage-holiday-types'
        },
        {
            key: 'document-categories',
            label: t('Document Categories'),
            icon: FileText,
            route: 'hrm.document-categories.index',
            permission: 'manage-document-categories'
        },
        {
            key: 'announcement-categories',
            label: t('Announcement Categories'),
            icon: Tag,
            route: 'hrm.announcement-categories.index',
            permission: 'manage-announcement-categories'
        },
        {
            key: 'event-types',
            label: t('Event Types'),
            icon: Calendar,
            route: 'hrm.event-types.index',
            permission: 'manage-event-types'
        },
        {
            key: 'allowance-types',
            label: t('Allowance Types'),
            icon: DollarSign,
            route: 'hrm.allowance-types.index',
            permission: 'manage-allowance-types'
        },
        {
            key: 'deduction-types',
            label: t('Deduction Types'),
            icon: Minus,
            route: 'hrm.deduction-types.index',
            permission: 'manage-deduction-types'
        },
        {
            key: 'loan-types',
            label: t('Loan Types'),
            icon: CreditCard,
            route: 'hrm.loan-types.index',
            permission: 'manage-loan-types'
        },
        {
            key: 'working-days',
            label: t('Working Days'),
            icon: Clock,
            route: 'hrm.working-days.index',
            permission: 'manage-working-days'
        },
        {
            key: 'mozambique-payroll-compliance',
            label: t('Mozambique Payroll Compliance'),
            icon: Calculator,
            route: 'hrm.mozambique-payroll-compliance.index',
            permission: 'manage-payrolls'
        },
        {
            key: 'ip-restricts',
            label: t('Ip Restricts'),
            icon: Shield,
            route: 'hrm.ip-restricts.index',
            permission: 'manage-ip-restricts'
        },
    ];

    const filteredItems = sidebarItems.filter(item =>
        auth.user?.permissions?.includes(item.permission)
    );

    return (
        <div className="sticky top-4">
            <ScrollArea className="h-[calc(100vh-8rem)]">
                <div className="pr-4 space-y-1">
                    {filteredItems.map((item) => {
                        const Icon = item.icon;
                        const isActive = activeItem === item.key || currentRoute === item.route;

                        return (
                            <Button
                                key={item.key}
                                variant="ghost"
                                className={cn('w-full justify-start', {
                                    'bg-muted font-medium': isActive,
                                })}
                                onClick={() => {
                                    router.get(route(item.route));
                                    onSectionChange?.(item.key);
                                }}
                            >
                                <Icon className="h-4 w-4 mr-2" />
                                {item.label}
                            </Button>
                        );
                    })}
                </div>
            </ScrollArea>
        </div>
    );
}
