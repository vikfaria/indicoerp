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
import { Plus, Edit, Trash2, FileText } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

import SystemSetupSidebar from '../SystemSetupSidebar';
import Create from './Create';
import EditAccountType from './Edit';
import NoRecordsFound from '@/components/no-records-found';

interface AccountType {
    id: number;
    category_id: number;
    name: string;
    code: string;
    normal_balance: string;
    description?: string;
    is_active: boolean;
    category?: { name: string };
}

interface AccountTypeModalState {
    isOpen: boolean;
    mode: string;
    data: AccountType | null;
}

interface Props {
    accounttypes: AccountType[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { accounttypes, auth } = usePage<Props>().props;

    const [modalState, setModalState] = useState<AccountTypeModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.account-types.destroy',
        defaultMessage: t('Are you sure you want to delete this account type?')
    });

    const openModal = (mode: 'add' | 'edit', data: AccountType | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name')
        },
        {
            key: 'code',
            header: t('Code')
        },
        {
            key: 'normal_balance',
            header: t('Normal Balance'),
            render: (value: any) => value === 'debit' ? t('Debit') : t('Credit')
        },
        {
            key: 'category.name',
            header: t('Category Name'),
            render: (value: any, row: any) => row.category?.name || '-'
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
        ...(auth.user?.permissions?.some((p: string) => ['edit-account-types', 'delete-account-types'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, accounttype: AccountType) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-account-types') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', accounttype)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}

                        {auth.user?.permissions?.includes('delete-account-types') && accounttype.is_system_type == 0 && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(accounttype.id)}
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
                    {label: t('Account Types')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Account Types')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="account-types" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Account Types')}</h3>
                                    {auth.user?.permissions?.includes('create-account-types') && (
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
                                            data={accounttypes}
                                            columns={tableColumns}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={FileText}
                                                    title={t('No account types found')}
                                                    description={t('Get started by creating your first account type.')}
                                                    createPermission="create-account-types"
                                                    onCreateClick={() => openModal('add')}
                                                    createButtonText={t('Create Account Type')}
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
                        <EditAccountType
                            accounttype={modalState.data}
                            onSuccess={closeModal}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Account Type')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}
