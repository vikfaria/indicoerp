import { useState } from 'react';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from '@/components/ui/button';
import { ScrollArea } from "@/components/ui/scroll-area";
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import { Info, DollarSign } from "lucide-react";
import { Deal } from './types';

interface ViewProps {
    deal: Deal;
}

export default function View({ deal }: ViewProps) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState('general');

    const tabs = [
        {
            key: 'general',
            label: t('General'),
            icon: Info,
        },
    ];

    const renderGeneral = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Deal Name')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.name || '-'}</p>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Price')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.price ? `$${deal.price}` : '-'}</p>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Phone')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.phone || '-'}</p>
                    </div>
                </div>
                <div className="space-y-4">
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Pipeline')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.pipeline?.name || '-'}</p>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Stage')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.stage?.name || '-'}</p>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-gray-500">{t('Status')}</h3>
                        <p className="mt-1 text-sm text-gray-900">{deal.status || '-'}</p>
                    </div>
                </div>
            </div>
            {deal.notes && (
                <div>
                    <h3 className="text-sm font-medium text-gray-500">{t('Notes')}</h3>
                    <div className="mt-1 text-sm text-gray-900" dangerouslySetInnerHTML={{ __html: deal.notes }} />
                </div>
            )}
        </div>
    );

    return (
        <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <DollarSign className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Deal Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{deal.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="flex gap-6 h-full">
                {/* Sidebar */}
                <div className="w-64 flex-shrink-0">
                    <ScrollArea className="h-[calc(100vh-12rem)]">
                        <div className="space-y-1">
                            {tabs.map((tab) => {
                                const Icon = tab.icon;
                                const isActive = activeTab === tab.key;

                                return (
                                    <Button
                                        key={tab.key}
                                        variant="ghost"
                                        className={cn('w-full justify-start', {
                                            'bg-muted font-medium': isActive,
                                        })}
                                        onClick={() => setActiveTab(tab.key)}
                                    >
                                        <Icon className="h-4 w-4 mr-2" />
                                        {tab.label}
                                    </Button>
                                );
                            })}
                        </div>
                    </ScrollArea>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-hidden">
                    <ScrollArea className="h-[calc(100vh-12rem)]">
                        <div className="pr-4">
                            {activeTab === 'general' && renderGeneral()}
                        </div>
                    </ScrollArea>
                </div>
            </div>
        </DialogContent>
    );
}