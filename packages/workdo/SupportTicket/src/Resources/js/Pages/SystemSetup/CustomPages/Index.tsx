import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, ExternalLink, FileText } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import NoRecordsFound from '@/components/no-records-found';
import SystemSetupSidebar from "../SystemSetupSidebar";
import CreateModal from './Create';
import EditModal from './Edit';

interface CustomPage {
    id: number;
    title: string;
    slug: string;
    description: string;
    enable_page_footer: string;
}

interface CustomPagesProps {
    customPages: CustomPage[];
    auth: any;
    actualUserSlug: string;
}

interface ModalState {
    isOpen: boolean;
    mode: 'add' | 'edit' | '';
    data: CustomPage | null;
}

export default function Index() {
    const { t } = useTranslation();
    const { customPages, auth, actualUserSlug } = usePage<CustomPagesProps>().props;

    const [modalState, setModalState] = useState<ModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'support-ticket.custom-pages.destroy',
        defaultMessage: t('Are you sure you want to delete this custom page?')
    });

    const openModal = (mode: 'add' | 'edit', data: CustomPage | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleViewPage = (pageSlug: string) => {
        const pageUrl = route('support-ticket.custom-page', { slug: actualUserSlug, pageslug: pageSlug });
        window.open(pageUrl, '_blank');
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Title'),
            sortable: false,
            render: (_: any, page: CustomPage) => (
                <div className="font-medium">{page.title}</div>
            )
        },
        {
            key: 'slug',
            header: t('Slug'),
            sortable: false,
            render: (_: any, page: CustomPage) => (
                <div className="flex items-center gap-2">
                    <code className="text-sm bg-gray-100 px-2 py-1 rounded">{page.slug}</code>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleViewPage(page.slug)}
                        className="h-6 w-6 p-0"
                    >
                        <ExternalLink className="h-3 w-3" />
                    </Button>
                </div>
            )
        },
        {
            key: 'enable_page_footer',
            header: t('Status'),
            sortable: false,
            render: (_: any, page: CustomPage) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${page.enable_page_footer === 'on'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-800'
                    }`}>
                    {page.enable_page_footer === 'on' ? t('Enabled') : t('Disabled')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-support-settings'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, page: CustomPage) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-support-settings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openModal('edit', page)}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('manage-support-settings') && !['privacy-policy', 'terms-conditions'].includes(page.slug) && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(page.id)}
                                        className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Delete')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    { label: t('Support Tickets'), url: route('dashboard.support-tickets') },
                    { label: t('System Setup') },
                    { label: t('Custom Pages') }
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Custom Pages')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="custom-pages" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Custom Pages')}</h3>
                                    {auth.user?.permissions?.includes('manage-support-settings') && (
                                        <Tooltip delayDuration={0}>
                                            <TooltipTrigger asChild>
                                                <Button size="sm" onClick={() => openModal('add')}>
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('Create')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    )}
                                </div>
                                <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                                    <div className="min-w-[600px]">
                                        <DataTable
                                            data={customPages}
                                            columns={tableColumns}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={FileText}
                                                    title={t('No Custom Pages found')}
                                                    description={t('Get started by creating your first Custom Page.')}
                                                    createPermission="manage-support-settings"
                                                    onCreateClick={() => openModal('add')}
                                                    createButtonText={t('Create Custom Page')}
                                                    className="h-auto"
                                                />
                                            }
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <CreateModal onSuccess={closeModal} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditModal
                            customPage={modalState.data}
                            onSuccess={closeModal}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Custom Page')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}