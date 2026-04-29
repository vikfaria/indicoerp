import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from '@/lib/utils';
import { Folder, BookOpen, HelpCircle, Library, Palette, FileEdit, Type, MousePointer, Link, Info, MapPin } from "lucide-react";

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
            key: 'categories',
            label: t('Categories'),
            icon: Folder,
            route: 'ticket-category.index',
            permission: 'manage-ticket-categories'
        },
        {
            key: 'support-categories',
            label: t('Support Category'),
            icon: HelpCircle,
            route: 'support-category.index',
            permission: 'manage-support-categories'
        },
        {
            key: 'knowledge-categories',
            label: t('KnowledgeBase Category'),
            icon: Library,
            route: 'knowledge-category.index',
            permission: 'manage-knowledge-base'
        },
        {
            key: 'brand-settings',
            label: t('Brand Settings'),
            icon: Palette,
            route: 'support-ticket.settings.brand',
            permission: 'manage-support-settings'
        },

        {
            key: 'custom-pages',
            label: t('Custom Pages'),
            icon: FileEdit,
            route: 'support-ticket.custom-pages.index',
            permission: 'manage-support-settings'
        },
        {
            key: 'title-sections',
            label: t('Title Sections'),
            icon: Type,
            route: 'support-ticket.title-sections.index',
            permission: 'manage-support-settings'
        },
        {
            key: 'cta-sections',
            label: t('CTA Sections'),
            icon: MousePointer,
            route: 'support-ticket.cta-sections.index',
            permission: 'manage-support-settings'
        },
        {
            key: 'quick-links',
            label: t('Quick Links'),
            icon: Link,
            route: 'support-ticket.quick-links.index',
            permission: 'manage-support-settings'
        },
        {
            key: 'support-information',
            label: t('Support Information'),
            icon: Info,
            route: 'support-ticket.support-information.index',
            permission: 'manage-support-settings'
        },
        {
            key: 'contact-information',
            label: t('Contact Information'),
            icon: MapPin,
            route: 'support-ticket.contact-information.index',
            permission: 'manage-support-settings'
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