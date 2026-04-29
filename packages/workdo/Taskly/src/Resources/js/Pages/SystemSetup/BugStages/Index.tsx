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
import { Plus, Edit, Trash2, Bug, GripVertical } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

import Create from './Create';
import EditBugStage from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { BugStage, BugStagesIndexProps, BugStageModalState } from './types';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { router } from '@inertiajs/react';

export default function Index() {
    const { t } = useTranslation();
    const { bugStages, auth } = usePage<BugStagesIndexProps>().props;

    const [modalState, setModalState] = useState<BugStageModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [draggedIndex, setDraggedIndex] = useState<number | null>(null);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'project.bug-stages.destroy',
        defaultMessage: t('Are you sure you want to delete this bug stage?')
    });

    const openModal = (mode: 'add' | 'edit', data: BugStage | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleDragStart = (e: React.DragEvent, index: number) => {
        setDraggedIndex(index);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (e: React.DragEvent, dropIndex: number) => {
        e.preventDefault();
        if (draggedIndex === null || draggedIndex === dropIndex) return;

        const reorderedStages = [...bugStages];
        const draggedStage = reorderedStages[draggedIndex];
        reorderedStages.splice(draggedIndex, 1);
        reorderedStages.splice(dropIndex, 0, draggedStage);

        // Update order and complete status
        const updatedStages = reorderedStages.map((stage, index) => ({
            id: stage.id,
            order: index + 1,
            complete: index === reorderedStages.length - 1 ? 1 : 0
        }));

        router.put(route('project.bug-stages.reorder'), {
            stages: updatedStages
        }, {
            preserveScroll: true
        });

        setDraggedIndex(null);
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Bug Stage')
        },
        {
            key: 'color',
            header: t('Color'),
            render: (value: string) => (
                <div className="flex items-center gap-2">
                    <div
                        className="w-6 h-6 rounded border border-gray-200"
                        style={{ backgroundColor: value }}
                    />
                </div>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-bug-stages', 'delete-bug-stages'].includes(p)) ? [{
            key: 'actions',
            header: t('Action'),
            render: (_: any, bugStage: BugStage) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-bug-stages') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', bugStage)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-bug-stages') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(bugStage.id)}
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
                    {label: t('Project'), url: route('project.index')},
                    {label: t('System Setup')},
                    {label: t('Bug Stage')}
                ]}
                pageTitle={t('System Setup')}
            >
                <Head title={t('Bug Stage')} />

                <div className="flex flex-col md:flex-row gap-8">
                    <div className="md:w-64 flex-shrink-0">
                        <SystemSetupSidebar activeItem="bug-stages" />
                    </div>

                    <div className="flex-1">
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-medium">{t('Bug Stage')}</h3>
                                    {auth.user?.permissions?.includes('create-bug-stages') && (
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
                                {bugStages.length > 0 ? (
                                    <div className="space-y-2">
                                        {bugStages.map((stage, index) => (
                                            <div
                                                key={stage.id}
                                                draggable
                                                onDragStart={(e) => handleDragStart(e, index)}
                                                onDragOver={handleDragOver}
                                                onDrop={(e) => handleDrop(e, index)}
                                                className={`flex items-center gap-3 p-4 border rounded-lg transition-all cursor-move bg-white border-gray-200 hover:shadow-md ${
                                                    index === bugStages.length - 1 ? 'border-green-300 bg-green-50' : ''
                                                }`}
                                            >
                                                <GripVertical className="h-5 w-5 text-gray-400" />
                                                <div className="flex-1 flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <span className="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
                                                            {index + 1}
                                                        </span>
                                                        <div className="flex items-center gap-3">
                                                            <div
                                                                className="w-6 h-6 rounded border border-gray-200"
                                                                style={{ backgroundColor: stage.color }}
                                                            />
                                                            <div>
                                                                <h4 className="font-medium text-gray-900">{stage.name}</h4>
                                                                {index === bugStages.length - 1 && (
                                                                    <p className="text-sm text-green-600 font-medium">{t('Done Stage')}</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('edit-bug-stages') && (
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
                                                            {auth.user?.permissions?.includes('delete-bug-stages') && (
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
                                        icon={Bug}
                                        title={t('No bug stages found')}
                                        description={t('Get started by creating your first bug stage.')}
                                        createPermission="create-bug-stages"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Bug Stage')}
                                        className="h-auto"
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create onSuccess={closeModal} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditBugStage
                            bugStage={modalState.data}
                            onSuccess={closeModal}
                        />
                    )}
                </Dialog>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Bug Stage')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}
