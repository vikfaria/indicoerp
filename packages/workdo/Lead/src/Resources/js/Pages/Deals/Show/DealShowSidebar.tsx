import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from '@/lib/utils';
import { Info, CheckSquare, Users, Package, Database, FileText, File, Phone, Activity } from "lucide-react";

interface SidebarItem {
    key: string;
    label: string;
    icon: React.ComponentType<{ className?: string }>;
}

interface DealShowSidebarProps {
    activeItem?: string;
    onSectionChange?: (section: string) => void;
}

export default function DealShowSidebar({ activeItem, onSectionChange }: DealShowSidebarProps) {
    const { t } = useTranslation();

    const sidebarItems: SidebarItem[] = [
        {
            key: 'general',
            label: t('General'),
            icon: Info,
        },
        {
            key: 'tasks',
            label: t('Tasks'),
            icon: CheckSquare,
        },
        {
            key: 'users',
            label: t('Users'),
            icon: Users,
        },
        {
            key: 'products',
            label: t('Products'),
            icon: Package,
        },
        {
            key: 'sources',
            label: t('Sources'),
            icon: Database,
        },


        {
            key: 'files',
            label: t('Files'),
            icon: File,
        },
        {
            key: 'calls',
            label: t('Calls'),
            icon: Phone,
        },
        {
            key: 'clients',
            label: t('Clients'),
            icon: Users,
        },
        {
            key: 'activity',
            label: t('Activity'),
            icon: Activity,
        },
    ];

    return (
        <div className="sticky top-4">
            <ScrollArea className="h-[calc(100vh-8rem)]">
                <div className="pr-4 space-y-1">
                    {sidebarItems.map((item) => {
                        const Icon = item.icon;
                        const isActive = activeItem === item.key;

                        return (
                            <Button
                                key={item.key}
                                variant="ghost"
                                className={cn('w-full justify-start', {
                                    'bg-muted font-medium': isActive,
                                })}
                                onClick={() => {
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