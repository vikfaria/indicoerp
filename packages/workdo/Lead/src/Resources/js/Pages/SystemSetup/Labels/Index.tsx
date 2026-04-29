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
import { Plus, Edit, Trash2, Tag } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";

import Create from './Create';
import EditLabel from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { Label, LabelsIndexProps, LabelModalState } from './types';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { labels, auth, pipelines } = usePage<LabelsIndexProps>().props;

    // Filter pipelines that have labels
    const pipelinesWithLabels = pipelines.filter((pipeline: any) => 
        labels.some((label: Label) => label.pipeline_id === pipeline.id)
    );

    const [activePipeline, setActivePipeline] = useState<number>(pipelinesWithLabels[0]?.id || 0);
    const [modalState, setModalState] = useState<LabelModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'lead.labels.destroy',
        defaultMessage: t('Are you sure you want to delete this Label?')
    });

    const openModal = (mode: 'add' | 'edit', data: Label | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    // Filter labels by active pipeline
    const filteredLabels = labels.filter((label: Label) => label.pipeline_id === activePipeline);

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true,
            render: (value: string, label: Label) => (
                <div className="inline-flex items-center">
                    <div 
                        className="px-3 py-1 rounded text-white text-sm font-medium" 
                        style={{ backgroundColor: label.color || '#FF6B6B' }}
                    >
                        {label.name}
                    </div>
                </div>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-labels', 'delete-labels'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, label: Label) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-labels') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', label)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-labels') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(label.id)}
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
                    {label: t('CRM'), url: route('lead.leads.index')},
                    {label: t('System Setup')},
                    {label: t('Labels')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Labels')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="labels" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Labels')}</h3>
                                    {auth.user?.permissions?.includes('create-labels') && (
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

                                {/* Pipeline Tabs - Only show pipelines with labels */}
                                {pipelinesWithLabels.length > 0 && (
                                    <div className="flex border-b border-gray-200 mb-6">
                                        {pipelinesWithLabels.map((pipeline: any) => (
                                            <button
                                                key={pipeline.id}
                                                onClick={() => setActivePipeline(pipeline.id)}
                                                className={`px-6 py-3 font-medium text-sm border-b-2 transition-colors ${
                                                    activePipeline === pipeline.id
                                                        ? 'text-white rounded-t-lg'
                                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                                                }`}
                                                style={activePipeline === pipeline.id ? {
                                                    backgroundColor: 'hsl(var(--primary))',
                                                    borderColor: 'hsl(var(--primary))'
                                                } : {}}
                                            >
                                                {pipeline.name}
                                            </button>
                                        ))}
                                    </div>
                                )}
                                <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                                    {filteredLabels.length > 0 ? (
                                        <div className="space-y-3">
                                            {filteredLabels.map((label: Label) => (
                                                <div
                                                    key={label.id}
                                                    className="flex items-center gap-3 p-4 border rounded-lg bg-white border-gray-200 hover:shadow-md transition-all"
                                                >
                                                    <div className="flex-1 flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div 
                                                                className="px-3 py-1 rounded text-white text-sm font-medium" 
                                                                style={{ backgroundColor: label.color || '#FF6B6B' }}
                                                            >
                                                                {label.name}
                                                            </div>
                                                        </div>
                                                        <div className="flex gap-1">
                                                            <TooltipProvider>
                                                                {auth.user?.permissions?.includes('edit-labels') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', label)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                                <Edit className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Edit')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user?.permissions?.includes('delete-labels') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => openDeleteDialog(label.id)}
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
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <NoRecordsFound
                                            icon={Tag}
                                            title={t('No Labels found')}
                                            description={t('Get started by creating your first Label.')}
                                            createPermission="create-labels"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Label')}
                                            className="h-auto"
                                        />
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create onSuccess={closeModal} pipelines={pipelines} defaultPipelineId={activePipeline} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditLabel
                            label={modalState.data}
                            onSuccess={closeModal} pipelines={pipelines}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Label')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}