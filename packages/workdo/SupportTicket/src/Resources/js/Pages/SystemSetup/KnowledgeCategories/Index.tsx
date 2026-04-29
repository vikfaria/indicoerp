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
import { Plus, Edit, Trash2, Library } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

import Create from './Create';
import EditCategory from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { usePageButtons } from '@/hooks/usePageButtons';

interface Category {
    id: number;
    title: string;
    created_at: string;
}

interface CategoriesIndexProps {
    categories: Category[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

interface CategoryModalState {
    isOpen: boolean;
    mode: string;
    data: Category | null;
}

export default function Index() {
    const { t } = useTranslation();
    const { categories = [], auth } = usePage<CategoriesIndexProps>().props;

    const [modalState, setModalState] = useState<CategoryModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const zendeskButtons = usePageButtons('zendeskSyncBtn',{ module: 'knowledgebasecategory', settingKey: 'zendesk_is_on' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'knowledge-category.destroy',
        defaultMessage: t('Are you sure you want to delete this category?')
    });

    const openModal = (mode: 'add' | 'edit', data: Category | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Category'),
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-knowledge-base', 'delete-knowledge-base'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, category: Category) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-knowledge-base') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', category)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-knowledge-base') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(category.id)}
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
                    {label: t('Support Tickets'), url: route('dashboard.support-tickets')},
                    {label: t('System Setup')},
                    {label: t('Knowledge Categories')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Knowledge Categories')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="knowledge-categories" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Knowledge Categories')}</h3>
                                    <div className="flex items-center gap-2">
                                        {zendeskButtons.map((button) => (
                                            <div key={button.id}>{button.component}</div>
                                        ))}
                                        {auth.user?.permissions?.includes('create-knowledge-base') && (
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
                                </div>
                                <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                                    <div className="min-w-[600px]">
                                        <DataTable
                                            data={categories}
                                            columns={tableColumns}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={Library}
                                                    title={t('No Knowledge Categories found')}
                                                    description={t('Get started by creating your first Knowledge Category.')}
                                                    createPermission="create-knowledge-base"
                                                    onCreateClick={() => openModal('add')}
                                                    createButtonText={t('Create Knowledge Category')}
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
                        <Create onSuccess={closeModal} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditCategory
                            category={modalState.data}
                            onSuccess={closeModal}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Knowledge Category')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}