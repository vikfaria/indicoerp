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
import { Plus, Edit, Trash2, Target, GripVertical } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import { toast } from 'sonner';

import Create from './Create';
import EditDealStage from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { DealStage, DealStagesIndexProps, DealStageModalState } from './types';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { dealstages, auth, pipelines } = usePage<DealStagesIndexProps>().props;

    // Filter pipelines that have deal stages
    const pipelinesWithStages = pipelines.filter((pipeline: any) => 
        dealstages.some((stage: DealStage) => stage.pipeline_id === pipeline.id)
    );

    const [activePipeline, setActivePipeline] = useState<number>(pipelinesWithStages[0]?.id || 0);
    const [modalState, setModalState] = useState<DealStageModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'lead.deal-stages.destroy',
        defaultMessage: t('Are you sure you want to delete this Deal Stage?')
    });

    const openModal = (mode: 'add' | 'edit', data: DealStage | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    // Filter stages by active pipeline
    const filteredStages = dealstages.filter((stage: DealStage) => stage.pipeline_id === activePipeline);

    const updateStageOrder = (newOrder: number[]) => {
        router.post(route('lead.deal-stages.update-order'), {
            stage_ids: newOrder,
            pipeline_id: activePipeline
        }, {
            preserveScroll: true,
            onSuccess: () => {
            },
            onError: () => {
                toast.error(t('Failed to update stage order'));
            }
        });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },               
        ...(auth.user?.permissions?.some((p: string) => ['edit-deal-stages', 'delete-deal-stages'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, dealstage: DealStage) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-deal-stages') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', dealstage)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-deal-stages') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(dealstage.id)}
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
                    {label: t('Deal Stages')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Deal Stages')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="deal-stages" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Deal Stages')}</h3>
                                    {auth.user?.permissions?.includes('create-deal-stages') && (
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

                                {/* Pipeline Tabs - Only show pipelines with stages */}
                                {pipelinesWithStages.length > 0 && (
                                    <div className="flex border-b border-gray-200 mb-6">
                                        {pipelinesWithStages.map((pipeline: any) => (
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
                                    {filteredStages.length > 0 ? (
                                        <div className="space-y-3">
                                            {filteredStages
                                                .sort((a: DealStage, b: DealStage) => a.order - b.order)
                                                .map((stage: DealStage, index: number) => (
                                                <div
                                                    key={stage.id}
                                                    draggable
                                                    onDragStart={(e) => {
                                                        e.dataTransfer.setData('text/plain', index.toString());
                                                    }}
                                                    onDragOver={(e) => {
                                                        e.preventDefault();
                                                    }}
                                                    onDrop={(e) => {
                                                        e.preventDefault();
                                                        const dragIndex = parseInt(e.dataTransfer.getData('text/plain'));
                                                        const sortedStages = filteredStages.sort((a: DealStage, b: DealStage) => a.order - b.order);
                                                        const newOrder = sortedStages.map((s: DealStage) => s.id);
                                                        const draggedItem = newOrder[dragIndex];
                                                        newOrder.splice(dragIndex, 1);
                                                        newOrder.splice(index, 0, draggedItem);
                                                        
                                                        updateStageOrder(newOrder);
                                                    }}
                                                    className="flex items-center gap-3 p-4 border rounded-lg bg-white border-gray-200 hover:shadow-md transition-all cursor-move"
                                                >
                                                    <GripVertical className="h-5 w-5 text-gray-400" />
                                                    <div className="flex-1 flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <span className="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
                                                                {stage.order}
                                                            </span>
                                                            <div>
                                                                <h4 className="font-medium text-gray-900">{stage.name}</h4>
                                                            </div>
                                                        </div>
                                                        <div className="flex gap-1">
                                                            <TooltipProvider>
                                                                {auth.user?.permissions?.includes('edit-deal-stages') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', stage)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                                <Edit className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Edit')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user?.permissions?.includes('delete-deal-stages') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => openDeleteDialog(stage.id)}
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
                                            icon={Target}
                                            title={t('No Deal Stages found')}
                                            description={t('Get started by creating your first Deal Stage.')}
                                            createPermission="create-deal-stages"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Deal Stage')}
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
                        <EditDealStage
                            dealstage={modalState.data}
                            onSuccess={closeModal} pipelines={pipelines}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Deal Stage')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}