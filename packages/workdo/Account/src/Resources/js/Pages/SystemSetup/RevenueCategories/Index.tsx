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
import { Plus, Edit, Trash2, Settings } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";

import Create from './Create';
import EditRevenueCategories from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { RevenueCategories, RevenueCategoriesIndexProps, RevenueCategoriesModalState } from './types';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { revenuecategories, auth, chartofaccounts } = usePage<RevenueCategoriesIndexProps>().props;

    const [modalState, setModalState] = useState<RevenueCategoriesModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.revenue-categories.destroy',
        defaultMessage: t('Are you sure you want to delete this revenue categories?')
    });

    const openModal = (mode: 'add' | 'edit', data: RevenueCategories | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'category_name',
            header: t('Category Name'),
            sortable: false
        },
        {
            key: 'category_code',
            header: t('Category Code'),
            sortable: false
        },
        {
            key: 'gl_account.account_name',
            header: t('GL Account'),
            render: (value: any, row: any) => row.gl_account?.account_name || '-'
        },
        {
            key: 'description',
            header: t('Description'),
            sortable: false
        },
        {
            key: 'is_active',
            header: t('Is Active'),
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                    {value ? t('Active') : t('Inactive')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-revenue-categories', 'delete-revenue-categories'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, revenuecategories: RevenueCategories) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-revenue-categories') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', revenuecategories)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-revenue-categories') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(revenuecategories.id)}
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
                    {label: t('Accounting'), url:route('account.index')},
                    {label: t('System Setup')},
                    {label: t('Revenue Categories')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Revenue Categories')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="revenue-categories" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Revenue Categories')}</h3>
                                    {auth.user?.permissions?.includes('create-revenue-categories') && (
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
                                            data={revenuecategories}
                                            columns={tableColumns}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={Settings}
                                                    title={t('No Revenue Categories found')}
                                                    description={t('Get started by creating your first Revenue Categories.')}
                                                    createPermission="create-revenue-categories"
                                                    onCreateClick={() => openModal('add')}
                                                    createButtonText={t('Create Revenue Categories')}
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
                        <Create onSuccess={closeModal} chartofaccounts={chartofaccounts} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditRevenueCategories
                            revenuecategories={modalState.data}
                            onSuccess={closeModal} chartofaccounts={chartofaccounts}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Revenue Categories')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}
