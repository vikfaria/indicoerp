import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, Shield } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import Create from './Create';
import EditIpRestrict from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { IpRestrict, IpRestrictsIndexProps, IpRestrictModalState } from './types';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { route } from 'ziggy-js';

export default function Index() {
    const { t } = useTranslation();
    const { iprestricts, auth, ipRestrictEnabled: initialEnabled } = usePage<IpRestrictsIndexProps>().props;
    const [ipRestrictEnabled, setIpRestrictEnabled] = useState(initialEnabled === 'on');

    const [modalState, setModalState] = useState<IpRestrictModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.ip-restricts.destroy',
        defaultMessage: t('Are you sure you want to delete this Ip Restrict?')
    });

    const openModal = (mode: 'add' | 'edit', data: IpRestrict | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleToggleIpRestrict = (enabled: boolean) => {
        setIpRestrictEnabled(enabled);

        router.post(
            route('hrm.ip-restricts.toggle-setting'),
            { enabled },
            {
                preserveScroll: true,
                onError: () => {
                    setIpRestrictEnabled(!enabled);
                }
            }
        );
    };

    const tableColumns = [
        {
            key: 'ip',
            header: t('Ip'),
            sortable: false
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-ip-restricts', 'delete-ip-restricts'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, iprestrict: IpRestrict) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-ip-restricts') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', iprestrict)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-ip-restricts') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(iprestrict.id)}
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
                    {label: t('HRM')},
                    {label: t('System Setup')},
                    {label: t('Ip Restricts')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Ip Restricts')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="ip-restricts" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Ip Restricts')}</h3>
                                    <div className="flex items-center gap-4">
                                        <div className="flex items-center space-x-2">
                                             <Label htmlFor="ip-restrict-toggle" className="text-sm font-medium">
                                                {ipRestrictEnabled ? t('IP Restrict On') : t('IP Restrict Off')}
                                            </Label>
                                            <Switch
                                                id="ip-restrict-toggle"
                                                checked={ipRestrictEnabled}
                                                onCheckedChange={handleToggleIpRestrict}
                                            />
                                           
                                        </div>
                                        {auth.user?.permissions?.includes('create-ip-restricts') && (
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
                                            data={iprestricts}
                                            columns={tableColumns}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={Shield}
                                                    title={t('No Ip Restricts found')}
                                                    description={t('Get started by creating your first Ip Restrict.')}
                                                    createPermission="create-ip-restricts"
                                                    onCreateClick={() => openModal('add')}
                                                    createButtonText={t('Create Ip Restrict')}
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
                        <EditIpRestrict
                            iprestrict={modalState.data}
                            onSuccess={closeModal}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Ip Restrict')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}