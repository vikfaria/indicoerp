import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { usePageButtons } from '@/hooks/usePageButtons';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Plus } from 'lucide-react';

import AuthenticatedLayout from '@/layouts/authenticated-layout';
import DealShowSidebar from "./Show/DealShowSidebar";
import General from "./Show/General";
import Tasks from "./Show/Tasks";
import Users from "./Show/Users";
import Products from "./Show/Products";
import Sources from "./Show/Sources";


import Files from "./Show/Files";
import Calls from "./Show/Calls";
import Activity from "./Show/Activity";
import Clients from "./Show/Clients";
import { Deal } from './types';

interface DealShowProps {
    deal: Deal;
    availableUsers: any[];
    availableProducts: any[];
    availableSources: any[];
    availableClients: any[];
}

export default function Show() {
    const { deal, availableUsers, availableProducts, availableSources, availableClients } = usePage<DealShowProps>().props;
    const { t } = useTranslation();
    const [activeSection, setActiveSection] = useState('general');
    const [currentDeal, setCurrentDeal] = useState(deal);
    const videoHubButtons = usePageButtons('dealShowButtons', { deal });
    const spreadsheetButtons = usePageButtons('spreadsheetBtn', { module: 'Deal', sub_module: deal.id });
    const businessProcessMappingButtons = usePageButtons('businessProcessMappingBtn', { module: 'Lead', submodule: 'Deal', id: deal.id });
    // Update currentDeal when deal prop changes
    useEffect(() => {
        setCurrentDeal(deal);
    }, [deal]);


    const renderSectionHeader = () => {
        const headers = {
            general: t('General'),
            tasks: t('Tasks'),
            users: t('Users'),
            products: t('Products'),
            sources: t('Sources'),
            files: t('Files'),
            calls: t('Calls'),
            clients: t('Clients'),
            activity: t('Activity')
        };

        const showAddButton = ['tasks', 'users', 'products', 'sources', 'calls', 'clients'].includes(activeSection);

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
                return <General deal={currentDeal} onStatusChange={(status) => {
                    setCurrentDeal(prev => ({...prev, status}));
                    router.post(route('lead.deals.change-status', deal.id), {
                        deal_status: status
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            router.reload({ only: ['deal'] });
                        }
                    });
                }} />;
            case 'tasks':
                return <Tasks deal={currentDeal} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, tasks: handler}))} onDealUpdate={setCurrentDeal} />;
            case 'users':
                return <Users deal={currentDeal} availableUsers={availableUsers} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, users: handler}))} onDealUpdate={setCurrentDeal} />;
            case 'products':
                return <Products deal={currentDeal} availableProducts={availableProducts} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, products: handler}))} onDealUpdate={setCurrentDeal} />;
            case 'sources':
                return <Sources deal={currentDeal} availableSources={availableSources} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, sources: handler}))} onDealUpdate={setCurrentDeal} />;
            case 'files':
                return <Files deal={currentDeal} onDealUpdate={setCurrentDeal} />;
            case 'calls':
                return <Calls deal={currentDeal} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, calls: handler}))} onDealUpdate={setCurrentDeal} />;
            case 'activity':
                return <Activity deal={currentDeal} onDealUpdate={setCurrentDeal} />;
            case 'clients':
                return <Clients deal={currentDeal} availableClients={availableClients} onRegisterAddHandler={(handler) => setAddHandlers(prev => ({...prev, clients: handler}))} onDealUpdate={setCurrentDeal} />;
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
                {label: t('Deals'), url: route('lead.deals.index')},
                {label: deal.name}
            ]}
            pageTitle={t('Deal Details')}
            backUrl={route('lead.deals.index')}
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
                </div>
            }
        >
            <Head title={`${deal.name} - ${t('Deal Details')}`} />

            <div className="flex flex-col md:flex-row gap-6">
                <div className="md:w-56 flex-shrink-0">
                    <DealShowSidebar
                        activeItem={activeSection}
                        onSectionChange={setActiveSection}
                    />
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
