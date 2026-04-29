import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { usePageButtons } from '@/hooks/usePageButtons';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Plus } from 'lucide-react';

import AuthenticatedLayout from '@/layouts/authenticated-layout';
import LeadSidebar from './LeadSidebar';
import General from './General';

import Activity from './Activity';
import Tasks from './Tasks';
import Users from './Users';
import Products from './Products';
import Sources from './Sources';

import Calls from './Calls';
import Files from './Files';
import ConvertToDeal from './ConvertToDeal';
import { Lead } from '../types';

interface ShowLeadProps {
    lead: Lead;
    deal?: {
        id: number;
        is_active: boolean;
    };
}

export default function Show() {
    const { lead, deal } = usePage<ShowLeadProps>().props;
    const { t } = useTranslation();
    const [activeSection, setActiveSection] = useState('general');
    const videoHubButtons = usePageButtons('leadShowButtons', { lead });
    const spreadsheetButtons = usePageButtons('spreadsheetBtn', { module: 'Lead', sub_module: lead.id });
    const businessProcessMappingButtons = usePageButtons('businessProcessMappingBtn', { module: 'Lead', submodule: 'Lead', id: lead.id });

    const renderSectionHeader = () => {
        const headers = {
            general: t('General'),
            tasks: t('Tasks'),
            users: t('Users'),
            products: t('Products'),
            sources: t('Sources'),
            files: t('Files'),
            calls: t('Calls'),
            activity: t('Activity')
        };

        const showAddButton = ['tasks', 'users', 'products', 'sources', 'calls'].includes(activeSection);

        return (
            <div className="flex justify-between items-center mb-6">
                <h3 className="text-lg font-medium">{headers[activeSection] || ''}</h3>
                {showAddButton && (
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => {
                                    if (addHandlers[activeSection]) {
                                        addHandlers[activeSection]();
                                    }
                                }}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t(`Add ${headers[activeSection]}`)}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                )}
            </div>
        );
    };

    const [addHandlers, setAddHandlers] = useState({});

    const renderSectionContent = () => {
        switch (activeSection) {
            case 'general':
                return <General lead={lead} />;
            case 'tasks':
                return <Tasks lead={lead} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, tasks: handler}))} />;
            case 'users':
                return <Users lead={lead} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, users: handler}))} />;
            case 'products':
                return <Products lead={lead} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, products: handler}))} />;
            case 'sources':
                return <Sources lead={lead} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, sources: handler}))} />;
            case 'calls':
                return <Calls lead={lead} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, calls: handler}))} />;

            case 'files':
                return <Files lead={lead} />;
            case 'activity':
                return <Activity lead={lead} />;
            default:
                return (
                    <div className="text-center py-8 text-gray-500">
                        {t('This section is under development')}
                    </div>
                );
        }
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('CRM'), url: route('lead.index')},
                {label: t('Lead'), url: route('lead.leads.index')},
                {label: lead.name}
            ]}
            pageTitle={t('CRM Details')}
            backUrl={route('lead.leads.index')}
            pageActions={
                <div className="flex items-center gap-2">
                    {videoHubButtons && videoHubButtons.length > 0 && (
                        <div className="flex items-center gap-2">
                            {videoHubButtons.map((button, index) => (
                                <div key={button.id || index}>{button.component}</div>
                            ))}
                        </div>
                    )}
                    {spreadsheetButtons && spreadsheetButtons.length > 0 && (
                        <div className="flex items-center gap-2">
                            {spreadsheetButtons.map((button, index) => (
                                <div key={button.id || index}>{button.component}</div>
                            ))}
                        </div>
                    )}
                    {businessProcessMappingButtons && businessProcessMappingButtons.length > 0 && (
                        <div className="flex items-center gap-2">
                            {businessProcessMappingButtons.map((button, index) => (
                                <div key={button.id || index}>{button.component}</div>
                            ))}
                        </div>
                    )}
                    <ConvertToDeal lead={lead} deal={deal} />
                </div>
            }
        >
            <Head title={`${lead.name} - ${t('CRM Details')}`} />

            <div className="flex flex-col md:flex-row gap-6">
                <div className="md:w-56 flex-shrink-0">
                    <LeadSidebar activeItem={activeSection} onSectionChange={setActiveSection} />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            {activeSection !== 'general' && renderSectionHeader()}
                            {renderSectionContent()}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
