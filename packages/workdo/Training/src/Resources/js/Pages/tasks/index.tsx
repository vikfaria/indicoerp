import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, CheckSquare, MessageSquare, Check } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import CreateTask from './create';
import EditTask from './edit';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { training, tasks, users, auth } = usePage().props;
    
    // Sort tasks to show latest on top
    const sortedTasks = (tasks.data || tasks).sort((a, b) => b.id - a.id);
    
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: '',
        data: null
    });


    const [deleteState, setDeleteState] = useState({
        isOpen: false,
        id: null,
        message: t('Are you sure you want to delete this task?')
    });

    const openDeleteDialog = (id) => {
        setDeleteState({
            isOpen: true,
            id,
            message: t('Are you sure you want to delete this task?')
        });
    };

    const closeDeleteDialog = () => {
        setDeleteState({
            isOpen: false,
            id: null,
            message: t('Are you sure you want to delete this task?')
        });
    };

    const confirmDelete = () => {
        if (deleteState.id) {
            router.delete(route('training.tasks.destroy', deleteState.id));
            closeDeleteDialog();
        }
    };

    const openModal = (mode, data = null) => {
        setModalState({
            isOpen: true,
            mode,
            data
        });
    };

    const closeModal = () => {
        setModalState({
            isOpen: false,
            mode: '',
            data: null
        });
    };



    const markTaskComplete = (taskId) => {
        router.patch(route('training.trainings.tasks.complete', [training.id, taskId]));
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Title')
        },
        {
            key: 'assigned_user',
            header: t('Assigned To'),
            render: (value, task) => task.assigned_user?.name || '-'
        },
        {
            key: 'due_date',
            header: t('Due Date'),
            render: (value) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value) => {
                const statusColors = {
                    pending: 'bg-yellow-100 text-yellow-800',
                    completed: 'bg-green-100 text-green-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value]}`}>
                        {t(value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' '))}
                    </span>
                );
            }
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_, task) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {task.status !== 'completed' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        onClick={() => markTaskComplete(task.id)}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Check className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Mark Complete')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-training-tasks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        onClick={() => openModal('edit', task)}
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
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    onClick={() => router.visit(route('training.tasks.feedbacks.index', task.id))}
                                    className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700"
                                >
                                    <MessageSquare className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Feedbacks')} ({task.feedbacks.length})</p>
                            </TooltipContent>
                        </Tooltip>
                        {auth.user?.permissions?.includes('delete-training-tasks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(task.id)}
                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
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
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Training')}, 
                {label: t('Training List'), url: route('training.trainings.index')},
                {label: training.title, url: route('training.trainings.index')},
                {label: t('Tasks')}
            ]}
            pageTitle={`${training.title} - ${t('Tasks')}`}
            backUrl={route('training.trainings.index')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-training-tasks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create Task')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Training Tasks')} />

            <Card className="shadow-sm">
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={sortedTasks}
                                columns={tableColumns}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={CheckSquare}
                                        title={t('No tasks found')}
                                        description={t('Get started by creating your first task.')}
                                        createPermission="create-training-tasks"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Task')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <CreateTask 
                        onSuccess={closeModal} 
                        training={training}
                        users={users}
                    />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditTask 
                        onSuccess={closeModal} 
                        training={training}
                        users={users}
                        task={modalState.data}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Task')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}